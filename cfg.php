<?php
// ---------------------------
// cfg.php — konfiguracja projektu v1.6
// ---------------------------

// Sesja wymagana do logowania admina
session_start();

// Wersja projektu
$version = "v1.6";

// Dane logowania do panelu admina
$login = "admin";
$pass  = "admin123";

// Dane do połączenia z bazą
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "moja_strona";

// Połączenie z bazą danych
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
