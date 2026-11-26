# Goods Tracking and Transportation Management System
### A Case Study of Funtua Garage, Samaru, Zaria

A complete, database-driven web application designed to computerize and manage goods registration, documentation, tracking, and transportation at **Funtua Garage, Samaru, Zaria**.

---

## 1. System Overview & Key Features

- **Digital Consignment Registration:** Computerized registration replacing paper waybills. Captures sender, receiver, goods details, handling instructions, and vehicle/driver assignments.
- **Automated Tracking & Waybill Generation:** Unique tracking codes in the format `FGSZ-YYYY-XXXXXX` and waybill numbers in the format `WB-YYYY-XXXXXX`.
- **Official Digital Waybill Generation:** Official printable waybill documentation with CSS `@media print` styling, verification watermarks, and administrative sign-off seals.
- **Status-Based Consignment Tracking:** Public-facing and administrative tracking with a modern 5-step visual progress stepper:
  $$\text{Registered} \longrightarrow \text{Dispatched} \longrightarrow \text{In Transit} \longrightarrow \text{Arrived} \longrightarrow \text{Delivered}$$
- **Delivery Status Management:** Multi-stop audit log recording timestamped updates, checkpoints, and administrator actions without overwriting previous history.
- **Delivery Confirmation Module:** Formal delivery receipt verification recording the recipient's name, timestamp, and notes while preventing duplicate delivery claims.
- **Fleet & Driver Management:** Driver rosters and haulage vehicle registries linked optionally to consignments.
- **Administrative Reporting:** Dynamic filtering by status, destination, and date range with live metric calculations and printable report summaries.
- **iziToast Interactive Notifications:** Integrated notification feedback and confirmation modals for all sensitive actions and form submissions.

---

## 2. Technology Stack

- **HTML5 & CSS3:** Semantic markup, responsive flexbox/grid layout, and print stylesheets.
- **PHP (8.x):** Native server-side business logic, session-based authentication, CSRF validation, and input sanitization.
- **MySQL / MariaDB (InnoDB):** Relational schema with foreign keys, indexes, and transactional guarantees.
- **iziToast:** Professional toast notification and modal confirmation library (CDN + local fallback).

---

## 3. Project Structure

```text
vehicle/ (or goods-tracking-system/)
│
├── index.php                         # System router and entry point
├── login.php                         # Administrator authentication
├── logout.php                        # Session destruction & sign out
├── dashboard.php                     # Central operations dashboard & live stats
│
├── database/
│   └── goods_tracking_system.sql     # Complete schema & seed data
│
├── config/
│   └── database.php                  # PDO connection & dynamic base URL resolver
│
├── includes/
│   ├── auth.php                      # Authentication guard & profile helpers
│   ├── functions.php                 # Tracking/waybill generators, sanitizers, badges
│   ├── header.php                    # App shell header & CSS imports
│   ├── sidebar.php                   # Navigation menu & responsive drawer
│   └── footer.php                    # iziToast configuration & session flash dispatcher
│
├── consignments/
│   ├── register.php                  # Consignment registration form
│   ├── list.php                      # Consignments ledger with search & pagination
│   ├── view.php                      # Consignment details & timeline
│   ├── edit.php                      # Record modifier
│   ├── search.php                    # Advanced query lookup
│   └── delete.php                    # Secure deletion handler
│
├── waybills/
│   ├── generate.php                  # Waybill selector & search
│   └── view.php                      # Official printable digital waybill
│
├── tracking/
│   ├── index.php                     # Public tracking portal
│   └── result.php                    # Milestone stepper & timeline history
│
├── delivery/
│   ├── update.php                    # Milestone status transition manager
│   └── confirm.php                   # Official delivery receipt & closing
│
├── drivers/
│   └── index.php                     # Driver roster management
│
├── vehicles/
│   └── index.php                     # Vehicle fleet management
│
├── reports/
│   └── index.php                     # Statistical reporting & audit logs
│
├── tests/
│   └── verify.php                    # Automated verification test suite
│
└── assets/
    ├── css/
    │   ├── style.css                 # Main application & print stylesheet
    │   └── iziToast.min.css          # Local notification styling
    └── js/
        └── iziToast.min.js           # Local notification script
```

---

## 4. Local Installation on XAMPP

1. **Start XAMPP Services:**
   - Launch the **XAMPP Control Panel**.
   - Start both **Apache** and **MySQL**.

2. **Database Setup:**
   - Open phpMyAdmin in your browser: `http://localhost/phpmyadmin/`.
   - Click on the **Import** tab.
   - Select the SQL file located at:
     ```
     c:\xampp\htdocs\vehicle\database\goods_tracking_system.sql
     ```
   - Click **Import** (or **Go**) at the bottom of the page.
   - Alternatively, if MySQL is running, import via command line:
     ```powershell
     Get-Content "C:\xampp\htdocs\vehicle\database\goods_tracking_system.sql" | mysql -u root
     ```

3. **Access the Application:**
   - Open your browser and navigate to:
     `http://localhost/vehicle/`
     *(or `http://localhost/goods-tracking-system/`)*

4. **Default Administrator Credentials:**
   - **Username:** `admin`
   - **Password:** `admin`

5. **Customer Consignment Tracking Portal:**
   - Customers and clients can track goods without logging in at:
     `http://localhost/vehicle/tracking/index.php`
   - Try tracking seed consignments: `FGSZ-2026-000001`, `FGSZ-2026-000002`, `FGSZ-2026-000003`, `FGSZ-2026-000004`, or `FGSZ-2026-000005`.
