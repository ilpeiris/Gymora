<?php
// /Gymora/user/progress.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);
$user_id = $_SESSION['user_id'];

// Fetch the history logs for the table at the bottom
$historyStmt = $pdo->prepare("SELECT log_date, weight_kg, bmi, body_fat_pct, notes FROM progress_logs WHERE user_id = ? ORDER BY log_date DESC");
$historyStmt->execute([$user_id]);
$historyLogs = $historyStmt->fetchAll();

require_once '../includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="bi bi-graph-up-arrow"></i> My Progress Tracker</h2>
        <p class="text-muted">Log your stats and track your medical fitness journey.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-success h-100">
            <div class="card-header bg-success text-white fw-bold">
                Log Today's Stats
            </div>
            <div class="card-body">
                <div id="progressAlert"></div>
                
                <form id="logProgressForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Weight (kg) *</label>
                        <input type="number" step="0.1" id="weight_kg" name="weight_kg" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Body Fat % (Optional)</label>
                        <input type="number" step="0.1" id="body_fat_pct" name="body_fat_pct" class="form-control">
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Notes / How do you feel?</label>
                        <textarea id="notes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold">Save Log</button>
                    <small class="d-block mt-2 text-muted text-center">BMI is calculated automatically using your medical profile height.</small>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                Weight Trend (kg)
            </div>
            <div class="card-body">
                <canvas id="weightChart" height="100"></canvas>
            </div>
        </div>
        
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white fw-bold">
                BMI Trend
            </div>
            <div class="card-body">
                <canvas id="bmiChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white fw-bold">
                <i class="bi bi-journal-text"></i> Detailed History & Notes
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Body Fat %</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($historyLogs as $log): ?>
                            <tr>
                                <td class="align-middle fw-bold"><?= date('M j, Y', strtotime($log['log_date'])) ?></td>
                                <td class="align-middle"><?= htmlspecialchars($log['weight_kg']) ?></td>
                                <td class="align-middle text-primary"><?= htmlspecialchars($log['bmi']) ?></td>
                                <td class="align-middle"><?= $log['body_fat_pct'] ? htmlspecialchars($log['body_fat_pct']) . '%' : '<span class="text-muted">-</span>' ?></td>
                                <td class="align-middle text-muted small"><?= nl2br(htmlspecialchars($log['notes'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(count($historyLogs) == 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No logs recorded yet. Add your first log above!</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let weightChartInstance = null;
let bmiChartInstance = null;

function loadCharts() {
    fetch('../api/analytics.php?type=progress')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                renderCharts(data.dates, data.weights, data.bmis);
            }
        });
}

function renderCharts(dates, weights, bmis) {
    const ctxWeight = document.getElementById('weightChart').getContext('2d');
    const ctxBmi = document.getElementById('bmiChart').getContext('2d');

    if (weightChartInstance) weightChartInstance.destroy();
    if (bmiChartInstance) bmiChartInstance.destroy();

    weightChartInstance = new Chart(ctxWeight, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Weight (kg)',
                data: weights,
                borderColor: '#198754', 
                backgroundColor: 'rgba(25, 135, 84, 0.2)',
                borderWidth: 3,
                tension: 0.3, 
                fill: true
            }]
        },
        options: { responsive: true }
    });

    bmiChartInstance = new Chart(ctxBmi, {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'BMI',
                data: bmis,
                borderColor: '#0d6efd', 
                backgroundColor: 'rgba(13, 110, 253, 0.2)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true }
    });
}

// Handle Form Submission using AJAX
document.getElementById('logProgressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const alertBox = document.getElementById('progressAlert');
    alertBox.innerHTML = `<div class="alert alert-info py-2">Saving...</div>`;
    
    fetch('../api/log_progress.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            const textError = await response.text();
            throw new Error("PHP Error: " + textError.substring(0, 100));
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            alertBox.innerHTML = `<div class="alert alert-success py-2">Saved! Auto-calculated BMI: ${data.bmi}</div>`;
            this.reset();
            // Automatically refresh the page after 1 second so the table updates
            setTimeout(() => { window.location.reload(); }, 1000);
        } else {
            alertBox.innerHTML = `<div class="alert alert-danger py-2">${data.message}</div>`;
        }
    })
    .catch(error => {
        alertBox.innerHTML = `<div class="alert alert-danger py-2 fw-bold">System Crash:</div><div class="bg-dark text-danger p-2 small font-monospace">${error.message}</div>`;
        console.error('AJAX Parse Error:', error);
    });
});

document.addEventListener("DOMContentLoaded", loadCharts);
</script>

<?php require_once '../includes/footer.php'; ?>