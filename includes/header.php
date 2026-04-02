<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gymora - Smart Gym Management</title>

<!-- 1. Add this in <head> BEFORE your bootstrap link -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">



    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">




    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">



<!-- 2. Add this AFTER bootstrap.min.css -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/gymora-theme.css">


    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { font-weight: bold; color: #0d6efd !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>">Gymora</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
     
<ul class="navbar-nav ms-auto">
    <?php if (isLoggedIn()): ?>
        <li class="nav-item">
            <span class="nav-link text-light me-3">Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL . $_SESSION['role'] ?>/dashboard.php">Dashboard</a>
        </li>

        <?php if ($_SESSION['role'] === ROLE_USER): ?>
            <!--<li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>user/classes.php">Book Classes</a>
            </li>-->
          
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>user/profile.php">My Profile</a>
            </li>
            <!--<li class="nav-item">
    <a class="nav-link text-success fw-bold" href="<?= BASE_URL ?>user/progress.php"><i class="bi bi-graph-up"></i> Progress</a>
</li>-->
<li class="nav-item">
    <a class="nav-link text-danger fw-bold" href="<?= BASE_URL ?>user/medical_report.php"><i class="bi bi-file-medical"></i> Medical Profile</a>
</li>
  <li class="nav-item">
                <a class="nav-link fw-bold text-primary" href="<?= BASE_URL ?>user/chat.php"><i class="bi bi-chat-dots"></i> Messages</a>
            </li>
            
        <?php elseif ($_SESSION['role'] === ROLE_DOCTOR): ?>
           
            <li class="nav-item">
    <a class="nav-link" href="<?= BASE_URL ?>doctor/schedule.php">My Schedule</a>
</li>
  <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>doctor/profile.php">My Profile</a>
            </li>
             <li class="nav-item">
                <a class="nav-link fw-bold text-danger" href="<?= BASE_URL ?>doctor/chat.php"><i class="bi bi-chat-dots"></i> Patient Messages</a>
            </li>
            
        <?php elseif ($_SESSION['role'] === ROLE_TRAINER): ?>
            
            <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>trainer/classes.php">My Classes</a></li>
                <li class="nav-item">
    <a class="nav-link" href="<?= BASE_URL ?>trainer/schedule.php">My Schedule</a>
</li>
  <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>trainer/profile.php">My Profile</a>
            </li>
<li class="nav-item">
                <a class="nav-link fw-bold text-primary" href="<?= BASE_URL ?>trainer/chat.php"><i class="bi bi-chat-dots"></i> Client Messages</a>
            </li>

        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link text-danger ms-lg-3" href="<?= BASE_URL ?>auth/logout.php">Logout</a>
        </li>
    <?php else: ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>auth/login.php">Login</a>
        </li>
        <li class="nav-item">
            <a class="nav-link btn btn-primary text-white ms-2 px-3" href="<?= BASE_URL ?>auth/register.php">Register</a>
        </li>
    <?php endif; ?>
</ul>

    </div>
  </div>
</nav>

<div class="container">