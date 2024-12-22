<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'path/to/PHPMailer/src/Exception.php';
require 'path/to/PHPMailer/src/PHPMailer.php';
require 'path/to/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $mail = new PHPMailer(true);
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $societyName = trim($_POST['societyName1']);

    if (empty($societyName)) {
        echo json_encode(['success' => false, 'message' => 'Society name cannot be empty.']);
        exit;
    }

    $tableName = "society_" . preg_replace("/[^a-z0-9]/i", "_", strtolower($societyName));

    $dbHost = 'localhost';
    $dbUser = 'root';
    $dbPass = '';
    $dbName = 'society_db';

    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }

    $query = "SELECT * FROM `$tableName`";
    $result = $conn->query($query);

    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No data found in the specified table.']);
        exit;
    }

    preg_match_all('/\{(.*?)\}/', $body, $matches);
    $placeholders = $matches[1] ?? [];

    $successCount = 0;
    $failureCount = 0;

    while ($row = $result->fetch_assoc()) {
        $processedBody = $body;

        foreach ($placeholders as $placeholder) {
            $replacement = $row[$placeholder] ?? '';
            $processedBody = str_replace("{{$placeholder}}", $replacement, $processedBody);
        }

        $email = $row['email'] ?? '';
        if (!empty($email)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.example.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ompandeyit.69@gmail.com';
                $mail->Password = '131205om';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('your_email@example.com', 'Your Name');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $processedBody;

                $mail->send();
                $successCount++;
            } catch (Exception $e) {
                $failureCount++;
            }
        } else {
            $failureCount++;
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Email sending process completed.',
        'successCount' => $successCount,
        'failureCount' => $failureCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
