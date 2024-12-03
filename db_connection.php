<?php
// Database connection settings
$host = 'imc.kean.edu';
$user = 'spencead'; 
$password = '1118126'; 
$dbname = '2024F_spencead'; 

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
