<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "geotrees_db"; // Adjust database name if needed
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$csvFile = "C:\\xampp\\htdocs\\geotress\\uploads\\Copy of Carbon_Stock.csv"; // Adjust path if needed
if (!is_readable($csvFile)) {
    die("CSV file exists but is not readable.");
}

if (!file_exists($csvFile)) {
    die("CSV file not found.");
}

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $conn->query("START TRANSACTION"); // Begin transaction

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (count($data) < 12) continue; // Skip rows with missing values

        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO trees (tree_id, name, scientific_name, latitude, longitude, circumference, dbh_age, height, wood_density, agb, carbon_storage) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdddddddd", $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11]);

        if (!$stmt->execute()) {
            $conn->query("ROLLBACK"); // Rollback if error occurs
            die("Error inserting data: " . $stmt->error);
        }
    }
    fclose($handle);
    $conn->query("COMMIT"); // Commit transaction if all rows are inserted

    echo "CSV imported successfully!";
} else {
    echo "Failed to open CSV.";
}
$conn->close();
?>