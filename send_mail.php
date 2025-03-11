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

    $tableName = "society_" . preg_replace("/[^a-z0-9]/i", "_", strtolower($societyName)); // Sanitize society name

    // Database credentials
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

        $email = $row['email_id'] ?? '';
        if (!empty($email)) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ompandeyit.69@gmail.com';
                $mail->Password = 'stgl qdwz jbad zggn';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('ompandeyit.69@gmail.com', 'Om Pandey');
                $mail->addAddress($email);

                // Attachments
                if (!empty($_FILES['attachments']['name'][0])) {
                    // Loop through each file
                    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                        $filePath = $_FILES['attachments']['tmp_name'][$i];
                        $fileName = $_FILES['attachments']['name'][$i];
                        $fileType = mime_content_type($filePath); // Get the file type
                        $fileSize = $_FILES['attachments']['size'][$i]; // Get the file size
                
                        // Validate file type and size
                        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
                        $maxFileSize = 5 * 1024 * 1024; // 5MB limit
                
                        if (!in_array($fileType, $allowedTypes)) {
                            echo json_encode(['success' => false, 'message' => "Invalid file type: $fileName"]);
                            exit;
                        }
                
                        if ($fileSize > $maxFileSize) {
                            echo json_encode(['success' => false, 'message' => "File too large: $fileName"]);
                            exit;
                        }
                
                        // Attach the file to the email
                        $mail->addAttachment($filePath, $fileName);
                    }
                }
                
                // Email content
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
