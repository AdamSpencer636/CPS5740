<?php
session_start();
include 'db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch airports from the database
$airports = [];
$result = $conn->query("SELECT airport_id, city FROM 2024F_spencead.Airport");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $airports[] = $row;
    }
} else {
    die("No airports found or database error: " . $conn->error);
}

$passenger_id = $_SESSION['passenger_id']; // Use logged-in user's ID
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$error = "";
$message = "";

// Step 1: Search Flights
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_flights'])) {
    $departure_city = $_POST['departure_city'] ?? null;
    $arrival_city = $_POST['arrival_city'] ?? null;
    $booking_date = $_POST['booking_date'] ?? null;

    if (!$departure_city || !$arrival_city || !$booking_date) {
        $error = "Departure city, arrival city, and booking date are required.";
    } elseif ($departure_city === $arrival_city) {
        $error = "Departure and arrival cities cannot be the same.";
    } else {
        // Fetch matching flights
        $stmt = $conn->prepare("
            SELECT f.flight_number, f.flight_duration, f.layover_time, f.number_of_stops, f.total_price
            FROM 2024F_spencead.Flight f
            JOIN 2024F_spencead.DepartsArrives da1 ON f.flight_number = da1.flight_number AND da1.is_departure = 1
            JOIN 2024F_spencead.DepartsArrives da2 ON f.flight_number = da2.flight_number AND da2.is_departure = 0
            WHERE da1.airport_id = ? AND da2.airport_id = ?
        ");
        $stmt->bind_param("ii", $departure_city, $arrival_city);
        $stmt->execute();
        $flights = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($flights)) {
            $error = "No flights found for the selected criteria.";
        } else {
            $_SESSION['booking_date'] = $booking_date; // Store booking date in session
            $_SESSION['departure_city'] = $departure_city;
            $_SESSION['arrival_city'] = $arrival_city;
            $_SESSION['flights'] = $flights; // Store flight options in session
            $step = 2; // Move to Step 2
        }
    }
}

// Step 2: Select Flight
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_flight'])) {
    $selected_flight = $_POST['flight_number'] ?? null;

    if (!$selected_flight) {
        $error = "Please select a flight.";
    } else {
        $_SESSION['selected_flight'] = $selected_flight; // Store selected flight in session
        $step = 3; // Move to Step 3
    }
}

// Step 3: Complete Booking
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_booking'])) {
    $flight_number = $_SESSION['selected_flight'] ?? null;
    $booking_date = $_SESSION['booking_date'] ?? null; // Retrieve booking date from session
    $ktn = $_POST['ktn'] ?? null; // Optional
    $number_of_bags = $_POST['number_of_bags'] ?? 0;
    $number_of_passengers = $_POST['number_of_passengers'] ?? 1;
    $class_of_service = $_POST['class_of_service'] ?? 'Economy';

    if (!$flight_number || !$booking_date) {
        $error = "Flight selection and booking date are required.";
    } else {
        // Fetch the flight price
        $stmt = $conn->prepare("SELECT total_price FROM 2024F_spencead.Flight WHERE flight_number = ?");
        $stmt->bind_param("s", $flight_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $flight = $result->fetch_assoc();
        $stmt->close();

        if (!$flight) {
            $error = "Selected flight not found.";
        } else {
            $flight_price = $flight['total_price'];
            $total_cost = ($flight_price + ($number_of_bags * 25)) * $number_of_passengers; // Total cost calculation

            // Insert booking into the database
            $conn->begin_transaction();

            try {
                // Insert into Booking table
                $stmt = $conn->prepare("
                    INSERT INTO 2024F_spencead.Booking (booking_date, total_cost, number_of_passengers, class_of_service, num_of_bags) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("sdiss", $booking_date, $total_cost, $number_of_passengers, $class_of_service, $number_of_bags);
                $stmt->execute();
                $booking_id = $stmt->insert_id;
                $stmt->close();

                // Insert into Book table
                $stmt = $conn->prepare("INSERT INTO 2024F_spencead.Book (passenger_id, booking_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $passenger_id, $booking_id);
                $stmt->execute();
                $stmt->close();

                // Insert into Includes table
                $stmt = $conn->prepare("INSERT INTO 2024F_spencead.Includes (booking_id, flight_number) VALUES (?, ?)");
                $stmt->bind_param("is", $booking_id, $flight_number);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $message = "Booking completed successfully! Total Cost: $" . number_format($total_cost, 2);
                unset($_SESSION['selected_flight'], $_SESSION['booking_date']); // Clear session variables
                $step = 1; // Reset for new booking
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error completing booking: " . $e->getMessage();
            }
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
    <title>Create Booking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
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

        .flight-card {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .flight-card:hover {
            background-color: #f9f9f9;
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

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
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
    <a href="index.html" class="btn">Home</a>
    <h1>Create a New Booking</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <!-- Step 1: Search Flights -->
        <form method="POST">
            <input type="hidden" name="step" value="1">
            <input type="hidden" name="find_flights" value="1">
             <div class="form-group">
                <label for="departure_city">From:</label>
                <select id="departure_city" name="departure_city" required>
                    <option value="">Select a departure city</option>
                    <?php foreach ($airports as $airport): ?>
                        <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                            <?php echo htmlspecialchars($airport['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="arrival_city">To:</label>
                <select id="arrival_city" name="arrival_city" required>
                    <option value="">Select an arrival city</option>
                    <?php foreach ($airports as $airport): ?>
                        <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                            <?php echo htmlspecialchars($airport['city']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="booking_date">Booking Date:</label>
                <input type="date" id="booking_date" name="booking_date" required>
            </div>
            <button type="submit" class="btn btn-primary">Find Flights</button>
        </form>
    <?php elseif ($step === 2): ?>
        <!-- Step 2: Select Flight -->
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <input type="hidden" name="select_flight" value="1">
            <?php foreach ($flights as $flight): ?>
                <div class="flight-card">
                    <input type="radio" name="flight_number" value="<?php echo htmlspecialchars($flight['flight_number']); ?>" required>
                    <strong>Flight Number:</strong> <?php echo htmlspecialchars($flight['flight_number']); ?><br>
                    <strong>Duration:</strong> <?php echo htmlspecialchars($flight['flight_duration']); ?> hrs<br>
                    <strong>Stops:</strong> <?php echo htmlspecialchars($flight['number_of_stops']); ?><br>
                    <strong>Layover Time:</strong> <?php echo htmlspecialchars($flight['layover_time'] ?? 'No Layover'); ?><br>
                    <strong>Price:</strong> $<?php echo htmlspecialchars($flight['total_price']); ?>
                </div>
            <?php endforeach; ?>
            <button type="submit"class="btn btn-primary">Continue</button>
        </form>
    <?php elseif ($step === 3): ?>
        <!-- Step 3: Additional Details -->
        <form method="POST">
            <input type="hidden" name="step" value="3">
            <input type="hidden" name="complete_booking" value="1">
            <div class="form-group">
           <label for="number_of_passengers">Number of Passengers:</label>
            <input type="number" id="number_of_passengers" name="number_of_passengers" min="1" required><br><br>
            <label for="class_of_service">Class of Service:</label>
            <select id="class_of_service" name="class_of_service" required>
                <option value="Economy">Economy</option>
                <option value="Premium Economy">Premium Economy</option>
                <option value="Business">Business</option>
                <option value="First Class">First Class</option>
            </select><br><br>
            <label for="ktn">Known Traveler Number (KTN):</label>
            <input type="text" id="ktn" name="ktn"><br><br>
            <label for="number_of_bags">Number of Bags:</label>
            <input type="number" id="number_of_bags" name="number_of_bags" min="0" required default="0" ><br><br>
        </div>
            <button type="submit" class="btn btn-primary">Complete Booking</button>
        </form>
    <?php endif; ?>
</body>

</html>
