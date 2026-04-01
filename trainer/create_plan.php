<?php
// /Gymora/trainer/create_plan.php (Upgraded to DSS Workout Builder)
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';
require_once '../dss/dss_engine.php'; // Bring in the brain!

requireRole(ROLE_TRAINER);

$client_id = $_GET['user_id'] ?? null;
if (!$client_id) die("Invalid request. Missing client ID.");

$success = '';
$error = '';

// 1. Fetch Client Details
$clientStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$clientStmt->execute([$client_id]);
$client = $clientStmt->fetch();

// 2. RUN THE DSS ENGINE FOR THIS SPECIFIC USER
$dss_restrictions = getDSSRestrictionsForUser($client_id);
$blocked_ids = $dss_restrictions['blocked_exercise_ids'];
$warned_ids = $dss_restrictions['warned_exercise_ids'];
$reasons = $dss_restrictions['reasons'];

// 3. Fetch all available exercises
$exStmt = $pdo->query("SELECT * FROM exercises ORDER BY category, name");
$all_exercises = $exStmt->fetchAll();

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['week_number'])) {
    $week_number = intval($_POST['week_number']);
    $notes = trim($_POST['notes']);
    $status = 'active';
    
    try {
        $pdo->beginTransaction();
        
        // Insert the main plan record
        $planStmt = $pdo->prepare("INSERT INTO workout_plans (user_id, trainer_id, week_number, status, notes) VALUES (?, ?, ?, ?, ?)");
        $planStmt->execute([$client_id, $_SESSION['user_id'], $week_number, $status, $notes]);
        $plan_id = $pdo->lastInsertId();
        
        // Loop through submitted exercises
        if (isset($_POST['exercise_ids']) && is_array($_POST['exercise_ids'])) {
            $insertEx = $pdo->prepare("INSERT INTO workout_exercises (plan_id, exercise_id, sets, reps, day_of_week, dss_approved) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($_POST['exercise_ids'] as $ex_id) {
                // Double-check security: Hacker prevention! Don't let them submit a blocked ID
                if (in_array($ex_id, $blocked_ids)) {
                    continue; 
                }
                
                $sets = intval($_POST['sets'][$ex_id] ?? 0);
                $reps = trim($_POST['reps'][$ex_id] ?? '');
                $day = $_POST['day'][$ex_id] ?? 'Mon';
                
                // If they actually filled out sets/reps, save it
                if ($sets > 0 && !empty($reps)) {
                    // If it wasn't blocked or warned, it is 100% DSS approved
                    $dss_approved = (!in_array($ex_id, $warned_ids)) ? 1 : 0;
                    $insertEx->execute([$plan_id, $ex_id, $sets, $reps, $day, $dss_approved]);
                }
            }
        }
        
        $pdo->commit();
        $success = "DSS-Approved Workout Plan successfully assigned to " . htmlspecialchars($client['name']) . "!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save plan: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <h2 class="fw-bold">Intelligent Workout Builder</h2>
        <p class="text-muted">Designing plan for: <strong><?= htmlspecialchars($client['name']) ?></strong></p>
        <hr>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-dark h-100">
                <div class="card-header bg-dark text-white">Plan Details</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Week Number</label>
                        <input type="number" name="week_number" class="form-control" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Overall Notes & Instructions</label>
                        <textarea name="notes" class="form-control" rows="5" placeholder="Focus on form and rest intervals this week..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold mt-2">Save & Assign Plan</button>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    DSS Filtered Exercise Library
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Include</th>
                                    <th>Exercise</th>
                                    <th>Day</th>
                                    <th>Sets</th>
                                    <th>Reps</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_exercises as $ex): 
                                    $ex_id = $ex['id'];
                                    $is_blocked = in_array($ex_id, $blocked_ids);
                                    $is_warned = in_array($ex_id, $warned_ids);
                                    $row_class = $is_blocked ? 'table-danger text-muted' : ($is_warned ? 'table-warning' : '');
                                ?>
                                    <tr class="<?= $row_class ?>">
                                        <td>
                                            <?php if ($is_blocked): ?>
                                                <i class="bi bi-lock-fill text-danger fs-5" title="Medically Blocked"></i>
                                            <?php else: ?>
                                                <input class="form-check-input fs-5" type="checkbox" name="exercise_ids[]" value="<?= $ex_id ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($ex['name']) ?></strong><br>
                                            <small><?= htmlspecialchars($ex['muscle_groups']) ?></small>
                                            
                                            <?php if ($is_blocked || $is_warned): ?>
                                                <div class="text-danger small mt-1 fw-bold">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($reasons[$ex_id] ?? 'Medical Contraindication') ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select name="day[<?= $ex_id ?>]" class="form-select form-select-sm" <?= $is_blocked ? 'disabled' : '' ?>>
                                                <option value="Mon">Mon</option>
                                                <option value="Tue">Tue</option>
                                                <option value="Wed">Wed</option>
                                                <option value="Thu">Thu</option>
                                                <option value="Fri">Fri</option>
                                                <option value="Sat">Sat</option>
                                                <option value="Sun">Sun</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="sets[<?= $ex_id ?>]" class="form-control form-control-sm" placeholder="e.g. 3" <?= $is_blocked ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <input type="text" name="reps[<?= $ex_id ?>]" class="form-control form-control-sm" placeholder="e.g. 10-12" <?= $is_blocked ? 'disabled' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>