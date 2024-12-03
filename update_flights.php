<?php
session_start();
include 'db_connection.php';

// Redirect if not logged in (ensure admin validation as needed)
//if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
//    header("Location: login.php");
//    exit;
//}

$error = "";
$message = "";

// Fetch all flights
$flights = [];
$stmt = $conn->prepare("
    SELECT flight_number, total_price, number_of_stops
    FROM 2024F_spencead.Flight
");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $flights = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "No flights found.";
}
$stmt->close();

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flight_number = $_POST['flight_number'] ?? null;
    $total_price = $_POST['total_price'] ?? null;
    $number_of_stops = $_POST['number_of_stops'] ?? null;

    if (!$flight_number || !$total_price || $number_of_stops === null) {
        $error = "All fields are required.";
    } else {
        // Update the flight details
        $stmt = $conn->prepare("
            UPDATE 2024F_spencead.Flight
            SET total_price = ?, number_of_stops = ?
            WHERE flight_number = ?
        ");
        $stmt->bind_param("dis", $total_price, $number_of_stops, $flight_number);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = "Flight updated successfully.";
        } else {
            $error = "Update failed or no changes were made.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Flights</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }

        h1 {
            color: #007bff;
            text-align: center;
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .message {
            margin-top: 20px;
        }

        .message p {
            padding: 10px;
            border-radius: 5px;
        }

        .message .success {
            background-color: #d4edda;
            color: #155724;
        }

        .message .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <!-- Home Button -->
    <div class="home-button-container">
        <a href="index.html" class="btn btn-home">Home</a>
    </div>

    <h1>Update Flights</h1>

    <!-- Error/Message Display -->
    <?php if ($error): ?>
        <div class="message error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message success">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <!-- Update Form -->
    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label for="flight_number">Select Flight:</label>
            <select id="flight_number" name="flight_number" required>
                <option value="">-- Select a Flight --</option>
                <?php foreach ($flights as $flight): ?>
                    <option value="<?php echo htmlspecialchars($flight['flight_number']); ?>">
                        <?php echo "Flight: " . htmlspecialchars($flight['flight_number']) . " | Price: $" . htmlspecialchars($flight['total_price']) . " | Stops: " . htmlspecialchars($flight['number_of_stops']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="total_price">Base Price:</label>
            <input type="number" id="total_price" name="total_price" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="number_of_stops">Number of Stops:</label>
            <input type="number" id="number_of_stops" name="number_of_stops" required>
        </div>

        <button type="submit" class="btn">Update Flight</button>
    </form>
</body>

</html>
