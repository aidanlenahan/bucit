# Email Integration Guide

## ✅ Setup Complete!

Your email functionality has been fully configured and integrated into the BUCIT support ticket system.

## Files Created

### Core Email System
- **vendor/autoload.php** - Autoloader for PHPMailer library
- **includes/email_config.php** - SMTP credentials (f3fff0@gmail.com)
- **includes/functions.php** - Email sending functions

### User-Facing Scripts
- **close_ticket.php** - Dedicated ticket closing script with email notification
- **test_email.php** - Test script to verify email configuration

### Modified Files
- **edit_ticket.php** - Added "Close Ticket & Send Email" button

## How to Use

### Method 1: Using the Close Ticket Button (Recommended)

1. Navigate to any ticket in `edit_ticket.php`
2. Click the green "✓ Close Ticket & Send Email" button
3. Fill in the resolution notes explaining how the issue was fixed
4. Check/uncheck "Send email notification to student" as needed
5. Click "Close Ticket & Send Email"

The system will:
- Update ticket status to "Closed"
- Set priority to 5 (to keep closed tickets out of active priority sorting)
- Assign the ticket to the current technician
- Send a professional HTML email to the student with:
  - Ticket number
  - Resolution notes
  - Closure date
  - Problem details

### Method 2: Programmatic Integration

If you want to integrate email notifications into other scripts:

```php
<?php
// Include the email functions
require_once 'includes/functions.php';

// Prepare ticket data
$ticketData = [
    'ticketNumber' => '12345',
    'status' => 'Closed',
    'resolution' => 'Replaced faulty power adapter. Device now charging properly.',
    'closedDate' => date('Y-m-d H:i:s'),
    'problemCategory' => 'Battery',
    'problemDetail' => 'Not charging'
];

// Send the email
$result = sendTicketClosedEmail(
    'student@example.edu',  // Student's email
    'John',                  // First name
    'Doe',                   // Last name
    $ticketData             // Ticket information
);

if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email (check error logs)";
}
?>
```

## Testing

Visit: `http://localhost/bucit/test_email.php`

This will send two test emails:
1. A simple test message
2. A sample ticket closure notification

## Email Template

The email sent to students includes:
- **Subject:** "Support Ticket #[number] - Closed"
- **Body:** Professional HTML template with:
  - Personalized greeting
  - Ticket details (number, status, date)
  - Resolution notes
  - Encouragement to submit new tickets if needed
- **Plain text alternative** for email clients that don't support HTML

## Configuration

To modify email settings, edit **includes/email_config.php**:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'f3fff0@gmail.com');
define('SMTP_PASSWORD', 'ryge ebal lzyh wvxo');
define('SMTP_FROM_EMAIL', 'f3fff0@gmail.com');
define('SMTP_FROM_NAME', 'BUCIT Support');
```

## Available Functions

### sendTicketClosedEmail()
Sends a ticket closure notification with resolution details.

**Parameters:**
- `$email` (string) - Recipient's email address
- `$firstName` (string) - Recipient's first name
- `$lastName` (string) - Recipient's last name  
- `$ticketData` (array) - Ticket information:
  - `ticketNumber` - Ticket ID
  - `status` - Ticket status (usually "Closed")
  - `resolution` - Description of how issue was resolved
  - `closedDate` - Closure timestamp
  - `problemCategory` - (optional) Problem category
  - `problemDetail` - (optional) Problem details

**Returns:** `true` on success, `false` on failure

### sendTestEmail()
Sends a simple test email to verify SMTP configuration.

**Parameters:**
- `$email` (string) - Recipient's email address

**Returns:** `true` on success, `false` on failure

## Security Notes

⚠️ **Important:**
- The email configuration contains sensitive credentials
- Consider adding `includes/email_config.php` to `.gitignore`
- Use environment variables for production deployments
- The current credentials are Gmail app-specific passwords (more secure than account password)

## Troubleshooting

If emails aren't sending:

1. **Check PHP error logs** - Email errors are logged via `error_log()`
2. **Verify SMTP credentials** - Ensure app password is correct
3. **Test with test_email.php** - Isolate configuration issues
4. **Check Gmail security settings** - Ensure "Less secure app access" isn't blocking
5. **Verify recipient email** - Ensure `school_email` field is populated in database

## Future Enhancements

Possible additions:
- Email templates for different ticket actions (created, assigned, etc.)
- Email queue for bulk sending
- Email logs/history in database
- Customizable email templates via admin panel
- CC technicians on closure emails
- Attachments (screenshots, receipts, etc.)

---

**Status:** ✅ Fully Operational  
**Last Updated:** January 14, 2026
