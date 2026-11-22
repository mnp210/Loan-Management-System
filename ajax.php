<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/admin_class.php';

$action = $_GET['action'] ?? '';
if (empty($action)) {
    http_response_code(400);
    echo "No action specified";
    exit;
}

$public_actions = ['login', 'login2', 'signup'];
$admin_only_actions = ['save_user', 'delete_user', 'save_settings'];
$staff_actions = [
    'logout', 'logout2',
    'save_loan_type', 'delete_loan_type',
    'save_plan', 'delete_plan',
    'save_borrower', 'delete_borrower',
    'save_loan', 'delete_loan',
    'save_payment', 'delete_payment'
];

// 1. Authentication check
if (!in_array($action, $public_actions, true)) {
    if (empty($_SESSION['login_id'])) {
        http_response_code(401);
        echo "unauthorized";
        exit;
    }

    // 2. Role-based Authorization check
    if (in_array($action, $admin_only_actions, true)) {
        if (($_SESSION['login_type'] ?? null) != 1) {
            http_response_code(403);
            echo "forbidden";
            exit;
        }
    } elseif (in_array($action, $staff_actions, true)) {
        if (!in_array($_SESSION['login_type'] ?? null, [1, 2])) {
            http_response_code(403);
            echo "forbidden";
            exit;
        }
    }
}

// 3. CSRF Protection for state-changing operations
$state_modifying_actions = [
    'save_user', 'delete_user', 'save_settings',
    'save_loan_type', 'delete_loan_type',
    'save_plan', 'delete_plan',
    'save_borrower', 'delete_borrower',
    'save_loan', 'delete_loan',
    'save_payment', 'delete_payment'
];

if (in_array($action, $state_modifying_actions, true)) {
    if (!validate_csrf()) {
        http_response_code(403);
        echo "invalid_csrf";
        exit;
    }
}

$crud = new Action();

if (method_exists($crud, $action)) {
    $result = $crud->$action();
    if ($result !== null) {
        echo $result;
    }
} else {
    http_response_code(404);
    echo "Action not found";
}
