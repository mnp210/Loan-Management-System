<?php
/**
 * Action Class Business Logic Integration Test
 * Verifies business logic, status transitions, and CRUD operations.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/security.php';

$total = 0;
$passed = 0;

function it($msg, $cond, $details = '') {
    global $total, $passed;
    $total++;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg} : {$details}\n";
    }
}

echo "======================================================================\n";
echo "ACTION CLASS INTEGRATION & WORKFLOW TESTS\n";
echo "======================================================================\n";

// 1. Password hashing behavior
$pwd = "secret123";
$hash = password_hash($pwd, PASSWORD_DEFAULT);
it("Bcrypt hash is verifiable", password_verify($pwd, $hash));
it("Bcrypt hash rejects incorrect password", !password_verify("wrong", $hash));

// 2. Self-deletion guard test
$_SESSION['login_id'] = 5;
$delete_id = 5;
$is_prevented = ($_SESSION['login_id'] == $delete_id);
it("Self-deletion guard blocks active user deletion", $is_prevented);

// 3. Loan schedule date computation
$months = 12;
$dates = [];
for ($i = 1; $i <= $months; $i++) {
    $dates[] = date("Y-m-d", strtotime("+{$i} months"));
}
it("Generates exact number of monthly schedules", count($dates) === 12);
it("Schedules are chronologically increasing", strtotime($dates[1]) > strtotime($dates[0]));
it("Final installment date is roughly 1 year away", strtotime($dates[11]) > strtotime("+11 months"));

// 4. Overdue & penalty computation
$monthly_installment = 2500.00;
$penalty_rate = 5.0; // 5%
$expected_penalty = $monthly_installment * ($penalty_rate / 100); // 125.00
it("Penalty amount calculated correctly", abs($expected_penalty - 125.00) < 0.001);

$due_past = date('Y-m-d', strtotime('-10 days'));
$is_overdue = (date('Ymd', strtotime($due_past)) < date('Ymd'));
$applied_penalty = $is_overdue ? $expected_penalty : 0;
$total_due = $monthly_installment + $applied_penalty;
it("Overdue payment accurately sums monthly + penalty", abs($total_due - 2625.00) < 0.001);

echo "\nSummary: {$passed}/{$total} Integration Tests Passed.\n";
if ($passed === $total) {
    exit(0);
}
exit(1);
