<?php
// /Gymora/trainer/dashboard.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_TRAINER);

// Fetch all users who have a package and their latest BMI
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.package_id, 
    (SELECT bmi FROM medical_assessments WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) as latest_bmi
    FROM users u 
    WHERE u.role = 'user'
");
$clients = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">Trainer Dashboard</h2>
        <p class="text-muted">Manage clients and design workout plans based on medical clearance.</p>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Gym Members / Clients</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member Name</th>
                            <th>Latest BMI</th>
                            <th>Medical Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td class="align-middle"><?= htmlspecialchars($client['name']) ?></td>
                                <td class="align-middle">
                                    <?= $client['latest_bmi'] ?? '<span class="text-muted small">No data</span>' ?>
                                </td>
                                <td class="align-middle">
                                    <?php if ($client['latest_bmi']): ?>
                                        <span class="badge bg-success">Cleared by Doctor</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Awaiting Assessment</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle">
    <a href="member_view.php?user_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-dark">
        View Profile
    </a>
    <a href="create_plan.php?user_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary <?= !$client['latest_bmi'] ? 'disabled' : '' ?>">
        Build Plan
    </a>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>