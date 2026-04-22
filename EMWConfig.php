<?php
$servername = "localhost";
$username = "gd3m89k_EMWUser";
$password = "IXEMWUserXI";
$dbname = "gd3m89k_EMWDatabase";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>