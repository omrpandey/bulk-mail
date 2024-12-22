<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    $societyName = trim($_POST['societyName']);
    $columns = $_POST['columns'] ?? [];

    if (empty($societyName)) {
        $response['message'] = 'Error: Society name is required.';
        echo json_encode($response);
        exit;
    }

    if (!is_array($columns) || count($columns) === 0) {
        $response['message'] = 'Error: At least one column must be defined.';
        echo json_encode($response);
        exit;
    }

    $tableName = "society_" . preg_replace("/\s+/", "_", strtolower($societyName));
    $allowedTypes = ['INT', 'VARCHAR', 'TEXT', 'DATE', 'FLOAT', 'DOUBLE', 'BOOLEAN'];

    foreach ($columns as $key => $column) {
        if (empty($column['name']) || empty($column['type'])) {
            $response['message'] = 'Error: Column name and type are required for column ' . ($key + 1);
            echo json_encode($response);
            exit;
        }

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column['name'])) {
            $response['message'] = 'Error: Invalid column name \'' . htmlspecialchars($column['name']) . '\'.';
            echo json_encode($response);
            exit;
        }

        if (!in_array(strtoupper($column['type']), $allowedTypes)) {
            $response['message'] = 'Error: Invalid column type \'' . htmlspecialchars($column['type']) . '\'.';
            echo json_encode($response);
            exit;
        }
    }

    $sql = "CREATE TABLE `$tableName` (id INT AUTO_INCREMENT PRIMARY KEY";
    foreach ($columns as $column) {
        $name = $column['name'];
        $type = strtoupper($column['type']);
        if (strpos($type, 'VARCHAR') === 0 && !isset($column['length'])) {
            $type .= "(255)";
        } elseif (isset($column['length']) && strpos($type, 'VARCHAR') === 0) {
            $type .= "(" . intval($column['length']) . ")";
        }
        $sql .= ", `$name` $type";
    }
    $sql .= ")";

    $mysqli = new mysqli('localhost', 'root', '', 'society_db');
    if ($mysqli->connect_error) {
        $response['message'] = 'Connection failed: ' . $mysqli->connect_error;
        echo json_encode($response);
        exit;
    }

    if ($mysqli->query($sql)) {
        $response['success'] = true;
        $response['message'] = "Table '$tableName' created successfully.";
    } else {
        $response['message'] = 'Error creating table: ' . $mysqli->error;
    }

    
    $mysqli->close();
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
