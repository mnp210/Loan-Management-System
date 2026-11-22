# Academic Project History & Engineering Evolution

## 1. Academic Background

This project originated as a **Diploma in Computer Engineering** final-year capstone project developed by **`mnp210`**.

### Original Objectives:
- Automate manual paperwork and ledger accounting for microfinance organizations and personal loan operations.
- Build an accessible web portal for managing borrower records, loan plans, interest calculations, and repayment schedules.
- Provide administrative reporting on active portfolios, daily collections, and overdue accounts.

---

## 2. Original Implementation & Academic Context

The initial submission fulfilled diploma degree requirements by demonstrating:
- Relational database schema design in MySQL.
- Server-side scripting using procedural and object-oriented PHP.
- Client-side interactivity using HTML5, CSS3, Bootstrap 4, and jQuery AJAX.
- Core financial calculation algorithms for flat-rate interest and monthly amortization schedules.

As with many academic prototypes created under academic timelines, the original implementation prioritized proof-of-concept functionality over production-grade security, enterprise resilience, and modern coding standards.

---

## 3. Engineering Evolution & Modernization

To prepare this academic project for a professional software engineering portfolio, a comprehensive three-phase engineering overhaul was executed while strictly preserving the project's original academic identity and architecture.

### Comparative Evolution Matrix

| Milestone / Capability | Original Academic Prototype | Modernized Portfolio Release |
| :--- | :--- | :--- |
| **Authentication & Password Storage** | Plaintext / legacy MD5 storage; vulnerable to brute-force and credential exposure. | Standard PHP **Bcrypt** (`password_hash` / `password_verify`) with seamless auto-rehash for legacy accounts. |
| **Database Communication** | Raw SQL queries with string concatenation; vulnerable to SQL Injection across endpoints. | 100% parameterized **MySQLi prepared statements** (`prepare()` / `bind_param()`) across all queries. |
| **API & Endpoint Security** | Unauthenticated `ajax.php` dispatcher; any network client could trigger database writes. | Strict session authentication and **Role-Based Access Control (RBAC)** gating Admin, Staff, and Customer actions. |
| **Request Forgery Defense** | No CSRF tokens; vulnerable to cross-site state tampering. | Session-bound cryptographic **CSRF tokens** validated across all POST and AJAX mutations. |
| **Dynamic Routing** | `include $_GET['page'].'.php'` vulnerable to Local File Inclusion (LFI). | Strict **whitelist-based routing** array neutralizing directory traversal attempts. |
| **Borrower View Integrity** | "Current Loan" and "Next Schedule" columns were hardcoded static placeholder strings (`None`, `N/A`). | Live relational queries displaying real active loan references, installment index, due date, and overdue badges. |
| **Runtime Reliability** | Fatal typos (`$thi->db`), missing methods (`delete_user()`), and unclosed test files (`newu.php`). | All syntax and runtime errors eliminated; missing methods implemented; legacy clinic artifacts cleaned. |
| **Automated Verification** | No automated unit or integration tests. | Comprehensive automated verification suite with **74 passing assertions** covering syntax, math, security, and RBAC. |
| **Documentation & Standards** | Minimal setup text file. | Professional GitHub documentation suite (`ARCHITECTURE.md`, `DATABASE.md`, `SECURITY.md`, `SETUP.md`). |

---

## 4. Academic Attribution & Integrity Statement

This repository stands as a testament to continuous technical growth: honoring the conceptual foundation of the original Diploma final-year project while demonstrating modern software engineering, defensive programming, and architecture standards.
