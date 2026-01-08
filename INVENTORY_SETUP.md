# Inventory Feature Setup Instructions

This document explains how to set up and use the new inventory management feature for tracking replacement parts.

## Setup Steps

### 1. Create Database Tables

**✅ EASIEST METHOD - One-Click Setup:**

1. Navigate to `http://localhost/bucit/setup_inventory.php` in your browser
2. The script will automatically create all required tables
3. You'll see a success message when complete
4. Done! (You can delete setup_inventory.php afterwards if desired)

**Alternative Method - Manual SQL:**

If you prefer to run SQL manually:

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select the `bucit` database
3. Go to the SQL tab
4. Copy and paste the contents of `phpscripts/create_inventory_tables.sql`
5. Click "Go" to execute

Or from terminal with MySQL access:
```bash
mysql -u bucit -p bucit < phpscripts/create_inventory_tables.sql
```

### 2. Verify Installation

After running the SQL script, verify the tables were created:
- `inventory` - Stores replacement parts information
- `ticket_parts` - Junction table linking tickets to parts used

## Using the Inventory Feature

### Accessing Inventory Management

1. Log in as a technician
2. From the "Support Ticket Management" page, click the "Inventory" button in the header
3. You'll be taken to the Inventory Management page

### Adding Parts to Inventory

1. On the Inventory Management page, use the "Add New Part" form
2. Fill in:
   - **Part Name** (required): E.g., "LCD Screen 15.6 inch", "Battery - HP Model XYZ"
   - **Part Number/SKU** (optional): Manufacturer part number
   - **Quantity**: Initial stock quantity
   - **Notes** (optional): Any relevant information about the part
3. Click "Add Part"

### Managing Existing Parts

For each part in the inventory, you can:
- **+1 / -1**: Quick buttons to adjust quantity
- **Edit**: Modify part details (name, part number, notes, quantity)
- **Delete**: Remove part from inventory (only if not used in any tickets)

Color coding for quantities:
- **Green**: Good stock (6+ items)
- **Yellow**: Low stock (1-5 items)
- **Red**: Out of stock (0 items)

### Attaching Parts to Tickets

1. Open a ticket in edit mode (edit_ticket.php)
2. Scroll down to the "Replacement Parts" section
3. If parts are already attached, they'll be listed in a table
4. To attach new parts:
   - Check the boxes next to the parts you used
   - Adjust quantity if you used more than 1 of each part
   - Click "Attach Selected Parts"
5. The parts will be automatically deducted from inventory

Important notes:
- Only parts with available inventory (quantity > 0) are shown
- Parts already attached to the ticket appear grayed out and can't be selected again
- When you attach parts, inventory is immediately deducted using a database transaction for safety
- If there's insufficient inventory, you'll get an error message

## Database Structure

### inventory table
- `id`: Auto-increment primary key
- `part_name`: Name of the replacement part
- `part_number`: Optional part/model number
- `quantity`: Current stock quantity
- `notes`: Optional notes about the part
- `created_at`: When the part was added
- `updated_at`: Last modification timestamp

### ticket_parts table
- `id`: Auto-increment primary key
- `ticket_id`: Foreign key to tickets table
- `part_id`: Foreign key to inventory table
- `quantity_used`: How many units were used
- `added_at`: When the part was attached to the ticket
- `added_by`: Username of the technician who attached the part

## Features

✅ **Full CRUD operations** on inventory items
✅ **Quick quantity adjustments** (+1/-1 buttons)
✅ **Multi-select dropdown** for attaching parts to tickets
✅ **Automatic inventory deduction** when parts are used
✅ **Transaction safety** - prevents inventory issues if errors occur
✅ **Visual indicators** for low stock and out-of-stock items
✅ **Part usage history** per ticket
✅ **Duplicate prevention** - can't attach the same part twice to one ticket

## Troubleshooting

**"Table 'bucit.inventory' doesn't exist"**
- Run the SQL setup script from `phpscripts/create_inventory_tables.sql`

**"Cannot delete part that has been used in tickets"**
- This is intentional - parts with history can't be deleted
- Set quantity to 0 instead to mark as discontinued

**"Insufficient inventory for selected part"**
- Another technician may have used the last units
- Refresh the page to see current inventory
- Add more stock in the inventory management page

**Parts don't appear in the dropdown**
- Check that the part has quantity > 0
- Refresh the edit_ticket.php page
- Verify the part exists in the inventory table

## Future Enhancements (Optional)

- Add reporting for parts usage over time
- Email alerts when parts reach low stock thresholds
- Bulk import parts from CSV
- Part categories/types for better organization
- Cost tracking per part
- Vendor/supplier information
