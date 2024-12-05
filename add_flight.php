<?php
session_start();
include 'db_connection.php';

// Redirect if not an admin
// if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
//    header("Location: login.php");
//    exit;
//}

$error = "";
$message = "";

function generateUniqueFlightNumber($conn) {
    do {
        // Generate a random 3-digit flight number
        $flight_number = str_pad(rand(1, 999), 3, "0", STR_PAD_LEFT);

        // Check if the flight number already exists in the database
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM 2024F_allaican.Flight WHERE flight_number = ?");
        $stmt->bind_param("s", $flight_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

    } while ($row['count'] > 0); // Repeat until a unique number is found

    return $flight_number;
}


// Fetch airports for the dropdowns
$airports = [];
$result = $conn->query("SELECT airport_id, city FROM 2024F_ortegjoh.Airport");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $airports[] = $row;
    }
} else {
    die("No airports found or database error: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure_airport = $_POST['departure_airport'] ?? null;
    $arrival_airport = $_POST['arrival_airport'] ?? null;
    $base_price = $_POST['base_price'] ?? null;
    $number_of_stops = $_POST['number_of_stops'] ?? null;

    if (!$departure_airport || !$arrival_airport || !$base_price || $number_of_stops === null) {
        $error = "All fields are required.";
    } elseif ($departure_airport === $arrival_airport) {
        $error = "Departure and arrival cities cannot be the same.";
    } else {
        // Calculate flight time based on stops
        $flight_time = 1.5 + ($number_of_stops * 0.5); // Example logic
        $layover_time = $number_of_stops * 30; // 30 minutes per stop

        // Generate a unique 3-digit flight number
        $flight_number = generateUniqueFlightNumber($conn);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert into Flight table
            $stmt = $conn->prepare("
                INSERT INTO 2024F_allaican.Flight (flight_number, total_price, flight_duration, number_of_stops, layover_time) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sdiii", $flight_number, $base_price, $flight_time, $number_of_stops, $layover_time);
            $stmt->execute();
            $stmt->close();

            // Insert into DepartsArrives table for departure
            $stmt = $conn->prepare("
                INSERT INTO 2024F_allaican.DepartsArrives (flight_number, airport_id, is_departure) 
                VALUES (?, ?, 1)
            ");
            $stmt->bind_param("si", $flight_number, $departure_airport);
            $stmt->execute();
            $stmt->close();

            // Insert into DepartsArrives table for arrival
            $stmt = $conn->prepare("
                INSERT INTO 2024F_allaican.DepartsArrives (flight_number, airport_id, is_departure) 
                VALUES (?, ?, 0)
            ");
            $stmt->bind_param("si", $flight_number, $arrival_airport);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();
            $message = "Flight added successfully! Flight Number: $flight_number";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding flight: " . $e->getMessage();
        }
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Flight</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #007bff;
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

    <h1>Add a New Flight</h1>

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

    <form method="POST">
        <div class="form-group">
            <label for="departure_airport">Departure City:</label>
            <select id="departure_airport" name="departure_airport" required>
                <option value="">Select a departure city</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                        <?php echo htmlspecialchars($airport['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="arrival_airport">Arrival City:</label>
            <select id="arrival_airport" name="arrival_airport" required>
                <option value="">Select an arrival city</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                        <?php echo htmlspecialchars($airport['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="base_price">Base Price:</label>
            <input type="number" id="base_price" name="base_price" min="1" step="0.01" required>
        </div>

        <div class="form-group">
            <label for="number_of_stops">Number of Stops:</label>
            <input type="number" id="number_of_stops" name="number_of_stops" min="0" required>
        </div>

        <button type="submit" class="btn">Add Flight</button>
    </form>
</body>

</html>
