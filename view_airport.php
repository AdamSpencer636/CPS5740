<?php
session_start();
include 'db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['passenger_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";
$airport_info = null;

// Fetch all airports for the dropdown
$airports = [];
$result = $conn->query("SELECT airport_id, city FROM 2024F_spencead.Airport");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $airports[] = $row;
    }
} else {
    $error = "No airports found.";
}

// Handle airport selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_airport_id = $_POST['airport_id'] ?? null;

    if ($selected_airport_id) {
        // Fetch information about the selected airport
        $stmt = $conn->prepare("SELECT airport_name, city, country FROM 2024F_spencead.Airport WHERE airport_id = ?");
        $stmt->bind_param("i", $selected_airport_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $airport_info = $result->fetch_assoc();
        } else {
            $error = "No information found for the selected airport.";
        }
        $stmt->close();
    } else {
        $error = "Please select an airport.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Airport Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
        }

        select,
        button {
            padding: 10px;
            font-size: 16px;
            margin-top: 5px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
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

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <a href="index.html" class="btn">Home</a>
    <h1>View Airport Information</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="airport_id">Select a City:</label>
            <select id="airport_id" name="airport_id" required>
                <option value="">-- Select a City --</option>
                <?php foreach ($airports as $airport): ?>
                    <option value="<?php echo htmlspecialchars($airport['airport_id']); ?>">
                        <?php echo htmlspecialchars($airport['city']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn">View Airport</button>
    </form>

    <?php if ($airport_info): ?>
        <h2>Airport Information</h2>
        <table>
            <tr>
                <th>Airport Name</th>
                <th>City</th>
                <th>Country</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($airport_info['airport_name']); ?></td>
                <td><?php echo htmlspecialchars($airport_info['city']); ?></td>
                <td><?php echo htmlspecialchars($airport_info['country']); ?></td>
            </tr>
        </table>
    <?php endif; ?>
</body>

</html>
