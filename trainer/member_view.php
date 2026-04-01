<?php
// /Gymora/trainer/member_view.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../dss/dss_engine.php';

requireRole(ROLE_TRAINER);

$client_id = $_GET['user_id'] ?? null;
if (!$client_id) die("Invalid request. Missing client ID.");

// 1. Fetch Client Details
$clientStmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$clientStmt->execute([$client_id]);
$client = $clientStmt->fetch();

if (!$client) die("Client not found.");

// 2. Fetch Latest Medical Assessment & Conditions
$medStmt = $pdo->prepare("
    SELECT weight_kg, height_cm, bmi, notes_encrypted, created_at 
    FROM medical_assessments 
    WHERE user_id = ? AND status = 'submitted' 
    ORDER BY created_at DESC LIMIT 1
");
$medStmt->execute([$client_id]);
$medical = $medStmt->fetch();

$conditions = [];
if ($medical) {
    $condStmt = $pdo->prepare("
        SELECT c.condition_name, c.severity 
        FROM medical_conditions c
        JOIN medical_assessments a ON c.assessment_id = a.id
        WHERE a.user_id = ? AND a.status = 'submitted' AND c.is_active = 1
    ");
    $condStmt->execute([$client_id]);
    $conditions = $condStmt->fetchAll();
}

// 3. Fetch Active Workout Plan
$planStmt = $pdo->prepare("SELECT id, week_number, notes, created_at FROM workout_plans WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$planStmt->execute([$client_id]);
$active_plan = $planStmt->fetch();

$plan_exercises = [];
if ($active_plan) {
    // Fetch the specific exercises attached to this plan
    $exStmt = $pdo->prepare("
        SELECT we.day_of_week, we.sets, we.reps, e.name as exercise_name
        FROM workout_exercises we
        JOIN exercises e ON we.exercise_id = e.id
        WHERE we.plan_id = ?
        ORDER BY FIELD(we.day_of_week, 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun')
    ");
    $exStmt->execute([$active_plan['id']]);
    $plan_exercises = $exStmt->fetchAll();
}

// 4. Fetch Progress Logs (History)
$progStmt = $pdo->prepare("SELECT log_date, weight_kg, bmi, body_fat_pct, notes FROM progress_logs WHERE user_id = ? ORDER BY log_date DESC");
$progStmt->execute([$client_id]);
$progress_logs = $progStmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Client Profile</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-bold">Client: <?= htmlspecialchars($client['name']) ?></h2>
            <a href="create_plan.php?user_id=<?= $client_id ?>" class="btn btn-primary fw-bold">
                <i class="bi bi-plus-circle"></i> Create New Workout Plan
            </a>
        </div>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-info h-100">
            <div class="card-header bg-info text-dark fw-bold">
                <i class="bi bi-clipboard2-pulse"></i> Medical Clearance Profile
            </div>
            <div class="card-body">
                <?php if ($medical): ?>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <strong>Latest Weight:</strong> <span><?= htmlspecialchars($medical['weight_kg']) ?> kg</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <strong>Latest BMI:</strong> <span><?= htmlspecialchars($medical['bmi']) ?></span>
                        </li>
                        <li class="list-group-item px-0">
                            <strong>Doctor's Notes:</strong><br>
                            <small class="text-muted"><?= nl2br(htmlspecialchars($medical['notes_encrypted'] ?? 'None')) ?></small>
                        </li>
                    </ul>
                    
                    <h6 class="fw-bold mt-3 border-bottom pb-1">Active Diagnoses (DSS Triggers)</h6>
                    <?php if (count($conditions) > 0): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($conditions as $cond): ?>
                                <li class="mb-2">
                                    <span class="badge bg-danger">Sev: <?= $cond['severity'] ?></span> 
                                    <?= ucwords(str_replace('_', ' ', htmlspecialchars($cond['condition_name']))) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted small">No active conditions reported.</p>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No medical assessment on file. Do not assign exercises.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm border-dark mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="bi bi-card-checklist"></i> Current Workout Plan
            </div>
            <div class="card-body">
                <?php if ($active_plan): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <h5 class="text-primary">Week <?= htmlspecialchars($active_plan['week_number']) ?> Plan</h5>
                        <small class="text-muted">Assigned: <?= date('M j, Y', strtotime($active_plan['created_at'])) ?></small>
                    </div>
                    
                    <div class="bg-light p-2 rounded border mb-3">
                        <strong>Trainer Notes:</strong><br>
                        <span style="white-space: pre-wrap;"><?= htmlspecialchars($active_plan['notes']) ?></span>
                    </div>

                    <?php if (count($plan_exercises) > 0): ?>
                        <h6 class="fw-bold border-bottom pb-1">Assigned Routine</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Exercise</th>
                                        <th>Sets</th>
                                        <th>Reps</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plan_exercises as $ex): ?>
                                        <tr>
                                            <td class="align-middle"><strong><?= htmlspecialchars($ex['day_of_week']) ?></strong></td>
                                            <td class="align-middle"><?= htmlspecialchars($ex['exercise_name']) ?></td>
                                            <td class="align-middle"><?= htmlspecialchars($ex['sets']) ?></td>
                                            <td class="align-middle"><?= htmlspecialchars($ex['reps']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small">No specific exercises were added to this plan.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-muted mb-0">No active workout plan for this client.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold">
                <i class="bi bi-graph-up"></i> Client Progress History
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Weight (kg)</th>
                                <th>BMI</th>
                                <th>Body Fat %</th>
                                <th>Trainer Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($progress_logs) > 0): ?>
                                <?php foreach ($progress_logs as $log): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($log['log_date'])) ?></td>
                                        <td><?= htmlspecialchars($log['weight_kg'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['bmi'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($log['body_fat_pct'] ?? '-') ?>%</td>
                                        <td><small><?= htmlspecialchars($log['notes'] ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No progress logs recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>