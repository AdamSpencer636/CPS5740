<?php
session_start();
include 'db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$passenger_id = $_SESSION['passenger_id']; // Use logged-in user's ID

// Fetch bookings for the logged-in user
$upcoming_bookings = [];
$past_canceled_bookings = [];

$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.total_cost, b.number_of_passengers, b.class_of_service, b.num_of_bags, b.canceled
    FROM 2024F_spencead.Booking b
    JOIN 2024F_ortegjoh.Book bk ON b.booking_id = bk.booking_id
    WHERE bk.passenger_id = ?
");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['canceled'] || strtotime($row['booking_date']) < date("l")) {
            $past_canceled_bookings[] = $row;
        } else {
            $upcoming_bookings[] = $row;
        }
    }
} else {
    $error = "No bookings found.";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bookings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1, h2 {
            color: #007bff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
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
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <a href="index.html" class="btn">Home</a>
    <h1>View Your Bookings</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <h2>Upcoming Bookings</h2>
        <?php if (!empty($upcoming_bookings)): ?>
            <table>
                <tr>
                    <th>Booking ID</th>
                    <th>Booking Date</th>
                    <th>Total Cost</th>
                    <th>Number of Passengers</th>
                    <th>Class of Service</th>
                    <th>Number of Bags</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($upcoming_bookings as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_cost']); ?></td>
                        <td><?php echo htmlspecialchars($row['number_of_passengers']); ?></td>
                        <td><?php echo htmlspecialchars($row['class_of_service']); ?></td>
                        <td><?php echo htmlspecialchars($row['num_of_bags']); ?></td>
                        <td><?php echo $row['canceled'] ? 'Canceled' : 'Active'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No upcoming bookings found.</p>
        <?php endif; ?>

        <h2>Past or Canceled Bookings</h2>
        <?php if (!empty($past_canceled_bookings)): ?>
            <table>
                <tr>
                    <th>Booking ID</th>
                    <th>Booking Date</th>
                    <th>Total Cost</th>
                    <th>Number of Passengers</th>
                    <th>Class of Service</th>
                    <th>Number of Bags</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($past_canceled_bookings as $row): ?>
                    <tr class="<?php echo $row['canceled'] ? 'canceled' : ''; ?>">
                        <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_cost']); ?></td>
                        <td><?php echo htmlspecialchars($row['number_of_passengers']); ?></td>
                        <td><?php echo htmlspecialchars($row['class_of_service']); ?></td>
                        <td><?php echo htmlspecialchars($row['num_of_bags']); ?></td>
                        <td><?php echo $row['canceled'] ? 'Canceled' : 'Completed'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No past or canceled bookings found.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>
