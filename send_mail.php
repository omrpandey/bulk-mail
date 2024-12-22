<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $body = $_POST['body'];
    $societyName = trim($_POST['societyName1']);

    if (empty($societyName)) {
        echo json_encode(['success' => false, 'message' => 'Society name cannot be empty.']);
        exit;
    }

    $tableName = "society_" . preg_replace("/[^a-z0-9]/i", "", strtolower($societyName)); // Remove any non-alphanumeric characters from the society name

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
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
                $mail->SMTPAuth = true;
                $mail->Username = 'ompandeyit.69@gmail.com'; // Your Gmail address
                $mail->Password = 'stgl qdwz jbad zggn'; // Your Gmail password or app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipients
                $mail->setFrom('ompandeyit.69@gmail.com', 'om pandey'); // Your Gmail address and name
                $mail->addAddress($email); // Add a recipient

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $processedBody;

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
