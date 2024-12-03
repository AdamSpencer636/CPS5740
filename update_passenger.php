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
$passenger_id = $_SESSION['passenger_id']; // Use the logged-in user's ID

// Fetch the passenger's current details
$stmt = $conn->prepare("SELECT name, email, phone FROM 2024F_spencead.Passenger WHERE passenger_id = ?");
$stmt->bind_param("i", $passenger_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $passenger = $result->fetch_assoc();
} else {
    $error = "Could not retrieve passenger details.";
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'update') {
        $first_name = $_POST['first_name'] ?? null;
        $last_name = $_POST['last_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $phone = $_POST['phone'] ?? null;

        if (!$first_name || !$last_name || !$email || !$phone) {
            $error = "All fields are required for updates.";
        } else {
            $full_name = trim($first_name . " " . $last_name);

            // Update the passenger's details
            $stmt = $conn->prepare("UPDATE 2024F_spencead.Passenger SET name = ?, email = ?, phone = ? WHERE passenger_id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $passenger_id);

            if ($stmt->execute()) {
                $message = "Details updated successfully.";
                // Refresh the updated details
                $passenger['name'] = $full_name;
                $passenger['email'] = $email;
                $passenger['phone'] = $phone;
            } else {
                $error = "Error updating details: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        // Begin transaction for cascading delete
        $conn->begin_transaction();

        try {
            // Delete associated bookings
            $stmt = $conn->prepare("DELETE FROM 2024F_spencead.Book WHERE passenger_id = ?");
            $stmt->bind_param("i", $passenger_id);
            $stmt->execute();
            $stmt->close();

            // Delete the passenger
            $stmt = $conn->prepare("DELETE FROM 2024F_spencead.Passenger WHERE passenger_id = ?");
            $stmt->bind_param("i", $passenger_id);
            $stmt->execute();
            $stmt->close();

            // Commit the transaction
            $conn->commit();
            session_destroy(); // Log the user out
            header("Location: signup.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error deleting account: " . $e->getMessage();
        }
    } else {
        $error = "Invalid action.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update or Delete Passenger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #007bff;
        }

        h2 {
            color: #333;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            margin-bottom: 10px;
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

    <h1>Update or Delete Passenger</h1>

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

    <h2>Update Your Details</h2>
    <form method="POST">
        <?php
        // Split the name into first and last name for form inputs
        $name_parts = explode(" ", $passenger['name'], 2);
        $first_name = $name_parts[0] ?? '';
        $last_name = $name_parts[1] ?? '';
        ?>
        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($passenger['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($passenger['phone']); ?>" pattern="[0-9]{10}" required>
        </div>

        <button type="submit" name="action" value="update" class="btn">Update Details</button>
        <button type="submit" name="action" value="delete" class="btn btn-danger">Delete Account</button>
    </form>
</body>

</html>
