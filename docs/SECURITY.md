# Application Security & Hardening Guide

## 1. Security Overview

The **Loan Management System** has been engineered to comply with modern web application security practices, mitigating vulnerabilities across the **OWASP Top 10** standard.

---

## 2. Threat Mitigation Matrix

| Vulnerability Category | Original Risk | Mitigation Implemented |
| :--- | :--- | :--- |
| **A01: Broken Access Control** | High — Unauthenticated AJAX endpoints permitted direct database modifications. | Implemented centralized authentication guards (`require_auth()`) and Role-Based Access Control (`require_admin()`) in `ajax.php` and view files. |
| **A02: Cryptographic Failures** | Critical — Administrative passwords stored in plaintext (`admin123`) or legacy MD5. | Migrated to standard PHP `password_hash()` (Bcrypt). Built an automatic backward-compatible rehash mechanism in `admin_class.php`. |
| **A03: Injection (SQLi)** | Critical — Unsanitized string concatenation in queries across all modules. | Migrated all database operations to parameterized MySQLi prepared statements (`prepare()` and `bind_param()`). |
| **A03: Local File Inclusion (LFI)**| Critical — Dynamic page loader evaluated unsanitized `$_GET['page']`. | Implemented strict array whitelisting in `index.php`. Path traversal sequences (`../`) automatically fall back to `home.php`. |
| **A04: Insecure Design (CSRF)** | Medium — State-changing requests lacked origin validation. | Implemented cryptographic CSRF token generation (`csrf_token()`) and header/POST validation (`validate_csrf()`) across all actions. |
| **A07: Identification Failures** | Medium — Session state lacked strict regeneration upon authentication. | Implemented session-backed permission verification, preventing session leakage. |
| **A03: Cross-Site Scripting (XSS)**| High — Borrower names, addresses, and user-supplied text rendered raw into HTML. | Applied context-aware HTML entity escaping (`escape()` wrapping `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`). |

---

## 3. Core Security Modules

### A. CSRF Token Engine (`security.php`)
```php
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
```
Every mutation request transmitted via jQuery automatically supplies the CSRF token via `$.ajaxSetup`:
```javascript
$.ajaxSetup({
    headers: {
        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
});
```

### B. Strict Page Whitelisting (`index.php`)
```php
$allowed_pages = [
    'home',
    'borrowers',
    'loans',
    'payments',
    'plan',
    'loan_type',
    'users'
];
$page = isset($_GET['page']) ? trim($_GET['page']) : 'home';
if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}
// RBAC: Users page is restricted to Admin only (login_type == 1)
if ($page === 'users' && ($_SESSION['login_type'] ?? null) != 1) {
    $page = 'home';
}
include $page . '.php';
```

### C. Prepared MySQLi Statements (`admin_class.php`)
Example of safe parameterization:
```php
$stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
```

---

## 4. Production Security Recommendations

1. **HTTPS Enforcement**: Always deploy with SSL/TLS enabled to encrypt credentials and session cookies in transit.
2. **Database Permissions**: Use a dedicated MySQL user account for the application with `SELECT`, `INSERT`, `UPDATE`, `DELETE` privileges only (avoid using `root`).
3. **Session Cookie Security**: Set `session.cookie_httponly = 1`, `session.cookie_secure = 1`, and `session.use_strict_mode = 1` in `php.ini`.
4. **Environment Isolation**: Keep debug flags (`display_errors`) disabled in production environments (`ini_set('display_errors', 0)`).
