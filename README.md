# bucIT
The BucIT program at my former high school was a senior-staffed team of technicians that would be available for various types of student-laptop repair. The legacy/initial organization system to facilitate this program was through students needing laptop repair being asked to fill out a physical ticket on paper, where then they would wedge it inside the laptop's closed lid. Once the technician receives the laptop, they would need to make out the student's handwriting to read the problem, and then once fixed, reading their name and email. After several repairs were completed, a technician would manually read the emails on each ticket and send emails to each student, notifying them that their device was ready for pickup. This system was rather clunky and disjointed.<br><br>
I developed ```bucit```, **which is an all-in-one, web-based IT help desk and service management system designed to streamline technical support for users and staff across a university or organizational ecosystem. Its core purpose is to bridge the gap between end users needing technical help, with technicians managing high volumes of service requests.** 
This served as a much more streamlined solution able to automatically assign device repairs to technicians, manage inventory of parts, automatically notify a student upon repair completion, and many more features.

**[Try the live demo](https://aidanlenahan.github.io/bucit/)**: the full student site, kiosk and technician dashboard, running in your browser with fictional data. Log in as `arivera` / `demo` (technician) or `admin` / `demo` (admin).

### **Primary Objectives & Core Purpose**

* **Streamlining Incident Reporting:** Provides dedicated self-service tools (`support.html`, `form.html`, `kiosk.php`) that allow users to quickly report issues, log service requests, and track ticket status in real time.
* **Promoting Self-Service & Deflection:** Features a integrated knowledge base of help articles covering common technical issues—such as login errors, network connectivity, system restarts, and display or hardware failures—to help users resolve routine problems independently before submitting a ticket.
* **Centralizing IT Workflows:** Equips technicians and system administrators with a unified dashboard (`admin_tech.php`, `manage_tickets.php`, `edit_ticket.php`, `close_ticket.php`) to prioritize, assign, update, and resolve incoming support requests efficiently.
* **Managing Hardware & Resources:** Integrates hardware and software asset tracking (`inventory.php`) with the ticketing flow so technicians can monitor equipment usage, track issue history by device, and maintain IT inventory.
* **Automating Communication:** Utilizes background services like PHPmailer (`email_config`) to automatically update users on ticket progress, staff assignments, and resolution status via email.

### **Live Demo (GitHub Pages)**

The production app is PHP + MySQL, so it can't run on GitHub Pages as-is. The [`docs/`](docs/) folder is a static port of every page:

* The public pages, articles and chatbot are copied as-is.
* Each PHP page (`status.php`, `kiosk.php`, everything under `tech/`) is rebuilt as HTML + JavaScript with the same markup, styles, validation and permission rules.
* `docs/js/demo.js` stands in for the server: a `localStorage` database, technician sessions, and an **email outbox** that shows the PHPMailer emails instead of sending them. "Reset demo data" in the top bar restores the starting data.

To publish it, go to **Settings → Pages**, set the source to *Deploy from a branch*, then pick `main` and `/docs`.

### **Running the original on WAMP/LAMP**

1. Import the schema and demo data: `mysql -u root < sql/bucit.sql` (or import `sql/bucit.sql` in phpMyAdmin). This creates a `bucit` database.
2. Copy `includes/db_config.example.php` to `includes/db_config.php` and `includes/email_config.example.php` to `includes/email_config.php`, then fill in your credentials (WAMP's default is `root` with no password).
3. Put the repo in your `www` folder and open `http://localhost/bucit/`. The technician dashboard is at `/tech/`.

Every technician password is `demo`. The admin account is `jmilonas`.

### **Regenerating the demo data**

`node scripts/generate-demo-data.mjs` rebuilds both `sql/bucit.sql` and `docs/js/seed-data.js` from one deterministic, fictional dataset, so the demo and the PHP version always start from the same tickets, parts and accounts.
