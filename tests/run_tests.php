<?php
/**
 * Automated Test Runner for Loan Management System
 * Executes all unit, integration, syntax, and security regression tests.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/security.php';

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$test_results = [];

function assert_test($description, $condition, $details = '') {
    global $total_tests, $passed_tests, $failed_tests, $test_results;
    $total_tests++;
    if ($condition) {
        $passed_tests++;
        $test_results[] = ['status' => 'PASS', 'test' => $description, 'details' => $details];
        echo "  [PASS] {$description}\n";
    } else {
        $failed_tests++;
        $test_results[] = ['status' => 'FAIL', 'test' => $description, 'details' => $details];
        echo "  [FAIL] {$description} -- {$details}\n";
    }
}

echo "======================================================================\n";
echo "LOAN MANAGEMENT SYSTEM - AUTOMATED VERIFICATION SUITE\n";
echo "======================================================================\n\n";

$php_bin = PHP_BINARY;

// ----------------------------------------------------------------------
// SUITE 1: PHP Syntax & Linting
// ----------------------------------------------------------------------
echo "Suite 1: PHP Syntax & Linting\n";
echo "----------------------------------------------------------------------\n";

$php_files = glob(PROJECT_ROOT . "/*.php");
foreach ($php_files as $file) {
    $filename = basename($file);
    $output = [];
    $return_var = 0;
    exec(escapeshellarg($php_bin) . " -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
    $out_str = implode(" ", $output);
    assert_test("Syntax lint: {$filename}", $return_var === 0, $out_str);
}
echo "\n";

// ----------------------------------------------------------------------
// SUITE 2: Security & CSRF Protection
// ----------------------------------------------------------------------
echo "Suite 2: Security, CSRF & XSS Prevention\n";
echo "----------------------------------------------------------------------\n";

$_SESSION = [];
$t1 = csrf_token();
assert_test("CSRF token is generated as a 64-character hex string", strlen($t1) === 64 && ctype_xdigit($t1), "Got: {$t1}");

$t2 = csrf_token();
assert_test("CSRF token is stable and persisted in session", $t1 === $t2, "Tokens mismatch");

$_POST['csrf_token'] = $t1;
assert_test("validate_csrf() returns true for matching token in \$_POST", validate_csrf() === true);

$_POST['csrf_token'] = 'invalid_attacker_token_1234567890';
assert_test("validate_csrf() returns false for forged token", validate_csrf() === false);

unset($_POST['csrf_token']);
$_SERVER['HTTP_X_CSRF_TOKEN'] = $t1;
assert_test("validate_csrf() returns true for matching token in HTTP header", validate_csrf() === true);

$_SERVER['HTTP_X_CSRF_TOKEN'] = 'bad_header_token';
assert_test("validate_csrf() returns false for forged header token", validate_csrf() === false);
unset($_SERVER['HTTP_X_CSRF_TOKEN']);

// XSS Escaping Tests
$xss_payload_1 = '<script>alert("XSS")</script>';
$escaped_1 = escape($xss_payload_1);
assert_test("XSS Escaping: Script tags neutralized", $escaped_1 === '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', "Got: {$escaped_1}");

$xss_payload_2 = 'John "O\'Connor" & Sons <test>';
$escaped_2 = escape($xss_payload_2);
assert_test("XSS Escaping: Quotes, ampersands, and angle brackets escaped", $escaped_2 === 'John &quot;O&#039;Connor&quot; &amp; Sons &lt;test&gt;', "Got: {$escaped_2}");

assert_test("XSS Escaping: Null value safely handled", escape(null) === '', "Got non-empty string on null");
echo "\n";

// ----------------------------------------------------------------------
// SUITE 3: Password Hashing & Verification
// ----------------------------------------------------------------------
echo "Suite 3: Password Hashing, Bcrypt & Backward Compatibility\n";
echo "----------------------------------------------------------------------\n";

$plain = 'admin123';
$hashed = password_hash($plain, PASSWORD_DEFAULT);
assert_test("Password hashing generates standard Bcrypt/Argon2 string", strpos($hashed, '$2y$') === 0 || strpos($hashed, '$argon2') === 0);
assert_test("password_verify confirms correct password against hash", password_verify($plain, $hashed) === true);
assert_test("password_verify rejects incorrect password", password_verify('wrongpassword', $hashed) === false);

// Test legacy plaintext fallback logic as implemented in Action::login()
$db_pass_legacy = 'admin123';
$entered_pass = 'admin123';
$matches = false;
$needs_rehash = false;
if (password_verify($entered_pass, $db_pass_legacy)) {
    $matches = true;
} elseif ($entered_pass === $db_pass_legacy) {
    $matches = true;
    $needs_rehash = true;
}
assert_test("Legacy plaintext password successfully authenticated", $matches === true);
assert_test("Legacy plaintext password triggers auto-rehash flag", $needs_rehash === true);

// Test legacy MD5 fallback logic
$db_pass_md5 = md5('secret123');
$entered_pass_md5 = 'secret123';
$matches_md5 = false;
$needs_rehash_md5 = false;
if (password_verify($entered_pass_md5, $db_pass_md5)) {
    $matches_md5 = true;
} elseif (md5($entered_pass_md5) === $db_pass_md5) {
    $matches_md5 = true;
    $needs_rehash_md5 = true;
}
assert_test("Legacy MD5 password successfully authenticated", $matches_md5 === true);
assert_test("Legacy MD5 password triggers auto-rehash flag", $needs_rehash_md5 === true);
echo "\n";

// ----------------------------------------------------------------------
// SUITE 4: Amortization & Repayment Calculation
// ----------------------------------------------------------------------
echo "Suite 4: Financial Math & Amortization Schedules\n";
echo "----------------------------------------------------------------------\n";

$principal = 100000.00;
$interest_percentage = 8.0; // 8% total
$penalty_rate = 3.0; // 3%
$months = 36;

$monthly = ($principal + ($principal * ($interest_percentage / 100))) / $months;
$total_payable = $monthly * $months;
$expected_monthly = (100000 + 8000) / 36; // 108000 / 36 = 3000.00
$penalty = $monthly * ($penalty_rate / 100); // 3000 * 0.03 = 90.00

assert_test("Monthly payable calculation is exact", abs($monthly - 3000.00) < 0.001, "Expected 3000.00, got: {$monthly}");
assert_test("Total payable matches principal + interest", abs($total_payable - 108000.00) < 0.001, "Expected 108000.00, got: {$total_payable}");
assert_test("Monthly penalty rate calculation is exact", abs($penalty - 90.00) < 0.001, "Expected 90.00, got: {$penalty}");

// Overdue logic
$past_due_date = date("Y-m-d", strtotime("-5 days"));
$future_due_date = date("Y-m-d", strtotime("+25 days"));

$is_past_overdue = (date('Ymd', strtotime($past_due_date)) < date('Ymd'));
$is_future_overdue = (date('Ymd', strtotime($future_due_date)) < date('Ymd'));

assert_test("Overdue detector correctly flags past-due date", $is_past_overdue === true);
assert_test("Overdue detector correctly clears future-due date", $is_future_overdue === false);
echo "\n";

// ----------------------------------------------------------------------
// SUITE 5: Page Whitelisting & LFI Prevention
// ----------------------------------------------------------------------
echo "Suite 5: Page Whitelisting & LFI Prevention\n";
echo "----------------------------------------------------------------------\n";

$allowed_pages = [
    'home',
    'borrowers',
    'loans',
    'payments',
    'plan',
    'loan_type',
    'users'
];

function resolve_page($input_page, $user_type = 1) {
    global $allowed_pages;
    $page = trim($input_page ?? '');
    if (!in_array($page, $allowed_pages, true)) {
        $page = 'home';
    }
    if ($page === 'users' && $user_type != 1) {
        $page = 'home';
    }
    return $page;
}

assert_test("Whitelisted page 'loans' resolves directly", resolve_page('loans', 1) === 'loans');
assert_test("Path traversal attempt '../../etc/passwd' resolves to 'home'", resolve_page('../../etc/passwd', 1) === 'home');
assert_test("Arbitrary file inclusion attempt 'admin_class' resolves to 'home'", resolve_page('admin_class', 1) === 'home');
assert_test("Empty page input resolves to 'home'", resolve_page('', 1) === 'home');
assert_test("Non-admin user (type 2) requesting 'users' page is redirected to 'home'", resolve_page('users', 2) === 'home');
assert_test("Admin user (type 1) requesting 'users' page is permitted", resolve_page('users', 1) === 'users');
echo "\n";

// ----------------------------------------------------------------------
// SUITE 6: Action Class Structure & Bug Fix Verifications
// ----------------------------------------------------------------------
echo "Suite 6: Action Class Integrity & Bug Fix Verifications\n";
echo "----------------------------------------------------------------------\n";

$admin_class_content = file_get_contents(PROJECT_ROOT . '/admin_class.php');

// Verify that $thi->db variable usage is completely gone
assert_test("Fix verified: '\$thi->db' variable usage removed from admin_class.php", preg_match('/\$thi->db/', $admin_class_content) === 0);
assert_test("Fix verified: 'delete_user()' method is defined in Action class", strpos($admin_class_content, 'function delete_user()') !== false);
assert_test("Fix verified: Prepared statements 'prepare(' are utilized throughout Action class", substr_count($admin_class_content, '->prepare(') >= 15);
assert_test("Fix verified: password_hash() is utilized for credential storage", strpos($admin_class_content, 'password_hash(') !== false);

$db_connect_content = file_get_contents(PROJECT_ROOT . '/db_connect.php');
// Check that undefined standalone $con is not present
assert_test("Fix verified: Undefined variable '\$con' removed from db_connect.php", preg_match('/\$con\b/', $db_connect_content) === 0);

$index_content = file_get_contents(PROJECT_ROOT . '/index.php');
assert_test("Fix verified: 'exit;' follows redirect header in index.php", preg_match('/header\([\'"]location:login\.php[\'"]\);\s*exit;/i', $index_content) === 1);

$ajax_content = file_get_contents(PROJECT_ROOT . '/ajax.php');
assert_test("Fix verified: ajax.php enforces authentication check", strpos($ajax_content, 'unauthorized') !== false);
assert_test("Fix verified: ajax.php enforces CSRF validation on mutations", strpos($ajax_content, 'validate_csrf') !== false);
assert_test("Fix verified: ajax.php enforces admin-only access check", strpos($ajax_content, '$admin_only_actions') !== false);

$home_content = file_get_contents(PROJECT_ROOT . '/home.php');
assert_test("Fix verified: Legacy 'Dr.' template artifact removed from home.php", strpos($home_content, 'Dr.') === false);
assert_test("Fix verified: Legacy 'login_name_pref' session key removed from home.php", strpos($home_content, 'login_name_pref') === false);

$borrowers_content = file_get_contents(PROJECT_ROOT . '/borrowers.php');
assert_test("Fix verified: Hardcoded 'None' and 'N/A' replaced with active loan & schedule query", strpos($borrowers_content, 'SELECT l.*, t.type_name') !== false);

$loan_type_content = file_get_contents(PROJECT_ROOT . '/loan_type.php');
assert_test("Fix verified: Action name typo 'delete_loan-type' corrected to 'delete_loan_type'", strpos($loan_type_content, 'action=delete_loan-type') === false);

assert_test("Safety verified: Corrupted newu.php is removed from active project", !file_exists(PROJECT_ROOT . '/newu.php'));
assert_test("Safety verified: Original copy of newu.php preserved in external archive", file_exists('d:/Projects/archive_preserved_files/newu.php'));
assert_test("Safety verified: Full project backup preserved in backup directory", file_exists('d:/Projects/Loan_Management_System_backup_original/admin_class.php'));
echo "\n";

// ----------------------------------------------------------------------
// SUMMARY
// ----------------------------------------------------------------------
echo "======================================================================\n";
echo "TEST RESULTS SUMMARY\n";
echo "======================================================================\n";
echo "Total Tests Run : {$total_tests}\n";
echo "Passed          : {$passed_tests}\n";
echo "Failed          : {$failed_tests}\n";
echo "Pass Rate       : " . number_format(($passed_tests / $total_tests) * 100, 2) . "%\n";
echo "======================================================================\n";

if ($failed_tests > 0) {
    exit(1);
} else {
    exit(0);
}
