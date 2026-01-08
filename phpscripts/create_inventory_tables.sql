-- Create inventory table for replacement parts
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_name VARCHAR(255) NOT NULL,
    part_number VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create ticket_parts junction table to track which parts are used for which tickets
CREATE TABLE IF NOT EXISTS ticket_parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity_used INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by VARCHAR(100),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES inventory(id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_ticket_parts_ticket ON ticket_parts(ticket_id);
CREATE INDEX idx_ticket_parts_part ON ticket_parts(part_id);
