<?php
session_start();
include 'db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$message = "";
$passenger_id = $_SESSION['passenger_id']; // Use logged-in user's ID

// Handle form submission for updating a booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $booking_id = $_POST['booking_id'] ?? null;
    $number_of_passengers = $_POST['number_of_passengers'] ?? null;
    $number_of_bags = $_POST['number_of_bags'] ?? null;
    $class_of_service = $_POST['class_of_service'] ?? null;

    if (!$booking_id || !$number_of_passengers || !$class_of_service || $number_of_bags === null) {
        $error = "All fields are required to update a booking.";
    } else {
        // Update the booking
        $stmt = $conn->prepare("
            UPDATE 2024F_spencead.Booking b
            JOIN 2024F_spencead.Book bk ON b.booking_id = bk.booking_id
            SET b.number_of_passengers = ?, b.class_of_service = ?, b.num_of_bags = ?
            WHERE b.booking_id = ? AND bk.passenger_id = ?
        ");
        $stmt->bind_param("isiii", $number_of_passengers, $class_of_service, $number_of_bags, $booking_id, $passenger_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Booking updated successfully.";
        } else {
            $error = "Booking update failed or you don't have permission to update this booking.";
        }
        $stmt->close();
    }
}

// Handle form submission for canceling a booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = $_POST['booking_id'] ?? null;

    if (!$booking_id) {
        $error = "Booking ID is required to cancel.";
    } else {
        // Mark the booking as canceled
        $stmt = $conn->prepare("
            UPDATE 2024F_spencead.Booking b
            JOIN 2024F_spencead.Book bk ON b.booking_id = bk.booking_id
            SET b.canceled = 1
            WHERE b.booking_id = ? AND bk.passenger_id = ?
        ");
        $stmt->bind_param("ii", $booking_id, $passenger_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Booking canceled successfully.";
        } else {
            $error = "Failed to cancel the booking or you don't have permission to cancel this booking.";
        }
        $stmt->close();
    }
}

// Fetch bookings for the logged-in user
$bookings = [];
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.total_cost, b.number_of_passengers, b.num_of_bags, b.class_of_service, b.canceled
    FROM 2024F_spencead.Booking b
    JOIN 2024F_spencead.Book bk ON b.booking_id = bk.booking_id
    WHERE bk.passenger_id = ?
");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $bookings = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Booking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
        }

        th {
            background-color: #f2f2f2;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        .canceled {
            background-color: red;
            color: white;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #bd2130;
        }
         .home-button-container {
        text-align: right;
        margin-bottom: 20px;
    }

    .btn-home {
        display: inline-block;
        padding: 10px 15px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
        transition: background-color 0.3s;
    }

    .btn-home:hover {
        background-color: #0056b3;
    }
    </style>
</head>

<body>
    <div class="home-button-container">
    <a href="index.html" class="btn btn-home">Home</a>
</div>

    <h1>Update or Cancel Your Bookings</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="booking_id">Select Booking:</label>
            <select id="booking_id" name="booking_id" required>
                <option value="">-- Select a Booking --</option>
                <?php foreach ($bookings as $booking): ?>
                    <option value="<?php echo $booking['booking_id']; ?>" <?php echo $booking['canceled'] ? 'disabled' : ''; ?>>
                        <?php echo "Booking ID: " . htmlspecialchars($booking['booking_id']) . " | Date: " . htmlspecialchars($booking['booking_date']) . " | Bags: " . htmlspecialchars($booking['num_of_bags']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="number_of_passengers">Number of Passengers:</label>
            <input type="number" id="number_of_passengers" name="number_of_passengers" required>
        </div>

        <div class="form-group">
            <label for="number_of_bags">Number of Bags:</label>
            <input type="number" id="number_of_bags" name="number_of_bags" min="0" required>
        </div>

        <div class="form-group">
            <label for="class_of_service">Class of Service:</label>
            <select id="class_of_service" name="class_of_service" required>
                <option value="">Select class of service</option>
                <option value="Economy">Economy</option>
                <option value="Premium Economy">Premium Economy</option>
                <option value="Business">Business</option>
                <option value="First Class">First Class</option>
            </select>
        </div>

        <button type="submit" name="action" value="update" class="btn">Update Booking</button>
    </form>

    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label for="booking_id_cancel">Select Booking to Cancel:</label>
            <select id="booking_id_cancel" name="booking_id" required>
                <option value="">-- Select a Booking --</option>
                <?php foreach ($bookings as $booking): ?>
                    <option value="<?php echo $booking['booking_id']; ?>" <?php echo $booking['canceled'] ? 'disabled' : ''; ?>>
                        <?php echo "Booking ID: " . htmlspecialchars($booking['booking_id']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="action" value="cancel" class="btn btn-danger">Cancel Booking</button>
    </form>
</body>

</html>
