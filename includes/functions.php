<?php
/**
 * Email Functions
 * Contains functions for sending emails using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer via autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load email configuration
require_once __DIR__ . '/email_config.php';

/**
 * Send email notification when a ticket is closed
 * 
 * @param string $email Recipient's email address
 * @param string $firstName Recipient's first name
 * @param string $lastName Recipient's last name
 * @param array $ticketData Ticket information (ticketNumber, status, resolution, etc.)
 * @return bool True if email sent successfully, false otherwise
 */
function sendTicketClosedEmail($email, $firstName, $lastName, $ticketData) {
    // Create new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Configure SMTP settings using constants
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Set sender & recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, "$firstName $lastName");
        
        // Extract ticket data
        $ticketNumber = isset($ticketData['ticketNumber']) ? $ticketData['ticketNumber'] : 'N/A';
        $status = isset($ticketData['status']) ? $ticketData['status'] : 'Closed';
        $resolution = isset($ticketData['resolution']) ? $ticketData['resolution'] : 'Issue resolved';
        $closedDate = isset($ticketData['closedDate']) ? $ticketData['closedDate'] : date('Y-m-d H:i:s');
        
        // Build HTML email
        $mail->isHTML(true);
        $mail->Subject = "Support Ticket #$ticketNumber - Closed";
        $mail->Body    = "
        <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #667eea;'>Your Support Ticket Has Been Closed</h2>
                    <p>Hello <strong>$firstName $lastName</strong>,</p>
                    <p>Your support ticket has been successfully resolved and closed.</p>
                    
                    <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Ticket Number:</strong> #$ticketNumber</p>
                        <p style='margin: 5px 0;'><strong>Status:</strong> $status</p>
                        <p style='margin: 5px 0;'><strong>Closed Date:</strong> $closedDate</p>
                        <p style='margin: 5px 0;'><strong>Resolution:</strong></p>
                        <p style='margin: 5px 0 5px 20px;'>$resolution</p>
                    </div>
                    
                    <p>If you have any additional questions or concerns, please feel free to submit a new ticket.</p>
                    
                    <p style='margin-top: 30px; color: #666; font-size: 12px;'>
                        Thank you for using BUCIT Support<br>
                        This is an automated message, please do not reply to this email.
                    </p>
                </div>
            </body>
        </html>
        ";
        
        // Plain text alternative
        $mail->AltBody = "Hello $firstName $lastName,\n\n" .
                        "Your support ticket has been successfully resolved and closed.\n\n" .
                        "Ticket Number: #$ticketNumber\n" .
                        "Status: $status\n" .
                        "Closed Date: $closedDate\n" .
                        "Resolution: $resolution\n\n" .
                        "If you have any additional questions or concerns, please feel free to submit a new ticket.\n\n" .
                        "Thank you for using BUCIT Support";
        
        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send a test email to verify configuration
 * 
 * @param string $email Recipient's email address
 * @return bool True if email sent successfully, false otherwise
 */
function sendTestEmail($email) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'BUCIT Email Configuration Test';
        $mail->Body    = '<h2>Test Email</h2><p>Email configuration is working correctly!</p>';
        $mail->AltBody = 'Test Email - Email configuration is working correctly!';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Test email failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
