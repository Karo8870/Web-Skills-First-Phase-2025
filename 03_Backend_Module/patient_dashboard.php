<?php
include 'db_config.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit;
}

if (isset($_POST['book_appointment'])) {
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $notes = htmlspecialchars($_POST['notes']);
    $patient_id = $_SESSION['user_id'];

    $dayOfWeek = date('l', strtotime($appointment_date));

    $stmt = $conn->prepare("SELECT * FROM availability 
                           WHERE doctor_id = ? 
                           AND day_of_week = ? 
                           AND ? BETWEEN start_time AND end_time");
    $stmt->bind_param("iss", $doctor_id, $dayOfWeek, $appointment_time);
    $stmt->execute();
    $availability_result = $stmt->get_result();

    $stmt = $conn->prepare("SELECT * FROM appointments 
                           WHERE doctor_id = ? 
                           AND appointment_date = ? 
                           AND appointment_time = ? 
                           AND status != 'canceled'");
    $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $stmt->execute();
    $existing_appointment = $stmt->get_result();

    if ($availability_result->num_rows > 0 && $existing_appointment->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO appointments 
                               (patient_id, doctor_id, appointment_date, appointment_time, notes) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $notes);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Appointment booked successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error booking appointment: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Selected time slot is not available.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: patient_dashboard.php");
    exit;
}

if (isset($_GET['cancel_appointment'])) {
    $appointment_id = $_GET['cancel_appointment'];
    $patient_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE appointments SET status = 'canceled' 
                           WHERE appointment_id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $appointment_id, $patient_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['message'] = "Appointment canceled successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error canceling appointment.";
        $_SESSION['message_type'] = "error";
    }

    header("Location: patient_dashboard.php");
    exit;
}

$doctors_query = "SELECT user_id, full_name, specialty FROM users WHERE role = 'doctor'";
$doctors_result = $conn->query($doctors_query);

$patient_id = $_SESSION['user_id'];
$appointments_query = "SELECT a.*, u.full_name, u.specialty
                      FROM appointments a
                      JOIN users u ON a.doctor_id = u.user_id
                      WHERE a.patient_id = ?
                      ORDER BY a.appointment_date, a.appointment_time";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
?>

<h2>Patient Dashboard</h2>
<h3>Welcome, <?= $_SESSION['full_name'] ?></h3>

<div>
    <h3>Book an Appointment</h3>
    <form method="post" action="">
        <div class="form-group">
            <label for="doctor_id">Select Doctor</label>
            <select id="doctor_id" name="doctor_id" required>
                <option value="">-- Select a doctor --</option>
                <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                    <option value="<?= $doctor['user_id'] ?>"><?= $doctor['full_name'] ?> (<?= $doctor['specialty'] ?>
                        )
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="appointment_date">Date</label>
            <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group">
            <label for="appointment_time">Time</label>
            <input type="time" id="appointment_time" name="appointment_time" required>
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>

        <button type="submit" name="book_appointment" class="btn">Book Appointment</button>
    </form>
</div>

<div>
    <h3>Your Appointments</h3>
    <table>
        <thead>
        <tr>
            <th>Doctor</th>
            <th>Specialty</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($appointments_result->num_rows > 0): ?>
            <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $appointment['full_name'] ?></td>
                    <td><?= $appointment['specialty'] ?></td>
                    <td><?= $appointment['appointment_date'] ?></td>
                    <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                    <td><?= ucfirst($appointment['status']) ?></td>
                    <td>
                        <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                            <a href="patient_dashboard.php?cancel_appointment=<?= $appointment['appointment_id'] ?>"
                               onclick="return confirm('Are you sure you want to cancel this appointment?')"
                               class="btn btn-danger">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No appointments found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
