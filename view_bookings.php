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
$results = [];
$stmt = $conn->prepare("
    SELECT b.booking_id, b.booking_date, b.total_cost, b.number_of_passengers, b.class_of_service, b.num_of_bags, b.canceled
    FROM 2024F_spencead.Booking b
    JOIN 2024F_spencead.Book bk ON b.booking_id = bk.booking_id
    WHERE bk.passenger_id = ?
");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $results = $result->fetch_all(MYSQLI_ASSOC);
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
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #0056b3;
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

    <h1>View Your Bookings</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <table>
            <tr>
                <th>Booking ID</th>
                <th>Booking Date</th>
                <th>Total Cost</th>
                <th>Number of Passengers</th>
                <th>Number of Bags</th>
                <th>Class of Service</th>
                <th>Status</th>
            </tr>
            <?php foreach ($results as $row): ?>
                <tr class="<?php echo $row['canceled'] ? 'canceled' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['booking_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                    <td>$<?php echo number_format($row['total_cost'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['number_of_passengers']); ?></td>
                    <td><?php echo htmlspecialchars($row['num_of_bags']); ?></td>
                    <td><?php echo htmlspecialchars($row['class_of_service']); ?></td>
                    <td><?php echo $row['canceled'] ? 'Canceled' : 'Active'; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <a href="booking.php" class="btn">Back to Booking</a>
</body>

</html>
