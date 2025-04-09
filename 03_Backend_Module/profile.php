<?php
include 'db_config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);

    $specialty = ($role == 'doctor') ? htmlspecialchars($_POST['specialty']) : NULL;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Email already exists";
        $_SESSION['message_type'] = "error";
    } else {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            if ($role == 'doctor') {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, specialty = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $full_name, $email, $password, $specialty, $phone, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $password, $phone, $user_id);
            }
        } else {
            if ($role == 'doctor') {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, specialty = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("ssssi", $full_name, $email, $specialty, $phone, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
                $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
            }
        }

        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;

            $_SESSION['message'] = "Profile updated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating profile: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<h2>Edit Profile</h2>
<form method="post" action="">
    <div class="form-group">
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>"
               required>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
    </div>
    <?php if ($role == 'doctor'): ?>
        <div class="form-group">
            <label for="specialty">Specialty</label>
            <input type="text" id="specialty" name="specialty" value="<?= htmlspecialchars($user['specialty']) ?>"
                   required>
        </div>
    <?php endif; ?>
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
    </div>
    <button type="submit" class="btn">Update Profile</button>
</form>
<p><a href="<?= $role == 'patient' ? 'patient_dashboard.php' : 'doctor_dashboard.php' ?>">Back to Dashboard</a></p>
