<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Check if file is uploaded successfully
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != UPLOAD_ERR_OK) {
        $response['message'] = 'Error uploading file.';
        echo json_encode($response);
        exit;
    }

    $societyName = trim($_POST['societyName1']);
    if (empty($societyName)) {
        $response['message'] = 'Table name cannot be empty.';
        echo json_encode($response);
        exit;
    }

    $tableName = "society_" . preg_replace("/\\s+/", "_", strtolower($societyName));
    $fileTmpPath = $_FILES['csv_file']['tmp_name'];
    $fileName = $_FILES['csv_file']['name'];

    // Validate file extension
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    if (strtolower($fileExtension) !== 'csv') {
        $response['message'] = 'Invalid file type. Only CSV files are allowed.';
        echo json_encode($response);
        exit;
    }

    // Read the CSV file
    $csvData = array_map('str_getcsv', file($fileTmpPath));
    if (!$csvData || count($csvData) <= 1) {
        $response['message'] = 'CSV file is empty or invalid.';
        echo json_encode($response);
        exit;
    }

    // Extract column names from the first row of the CSV
    $columns = array_map(function ($col) {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($col));
    }, $csvData[0]);

    // Prepare database connection
    $mysqli = new mysqli('localhost', 'root', '', 'society_db');
    if ($mysqli->connect_error) {
        $response['message'] = 'Database connection failed: ' . $mysqli->connect_error;
        echo json_encode($response);
        exit;
    }

    // Check if table exists, if not create it
    $result = $mysqli->query("SHOW TABLES LIKE '$tableName'");
    if ($result->num_rows === 0) {
        $createTableSql = "CREATE TABLE `$tableName` (" . implode(", ", array_map(
            fn($col) => "`$col` VARCHAR(255)", $columns)) . ")";
        if (!$mysqli->query($createTableSql)) {
            $response['message'] = 'Error creating table: ' . $mysqli->error;
            echo json_encode($response);
            exit;
        }
    }

    // Insert data
    $insertSql = "INSERT INTO `$tableName` (`" . implode('`, `', $columns) . "`) VALUES ";
    $valuePlaceholders = [];
    $valueData = [];

    foreach (array_slice($csvData, 1) as $row) {
        $valuePlaceholders[] = '(' . implode(', ', array_fill(0, count($row), '?')) . ')';
        $valueData = array_merge($valueData, $row);
    }

    $stmt = $mysqli->prepare($insertSql . implode(', ', $valuePlaceholders));
    if (!$stmt) {
        $response['message'] = 'Error preparing statement: ' . $mysqli->error;
        echo json_encode($response);
        exit;
    }

    $paramTypes = str_repeat('s', count($valueData));
    $stmt->bind_param($paramTypes, ...$valueData);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'File uploaded and data inserted successfully.';
    } else {
        $response['message'] = 'Error inserting data: ' . $stmt->error;
    }

    // Cleanup
    $stmt->close();
    $mysqli->close();

    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
