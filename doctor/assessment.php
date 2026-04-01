<?php
// /Gymora/doctor/assessment.php
require_once '../config/db.php';
require_once '../config/session.php';
require_once '../config/constants.php';

requireRole(ROLE_DOCTOR);

$error = '';
$success = '';

// Get the IDs from the URL (e.g., ?appointment_id=1&patient_id=2)
$appointment_id = $_GET['appointment_id'] ?? null;
$patient_id = $_GET['patient_id'] ?? null;

if (!$appointment_id || !$patient_id) {
    die("Invalid request. Missing appointment or patient ID.");
}

// Fetch Patient Details
$patStmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$patStmt->execute([$patient_id]);
$patient = $patStmt->fetch();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = floatval($_POST['weight_kg']);
    $height = floatval($_POST['height_cm']);
    $bp = trim($_POST['blood_pressure']);
    $notes = trim($_POST['medical_notes']);
    
    if ($weight <= 0 || $height <= 0) {
        $error = "Please enter valid weight and height.";
    } else {
        // Calculate BMI: Weight (kg) / [Height (m)]^2
        $height_in_meters = $height / 100;
        $bmi = round($weight / ($height_in_meters * $height_in_meters), 1);
        
        try {
            $pdo->beginTransaction();
            
            // 1. Insert the medical assessment
            $insertStmt = $pdo->prepare("
                INSERT INTO medical_assessments (user_id, weight_kg, height_cm, bmi, blood_pressure, medical_notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'submitted')
            ");
            $insertStmt->execute([$patient_id, $weight, $height, $bmi, $bp, $notes]);
            
            // 2. Mark the appointment as completed
            $updateAppt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $updateAppt->execute([$appointment_id]);
            
            $pdo->commit();
            $success = "Assessment submitted successfully! BMI calculated as $bmi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to submit assessment: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row mt-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Patient Assessment</li>
            </ol>
        </nav>
        <h2 class="fw-bold">Medical Assessment: <?= htmlspecialchars($patient['name']) ?></h2>
        <hr>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Record Patient Vitals</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <br><br>
                        <a href="dashboard.php" class="btn btn-success">Return to Dashboard</a>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" step="0.1" name="weight_kg" class="form-control" required placeholder="e.g. 75.5">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Height (cm)</label>
                                <input type="number" step="0.1" name="height_cm" class="form-control" required placeholder="e.g. 180">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Blood Pressure</label>
                                <input type="text" name="blood_pressure" class="form-control" placeholder="e.g. 120/80">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Medical Notes & Recommendations</label>
                            <textarea name="medical_notes" class="form-control" rows="4" placeholder="Enter any restrictions or recommendations for the trainer..."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary fw-bold">Submit Assessment & Calculate BMI</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>