<?php

require 'vendor/autoload.php'; // Load Appwrite SDK and PHPMailer

use Appwrite\Client;
use Appwrite\Services\Databases;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize Appwrite client
$client = new Client();
$client
    ->setEndpoint('https://cloud.appwrite.io/v1') // Replace with your Appwrite endpoint
    ->setProject('671737250034dc45f228'); // Replace with your project ID
   
$database = new Databases($client);

// Read the request body
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? null;

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

function generateOTP($length = 6) {
    return rand(100000, 999999); // Generate a 6-digit random OTP
}

function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.your-email-provider.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com'; // Your email
        $mail->Password = getenv('SMTP_PASSWORD'); // Use environment variable for SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your-email@example.com', 'Your App Name');
        $mail->addAddress($email); // Add a recipient

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body    = "Your One-Time Password (OTP) is: <b>$otp</b>";
        $mail->AltBody = "Your One-Time Password (OTP) is: $otp";

        // Send the email
        $mail->send();
        echo json_encode(['message' => 'OTP has been sent successfully!']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'OTP could not be sent.']);
    }
}

// Generate OTP
$otp = generateOTP(); // Generate OTP

try {
    // Save OTP to Appwrite collection
    $documentId = 'unique()'; // Automatically generate a unique ID
    $data = [
        'email' => $email,
        'otp' => $otp,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $database->createDocument('[YOUR_DATABASE_ID]', 'otps', $documentId, $data); // Replace [YOUR_DATABASE_ID] with the actual database ID
    sendOTP($email, $otp); // Send the OTP to the user's email
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save OTP.']);
}

?>
