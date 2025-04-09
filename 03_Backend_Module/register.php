<?php
include 'db_config.php';
include 'header.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = htmlspecialchars($_POST['role']);
    $specialty = ($role == 'doctor') ? htmlspecialchars($_POST['specialty']) : NULL;
    $phone = htmlspecialchars($_POST['phone']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['message'] = "Email already exists";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, specialty, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $email, $password, $role, $specialty, $phone);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Registration successful. Please login.";
                $_SESSION['message_type'] = "success";
                header("Location: login.php");
                exit;
            } else {
                $_SESSION['message'] = "Error: " . $stmt->error;
                $_SESSION['message_type'] = "error";
            }
        }
    }
}
?>

<h2>Register</h2>
<form method="post" action="">
    <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" required>
    </div>

    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
    </div>

    <div class="form-group">
        <label for="role">Role</label>
        <select id="role" name="role" required onchange="showSpecialtyField()">
            <option value="patient">Patient</option>
            <option value="doctor">Doctor</option>
        </select>
    </div>

    <div class="form-group" id="specialty_field" style="display:none;">
        <label for="specialty">Specialty</label>
        <input type="text" id="specialty" name="specialty">
    </div>

    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" required>
    </div>

    <button type="submit" class="btn">Register</button>
</form>

<script>
    function showSpecialtyField() {
        var role = document.getElementById('role').value;
        var specialtyField = document.getElementById('specialty_field');

        if (role === 'doctor') {
            specialtyField.style.display = 'block';
            document.getElementById('specialty').required = true;
        } else {
            specialtyField.style.display = 'none';
            document.getElementById('specialty').required = false;
        }
    }
</script>
