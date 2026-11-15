# 🛡️ ResolveX - Online Complaint Management System (OCMS)

A web-based **Online Complaint Management System** developed with **PHP**, **MySQL (PDO)**, **HTML5**, **CSS3**, and **Bootstrap 5**. Designed for responsive user interaction, complete CRUD lifecycle management, and seamless deployment on both **XAMPP (Local)** and **InfinityFree (Cloud Hosting)**.

---

## 📋 Table of Contents
1. [System Features & Architecture](#-system-features--architecture)
2. [Database Design & Schema (Requirement C)](#-database-design--schema-requirement-c)
3. [Front-End Design & Bootstrap 5 (Requirement A)](#-front-end-design--bootstrap-5-requirement-a)
4. [Back-End PHP & MySQL CRUD Operations (Requirement B)](#-back-end-php--mysql-crud-operations-requirement-b)
5. [Local Development Setup with XAMPP](#-local-development-setup-with-xampp)
6. [InfinityFree Hosting Deployment Guide](#-infinityfree-hosting-deployment-guide)
7. [Testing & Verification Checklist](#-testing--verification-checklist)

---

## 🚀 System Features & Architecture

```
ocmsproj/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Custom CSS enhancements & status badges
│   └── js/
│       └── main.js              # Form validation, copy-to-clipboard, modals
│
├── config/
│   └── database.php             # Unified PDO connection (XAMPP & InfinityFree)
│
├── database/
│   └── schema.sql               # MySQL database schema with seed data
│
├── includes/
│   ├── header.php               # Responsive Navbar, metadata, Bootstrap CDN
│   ├── footer.php               # Global footer & JS scripts
│   └── alerts.php               # Session flash message feedback component
│
├── index.php                    # Public landing page, stats & quick tracking
├── submit-complaint.php         # CREATE (Insert) - File new complaint form
├── track-complaint.php          # READ (Select) - Public tracker & visual stepper
├── manage-complaints.php        # READ & Dashboard - Search, filter, action list
├── view-complaint.php           # READ (Detail) - Single record printable view
├── edit-complaint.php           # UPDATE - Modify status, priority & admin remarks
├── delete-complaint.php         # DELETE - Safe record removal via POST
├── export.php                   # Export records to CSV spreadsheet
├── .htaccess                    # Security rules & index configuration
└── README.md                    # Documentation & deployment guide
```

---

## 🗄️ Database Design & Schema (Requirement C)

The database `ocms_db` is normalized with proper primary keys, data types, indexes, and automatic timestamp tracking.

### Table: `complaints` (13 Fields)

| Field Name | Data Type | Nullable | Default | Description / Constraints |
|---|---|---|---|---|
| `id` | `INT` | NO | Auto Increment | **PRIMARY KEY** (Unique identifier) |
| `tracking_code` | `VARCHAR(25)` | NO | None | **UNIQUE INDEX** (e.g. `CMP-2026-1001`) |
| `complainant_name`| `VARCHAR(100)`| NO | None | Full legal or registered name |
| `email` | `VARCHAR(120)`| NO | None | Indexed for fast lookups |
| `phone` | `VARCHAR(25)` | NO | None | Contact telephone/mobile |
| `category` | `VARCHAR(100)`| NO | None | Complaint classification |
| `priority` | `ENUM` | NO | `'Medium'` | `'Low'`, `'Medium'`, `'High'`, `'Urgent'` |
| `subject` | `VARCHAR(200)`| NO | None | Concise summary title |
| `description` | `TEXT` | NO | None | Detailed grievance narrative |
| `status` | `ENUM` | NO | `'Pending'`| `'Pending'`, `'In Progress'`, `'Resolved'`, `'Closed'` |
| `admin_remarks` | `TEXT` | YES | `NULL` | Department investigation & resolution notes |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | Submission timestamp |
| `updated_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Last modified timestamp |

### Table: `categories` (Reference Table)
- `id` (`INT AUTO_INCREMENT PRIMARY KEY`)
- `name` (`VARCHAR(100) UNIQUE`)
- `description` (`VARCHAR(255)`)
- `created_at` (`TIMESTAMP`)

---

## 🎨 Front-End Design & Bootstrap 5 (Requirement A)

- **Responsive Navigation Bar**: Collapsible on mobile/tablets with brand icon, active page highlighting, and direct CTA buttons.
- **Interactive Forms**: Styled with Bootstrap 5 floating layouts, client-side `.needs-validation` feedback, radio urgency selectors, and sanitized inputs.
- **Color-Coded Status & Priority Badges**:
  - `Pending`: Amber badge (`badge-pending`)
  - `In Progress`: Cyan badge (`badge-in-progress`)
  - `Resolved`: Emerald green badge (`badge-resolved`)
  - `Closed`: Slate grey badge (`badge-closed`)
  - Priority levels from green (Low) to crimson (Urgent).
- **Interactive Visual Timeline**: 4-stage tracking stepper (Submitted ➔ In Review ➔ Resolved ➔ Closed) on `track-complaint.php`.
- **Reusable Confirmation Modals**: Dynamic Bootstrap 5 modal preventing accidental record deletions.
- **Printer-Friendly View**: Clean CSS media query hiding navigation elements for instant PDF printing or physical filing.

---

## ⚙️ Back-End PHP & MySQL CRUD Operations (Requirement B)

All database operations use **PHP Data Objects (PDO)** with prepared statements to guarantee full protection against SQL Injection vulnerabilities.

### 1. Create (Insert)
- **File**: `submit-complaint.php`
- **Logic**:
  ```php
  $stmt = $pdo->prepare("INSERT INTO complaints 
      (tracking_code, complainant_name, email, phone, category, priority, subject, description, status) 
      VALUES (:tracking_code, :name, :email, :phone, :category, :priority, :subject, :description, 'Pending')");
  $stmt->execute([...]);
  ```
- Generates unique tracking reference (e.g. `CMP-2026-XXXX`).

### 2. Read (Select)
- **Files**: `index.php`, `track-complaint.php`, `manage-complaints.php`, `view-complaint.php`
- **Features**:
  - Multi-condition search (by tracking code, name, email, subject).
  - Status and category filtering.
  - Aggregation metrics (`COUNT(*)`, `SUM(CASE WHEN status ...)`).

### 3. Update (Edit)
- **File**: `edit-complaint.php`
- **Logic**:
  ```php
  $updateStmt = $pdo->prepare("UPDATE complaints SET 
      complainant_name = :name, email = :email, phone = :phone,
      category = :category, priority = :priority, subject = :subject,
      description = :description, status = :status, admin_remarks = :remarks
      WHERE id = :id");
  ```
- Updates incident status, reassigns priorities, and logs official administrative remarks.

### 4. Delete
- **File**: `delete-complaint.php`
- **Logic**:
  ```php
  $deleteStmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id");
  $deleteStmt->execute([':id' => $id]);
  ```
- Restricted to HTTP POST requests with modal confirmation to prevent CSRF attacks.

---

## 💻 Local Development Setup with XAMPP

### Step 1: Start XAMPP Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 2: Copy Project to XAMPP `htdocs`
Copy this project folder into your XAMPP web root:
- Source: `C:\ocmsproj`
- Destination: `C:\xampp\htdocs\ocms`

*(You can simply copy the contents or create a shortcut/symlink).*

### Step 3: Import the Database
1. Open your browser and navigate to `http://localhost/phpmyadmin`.
2. Click on the **Import** tab at the top.
3. Click **Choose File** and browse to `C:\xampp\htdocs\ocms\database\schema.sql`.
4. Click **Go** at the bottom.
5. The `ocms_db` database and tables (`complaints`, `categories`) will be created and populated with sample records.

### Step 4: Run the Application
Open your browser and visit:
👉 **`http://localhost/ocms`**

---

## 🌐 InfinityFree Hosting Deployment Guide

InfinityFree is a popular free PHP/MySQL web hosting platform. Follow these step-by-step instructions to deploy ResolveX online:

### Step 1: Create an Account on InfinityFree
1. Go to [https://www.infinityfree.com](https://www.infinityfree.com) and sign up for a free account.
2. In the dashboard, click **Create Account**.
3. Choose a domain or free subdomain (e.g., `resolvex-portal.infinityfreeapp.com`).
4. Complete the creation and note your account username (e.g., `epiz_12345678`).

### Step 2: Create the MySQL Database on InfinityFree
1. In your InfinityFree dashboard, open the **Control Panel (vPanel)**.
2. Scroll to the **Databases** section and click **MySQL Databases**.
3. Under **Create New Database**, type `ocms_db` and click **Create Database**.
4. InfinityFree will create a database named like: `epiz_12345678_ocms_db`.
5. Take note of the **MySQL Connection Details** displayed on the page:
   - **MySQL Host**: (e.g. `sql302.infinityfree.com`)
   - **Database Name**: (e.g. `epiz_12345678_ocms_db`)
   - **MySQL Username**: (e.g. `epiz_12345678`)
   - **MySQL Password**: (Your InfinityFree account/vPanel password)

### Step 3: Import the SQL Schema on InfinityFree
1. In the InfinityFree Control Panel, click **phpMyAdmin**.
2. Click **Connect** next to your newly created database `epiz_12345678_ocms_db`.
3. Inside phpMyAdmin, click the **Import** tab.
4. Select `database/schema.sql` from your computer.
5. *(Note: Remove the `CREATE DATABASE` line if InfinityFree already selected your database, or just import the table definitions).*
6. Click **Go** to execute the SQL import.

### Step 4: Configure Database Credentials
Open `config/database.php` in your code editor and update the InfinityFree section:

```php
// config/database.php
define('DB_HOST', 'sql302.infinityfree.com'); // Your InfinityFree MySQL Host
define('DB_PORT', '3306');
define('DB_NAME', 'epiz_12345678_ocms_db');    // Your InfinityFree Database Name
define('DB_USER', 'epiz_12345678');            // Your InfinityFree MySQL Username
define('DB_PASS', 'YourVPanelPasswordHere');   // Your InfinityFree Password
```

### Step 5: Upload Project Files to InfinityFree
You can upload your files using either the **Monsta File Manager** (in browser) or **FileZilla (FTP)**:

#### Method A: Online File Manager
1. In the InfinityFree Control Panel, click **Online File Manager**.
2. Navigate into the **`htdocs`** folder.
3. Delete any default `index2.html` placeholder file.
4. Upload all the files and folders from your project (`assets/`, `config/`, `database/`, `includes/`, `index.php`, `submit-complaint.php`, `track-complaint.php`, `manage-complaints.php`, `view-complaint.php`, `edit-complaint.php`, `delete-complaint.php`, `export.php`).

#### Method B: FileZilla (FTP)
1. Open FileZilla.
2. Host: `ftpupload.net` (or the FTP host shown in your InfinityFree dashboard).
3. Username: Your `epiz_12345678`.
4. Password: Your vPanel password.
5. Port: `21`.
6. Connect, go into `/htdocs`, and transfer all project files into it.

### Step 6: Test Your Live System
Visit your website URL:
👉 **`http://your-subdomain.infinityfreeapp.com`**

---

## ✅ Testing & Verification Checklist

- [x] **Create**: Submit a complaint at `submit-complaint.php` and verify the generated tracking code.
- [x] **Read**: Track the code on `track-complaint.php` and view the 4-stage stepper.
- [x] **Read & Filter**: Open `manage-complaints.php`, search by keyword and filter by category or status.
- [x] **Update**: Edit an issue at `edit-complaint.php`, change the status to "In Progress" or "Resolved", and add remarks.
- [x] **Delete**: Click the trash icon in `manage-complaints.php` and verify deletion confirmation modal and database record removal.
- [x] **CSV Export**: Click "Export CSV" to download all records into Microsoft Excel.
- [x] **Responsive Layout**: Tested on mobile, tablet, and desktop breakpoints via Bootstrap 5 grid.
