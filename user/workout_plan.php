<?php
// /Gymora/user/workout_plan.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_USER);

// Fetch all workout plans assigned to this specific user
$stmt = $pdo->prepare("
    SELECT w.*, t.name as trainer_name 
    FROM workout_plans w 
    JOIN users t ON w.trainer_id = t.id 
    WHERE w.user_id = ? AND w.status != 'draft'
    ORDER BY w.week_number ASC
");
$stmt->execute([$_SESSION['user_id']]);
$plans = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">My Workout Plan</li>
            </ol>
        </nav>
        <h2 class="fw-bold">My Training Program</h2>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if (count($plans) > 0): ?>
            <div class="accordion shadow-sm" id="workoutAccordion">
                <?php foreach ($plans as $index => $plan): ?>
                    <div class="accordion-item border-primary mb-3" style="border-radius: 8px; overflow: hidden;">
                        <h2 class="accordion-header" id="heading<?= $plan['id'] ?>">
                            <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $plan['id'] ?>">
                                <strong>Week <?= htmlspecialchars($plan['week_number']) ?></strong> 
                                <span class="badge bg-primary ms-3">Assigned by Trainer <?= htmlspecialchars($plan['trainer_name']) ?></span>
                            </button>
                        </h2>
                        <div id="collapse<?= $plan['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#workoutAccordion">
                            <div class="accordion-body bg-light">
                                <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                                    <span class="text-muted"><strong>Status:</strong> <?= ucfirst(htmlspecialchars($plan['status'])) ?></span>
                                    <span class="text-muted"><strong>Assigned on:</strong> <?= date('F j, Y', strtotime($plan['created_at'])) ?></span>
                                </div>
                                <div class="p-3 bg-white border rounded">
                                    <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($plan['notes']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center py-5">
                    <h4 class="text-warning mb-3">No Workout Plan Assigned Yet</h4>
                    <p class="text-muted">You need to be cleared by a doctor before a trainer can design your custom workout plan.</p>
                    <a href="appointments.php" class="btn btn-outline-dark mt-2">Check Appointments</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>