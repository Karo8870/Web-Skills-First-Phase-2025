<?php
include 'db_config.php';
include 'header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['message'] = "Login successful!";
            $_SESSION['message_type'] = "success";

            if ($user['role'] == 'patient') {
                header("Location: patient_dashboard.php");
            } else {
                header("Location: doctor_dashboard.php");
            }
            exit;
        } else {
            $_SESSION['message'] = "Invalid email or password";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid email or password";
        $_SESSION['message_type'] = "error";
    }
}
?>

<h2>Login</h2>
<form method="post" action="">
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>

    <button type="submit" class="btn">Login</button>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>
