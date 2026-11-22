# Local Installation & Setup Guide

This guide provides instructions for setting up the **Loan Management System** on a local development machine using XAMPP or the PHP built-in web server.

---

## 1. Prerequisites

- **PHP**: PHP 7.4, 8.0, 8.1, 8.2, or 8.3 with extensions enabled: `mysqli`, `pdo_mysql`, `mbstring`, `session`.
- **Database**: MySQL 5.7+ or MariaDB 10.4+.
- **Web Server**: Apache (via XAMPP / WAMP) or the PHP built-in CLI server.
- **Web Browser**: Microsoft Edge, Google Chrome, Firefox, or Safari.

---

## 2. Installation via XAMPP

### Step 1: Clone or Copy the Repository
Clone the repository or extract the project directory into your XAMPP `htdocs` folder:
```bash
cd C:\xampp\htdocs
git clone https://github.com/mnp210/Loan-Management-System.git
```
The folder path should be:
```
C:\xampp\htdocs\Loan-Management-System\
```

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Create & Import the Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin
   ```
2. Click **New** in the left sidebar and create a database named:
   ```
   loan_db
   ```
   *(Select `utf8mb4_general_ci` as collation).*
3. Select the newly created `loan_db` database, go to the **Import** tab.
4. Click **Choose File**, select `database/loan_db.sql` from the project directory, and click **Go**.
5. *(Optional / Recommended)*: Run `database/migration_security_and_cleanup.sql` in the **SQL** tab to apply optimized indexes.

### Step 4: Configure Database Connection
Check `db_connect.php` and verify the database connection parameters:
```php
$host = '127.0.0.1'; // or 'localhost'
$user = 'root';
$pass = '';          // default XAMPP root password is blank
$db   = 'loan_db';
```

### Step 5: Access the Application
Open your browser and navigate to:
```
http://localhost/Loan-Management-System/login.php
```

---

## 3. Quick Start via PHP CLI Server

You can also run the application directly from the command line using PHP's built-in web server:

```bash
# 1. Navigate to project root
cd D:\Projects\Loan_Management_System

# 2. Start MariaDB / MySQL on port 3306

# 3. Start PHP development server
php -S 127.0.0.1:8080
```

Open your browser and visit:
```
http://127.0.0.1:8080/login.php
```

---

## 4. Default Credentials

The database dump includes an initial administrative account:

| Username | Password | Role |
| :--- | :--- | :--- |
| `admin` | `admin123` | Administrator |

> [!NOTE]
> Upon your first successful login, the system automatically migrates the initial password into a modern, secure **Bcrypt** hash. It is recommended to change this password immediately from the **Users** menu.

---

## 5. Running the Automated Test Suite

The repository includes automated verification suites located in the `tests/` directory:

```bash
# Execute full syntax, security, math & regression test suite
php tests/run_tests.php

# Execute workflow & status transition test suite
php tests/action_integration_test.php
```
Both test suites should return **100% Pass Rate**.
