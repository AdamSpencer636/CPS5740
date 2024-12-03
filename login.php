<?php
session_start();
include 'db_connection.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!$email || !$password) {
        $error = "Both email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT passenger_id, password FROM Passenger WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['passenger_id'] = $user['passenger_id'];
                header("Location: index.html");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No user found with this email.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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

        .message {
            margin-top: 20px;
        }

        .message p {
            padding: 10px;
            border-radius: 5px;
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
    <h1>Login</h1>
    <?php if ($error): ?>
        <div class="message error">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</body>
</html>

