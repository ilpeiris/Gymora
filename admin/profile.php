<?php
// /Gymora/admin/profile.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_ADMIN);
$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- FORM HANDLING: UPDATE PROFILE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $user_id]);
    
    if ($checkStmt->rowCount() > 0) {
        $error = "That email address is already in use.";
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $updateStmt->execute([$name, $email, $user_id]);
        $_SESSION['name'] = $name;
        $success = "Admin profile updated successfully!";
    }
}

// --- FORM HANDLING: CHANGE PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    $userRow = $userStmt->fetch();
    
    if (!password_verify($current_pass, $userRow['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $passStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $passStmt->execute([$new_hash, $user_id]);
        $success = "Security credentials updated!";
    }
}

// 1. Fetch current admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// 2. Fetch System Stats for the Admin display
$statUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$statPackages = $pdo->query("SELECT COUNT(*) FROM packages WHERE is_active = 1")->fetchColumn();

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold"><i class="bi bi-shield-lock-fill"></i> System Administrator</h2>
            <hr>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success shadow-sm"><i class="bi bi-check-circle"></i> <?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 bg-white mb-4">
                <div class="card-body text-center py-5">
                    <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                    <p class="text-dark fw-bold small text-uppercase mb-2">Platform Administrator</p>
                    <p class="text-muted small"><i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-dark bg-white">
                <div class="card-header bg-dark text-white fw-bold"><i class="bi bi-server"></i> System Overview</div>
                <div class="card-body py-4">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <span class="text-muted fw-bold">Total Registered Users</span>
                        <span class="badge bg-primary rounded-pill fs-6"><?= $statUsers ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fw-bold">Active Membership Tiers</span>
                        <span class="badge bg-success rounded-pill fs-6"><?= $statPackages ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-dark mb-4">
                <div class="card-header bg-dark text-white fw-bold">Profile Details</div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>
                        <div class="text-end"><button type="submit" class="btn btn-primary fw-bold px-4">Save Changes</button></div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white fw-bold"><i class="bi bi-key"></i> Security Credentials</div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3"><label class="form-label fw-bold">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label fw-bold">New Password</label><input type="password" name="new_password" class="form-control" minlength="6" required></div>
                            <div class="col-md-6 mb-4"><label class="form-label fw-bold">Confirm</label><input type="password" name="confirm_password" class="form-control" minlength="6" required></div>
                        </div>
                        <div class="text-end"><button type="submit" class="btn btn-danger fw-bold px-4">Update Password</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>