<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "geotrees_db"; 

// Buat sambungan dengan mekanisme try-catch untuk kestabilan
$conn = new mysqli($servername, $username, $password, $dbname);

// Semak sambungan
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set karakter untuk elak masalah encoding
$conn->set_charset("utf8mb4");

// Pastikan sambungan tidak ditutup di sini supaya boleh digunakan di index.php
?>