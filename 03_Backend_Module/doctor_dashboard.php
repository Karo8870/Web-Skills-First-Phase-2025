<?php
include 'db_config.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = $_SESSION['user_id'];

if (isset($_POST['set_availability'])) {
    $day = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    if ($start_time >= $end_time) {
        $_SESSION['message'] = "Start time must be before end time.";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("SELECT * FROM availability WHERE doctor_id = ? AND day_of_week = ?");
        $stmt->bind_param("is", $doctor_id, $day);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE availability SET start_time = ?, end_time = ? 
                                   WHERE doctor_id = ? AND day_of_week = ?");
            $stmt->bind_param("ssis", $start_time, $end_time, $doctor_id, $day);
        } else {
            $stmt = $conn->prepare("INSERT INTO availability (doctor_id, day_of_week, start_time, end_time) 
                                   VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $doctor_id, $day, $start_time, $end_time);
        }

        if ($stmt->execute()) {
            $_SESSION['message'] = "Availability set successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error setting availability: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
    }

    header("Location: doctor_dashboard.php");
    exit;
}

if (isset($_POST['update_appointment'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
    $stmt->bind_param("sii", $status, $appointment_id, $doctor_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Appointment updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating appointment: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }

    header("Location: doctor_dashboard.php");
    exit;
}

$availability_query = "SELECT * FROM availability WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$stmt = $conn->prepare($availability_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$availability_result = $stmt->get_result();

$appointments_query = "SELECT a.*, u.full_name
                      FROM appointments a
                      JOIN users u ON a.patient_id = u.user_id
                      WHERE a.doctor_id = ?
                      ORDER BY a.appointment_date, a.appointment_time";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments_result = $stmt->get_result();
?>

<h2>Doctor Dashboard</h2>
<h3>Welcome, Dr. <?= $_SESSION['full_name'] ?></h3>

<div>
    <h3>Set Your Availability</h3>
    <form method="post" action="">
        <div class="form-group">
            <label for="day_of_week">Day of Week</label>
            <select id="day_of_week" name="day_of_week" required>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>
        </div>

        <div class="form-group">
            <label for="start_time">Start Time</label>
            <input type="time" id="start_time" name="start_time" required>
        </div>

        <div class="form-group">
            <label for="end_time">End Time</label>
            <input type="time" id="end_time" name="end_time" required>
        </div>

        <button type="submit" name="set_availability" class="btn">Set Availability</button>
    </form>
</div>

<div>
    <h3>Your Current Availability</h3>
    <table>
        <thead>
        <tr>
            <th>Day</th>
            <th>Start Time</th>
            <th>End Time</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($availability_result->num_rows > 0): ?>
            <?php while ($availability = $availability_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $availability['day_of_week'] ?></td>
                    <td><?= date('h:i A', strtotime($availability['start_time'])) ?></td>
                    <td><?= date('h:i A', strtotime($availability['end_time'])) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">No availability set.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div>
    <h3>Your Appointments</h3>
    <table>
        <thead>
        <tr>
            <th>Patient</th>
            <th>Date</th>
            <th>Time</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($appointments_result->num_rows > 0): ?>
            <?php while ($appointment = $appointments_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $appointment['full_name'] ?></td>
                    <td><?= $appointment['appointment_date'] ?></td>
                    <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                    <td><?= $appointment['notes'] ?></td>
                    <td><?= ucfirst($appointment['status']) ?></td>
                    <td>
                        <?php if ($appointment['status'] != 'canceled'): ?>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="appointment_id"
                                       value="<?= $appointment['appointment_id'] ?>">
                                <select name="status" required>
                                    <option value="pending" <?= $appointment['status'] == 'pending' ? 'selected' : '' ?>>
                                        Pending
                                    </option>
                                    <option value="confirmed" <?= $appointment['status'] == 'confirmed' ? 'selected' : '' ?>>
                                        Confirmed
                                    </option>
                                    <option value="canceled">Cancel</option>
                                </select>
                                <button type="submit" name="update_appointment" class="btn">Update</button>
                            </form>
                        <?php else: ?>
                            Canceled
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
