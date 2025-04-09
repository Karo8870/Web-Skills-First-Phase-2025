<?php
include 'header.php';
?>

<?php if (!isset($_SESSION['user_id'])): ?>
    <div>
        <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to continue.</p>
    </div>
<?php else: ?>
    <div>
        <p>Welcome, <?= $_SESSION['full_name']; ?>!</p>
        <?php if ($_SESSION['role'] == 'patient'): ?>
            <a href="patient_dashboard.php">Patient dashboard</a>
        <?php else: ?>
            <a href="doctor_dashboard.php">Doctor dashboard</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

