<?php
$servername = "localhost";
$username   = "root";   // TIP 3: w XAMPP najczęściej root
$password   = "";       // w XAMPP standardowo puste hasło
$dbname     = "moja_strona";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>

