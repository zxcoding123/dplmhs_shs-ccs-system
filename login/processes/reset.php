<?php
require_once 'conn.php'; // Database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; // PHPMailer's autoload

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $_POST['email'];

    try {
        // Check if email exists in any of the tables
        $user = null;
        $table = null;

        // Query to check all tables
        $queries = [
            "SELECT 'admin' as role, id as user_id FROM admin WHERE email = :email",
            "SELECT 'staff_accounts' as role, id as user_id FROM staff_accounts WHERE email = :email",
            "SELECT 'students' as role, id as user_id FROM students WHERE email = :email",
        ];

        foreach ($queries as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            if ($user) {
                $table = $user['role'];
                break;
            }
        }

        if ($user) {
            // Generate a unique token for password reset
            $token = bin2hex(random_bytes(50));

            // Store the token in the corresponding table
            $updateQuery = "UPDATE {$table} SET reset_token = :token WHERE email = :email";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([':token' => $token, ':email' => $email]);

            // Send the reset password email
            if (sendResetPasswordEmail($email, $token)) {
                $_SESSION['STATUS'] = "RESET_LINK_SENT";
                header("Location: ../index.php");
                exit();
            }
        } else {
            // Email not found in any table
            $_SESSION['STATUS'] = "EMAIL_NONE_EXISTENCE";
            header("Location: ../index.php");
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Please submit a valid email address.";
}

function sendResetPasswordEmail($email, $token)
{
    try {
        // PHPMailer settings
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mistyantelope@gmail.com'; // Your SMTP username
        $mail->Password = 'qgam kybv jwqn ahbh'; // Your SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ADDUccssms@gmail.com', 'ADDU - Student Management System');
        $mail->addAddress($email);

        // Reset link
        $resetLink = "http://localhost/login/processes/reset_password.php?token=$token";

        // Compose the email content
        $mail->isHTML(true);
        $mail->Subject = 'ADDU - Student Management System [Password Reset]';

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; text-align: center; padding: 20px; background-color: #f2f2f2; border-radius: 10px;'>
          
              
    
                <!-- Text -->
                <div style='font-size: 24px; color: #2c3e50; font-weight: bold; text-align: center;'>
                    Ateneo de Davao University <br> Student Management System
                </div>
    
      
    
            <!-- Main Content -->
            <h2>Password Reset Request</h2>
        <p>You requested a password reset for your <strong>ADDU - Student Management System</strong> account.</p>
        <p>Please click the link below to reset your password:</p>
        <p><a href='$resetLink' style='color: blue;'>Reset Password</a></p>
        <p>If you did not request this, you can ignore this email.</p>
        <p>Best regards,<br>ADDU Student Management System Team</p>
            <p style='font-size: 16px; color: #7f8c8d;'>If you have any issues, feel free to contact us at <a href='mailto:support@ADDU.edu.ph' style='color: #2980b9;'>support@ADDU.edu.ph</a></p>
    
            <div style='font-size: 14px; color: #7f8c8d;'>Best regards,<br>The ADDU Student Management System Team</div>
        </div>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    }
}
