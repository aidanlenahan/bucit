<?php
/**
 * Test Email Configuration
 * Run this file to test if the email setup is working correctly
 */

require_once 'includes/functions.php';

// Test 1: Send a simple test email
echo "<h2>Testing Email Configuration</h2>";

$testEmail = 'aidanlenahan@gmail.com'; // Change this to your test email
echo "<p>Sending test email to: $testEmail</p>";

$testResult = sendTestEmail($testEmail);

if ($testResult) {
    echo "<p style='color: green;'>✓ Test email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to send test email. Check error logs.</p>";
}

echo "<hr>";

// Test 2: Send a ticket closed notification
echo "<h3>Testing Ticket Closed Notification</h3>";

$ticketData = [
    'ticketNumber' => '12345',
    'status' => 'Closed',
    'resolution' => 'The laptop charging issue has been resolved by replacing the power adapter.',
    'closedDate' => date('Y-m-d H:i:s')
];

$ticketResult = sendTicketClosedEmail($testEmail, 'Test', 'User', $ticketData);

if ($ticketResult) {
    echo "<p style='color: green;'>✓ Ticket closed notification sent successfully!</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to send ticket notification. Check error logs.</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> Check the inbox of $testEmail for the test emails.</p>";
?>
