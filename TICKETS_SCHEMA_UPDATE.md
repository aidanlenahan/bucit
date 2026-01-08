# Tickets Table Update - Replace Phone with Email and Additional Info

## Changes Made

### Database Schema Updates
- **Removed:** `phone` column (VARCHAR)
- **Added:** `school_email` column (VARCHAR 255) - Email address validation required
- **Added:** `additional_info` column (VARCHAR 120) - Optional text field

### Files Updated

1. **form.html** (Intake Form)
   - Removed phone field
   - Added school email field with email validation
   - Added additional information textarea (120 character limit)

2. **support_ticket.php** (Form Submission Handler)
   - Updated to collect school_email and additional_info
   - Updated SQL INSERT statement to include new columns
   - Updated bind_param to match new column structure

3. **manage_tickets.php** (Ticket Dashboard)
   - Updated SELECT query to include school_email and additional_info columns

4. **edit_ticket.php** (Ticket Editor)
   - Updated POST data collection for new fields
   - Updated SQL UPDATE statement
   - Updated ticket details display section
   - Updated edit form fields
   - Added additional_info display when present

### Setup Instructions

**Run the database update:**

1. **Easy method:** Navigate to `http://localhost/bucit/update_tickets_schema.php`
   - The script will automatically update the table structure
   - You'll see success/error messages

2. **Manual method:** Run the SQL in `phpscripts/update_tickets_columns.sql` via phpMyAdmin

### Form Field Details

**School Email:**
- Type: Email input with HTML5 validation
- Required: Yes
- Validation: Standard email format (example@domain.com)
- Placeholder: "yourname@school.edu"

**Additional Information:**
- Type: Textarea
- Required: No
- Max Length: 120 characters
- Rows: 3
- Purpose: Allow students to provide extra context about their issue

### Migration Notes

- Existing tickets will have NULL values for school_email and additional_info
- The phone column will be completely removed from the database
- This is a breaking change - ensure all references to the phone field are removed

### Testing Checklist

- [ ] Submit a new ticket with school email
- [ ] Verify email validation works
- [ ] Submit ticket with additional info
- [ ] Submit ticket without additional info (should work)
- [ ] View ticket in manage_tickets.php
- [ ] Edit ticket in edit_ticket.php
- [ ] Verify new fields display correctly
- [ ] Ensure no PHP errors related to missing 'phone' column
