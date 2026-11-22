<?php
/**
 * Screenshot Capture Script for Loan Management System
 * Renders authentic application pages with live database connection
 * and captures high-resolution screenshots via Microsoft Edge headless.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PROJECT_ROOT', dirname(__DIR__));
require_once PROJECT_ROOT . '/db_connect.php';

$output_dir = PROJECT_ROOT . '/screenshots';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

$temp_dir = PROJECT_ROOT . '/temp_render';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// 1. Render Login Page
echo "Rendering Login Page...\n";
$_SESSION = [];
ob_start();
include PROJECT_ROOT . '/login.php';
$login_html = ob_get_clean();
// Fix asset paths to point to project root
$login_html = str_replace('href="assets/', 'href="../assets/', $login_html);
$login_html = str_replace('src="assets/', 'src="../assets/', $login_html);
$login_html = str_replace('url(assets/', 'url(../assets/', $login_html);
file_put_contents($temp_dir . '/01_login.html', $login_html);

// Setup Admin Session for Authenticated Pages
$_SESSION['login_id'] = 1;
$_SESSION['login_name'] = 'Administrator';
$_SESSION['login_username'] = 'admin';
$_SESSION['login_type'] = 1;

$authenticated_pages = [
    'home' => '02_dashboard',
    'borrowers' => '03_borrowers',
    'loans' => '04_loans',
    'payments' => '05_payments',
    'plan' => '06_loan_plans',
    'users' => '07_users'
];

foreach ($authenticated_pages as $page => $filename) {
    echo "Rendering {$page} ({$filename})...\n";
    $_GET['page'] = $page;
    ob_start();
    include PROJECT_ROOT . '/index.php';
    $page_html = ob_get_clean();
    $page_html = str_replace('href="assets/', 'href="../assets/', $page_html);
    $page_html = str_replace('src="assets/', 'src="../assets/', $page_html);
    $page_html = str_replace('url(assets/', 'url(../assets/', $page_html);
    file_put_contents($temp_dir . '/' . $filename . '.html', $page_html);
}

echo "Generating screenshots using Microsoft Edge Headless...\n";

$edge_bin = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
if (!file_exists($edge_bin)) {
    $edge_bin = 'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe';
}

$files_to_capture = [
    '01_login' => '01_login.png',
    '02_dashboard' => '02_dashboard.png',
    '03_borrowers' => '03_borrowers.png',
    '04_loans' => '04_loans.png',
    '05_payments' => '05_payments.png',
    '06_loan_plans' => '06_loan_plans.png',
    '07_users' => '07_users.png'
];

foreach ($files_to_capture as $html_name => $png_name) {
    $html_file = realpath($temp_dir . '/' . $html_name . '.html');
    $png_file = $output_dir . '/' . $png_name;
    $file_url = 'file:///' . str_replace('\\', '/', $html_file);

    $cmd = escapeshellarg($edge_bin) . ' --headless --disable-gpu --hide-scrollbars --window-size=1280,820 --virtual-time-budget=3000 --screenshot=' . escapeshellarg($png_file) . ' ' . escapeshellarg($file_url) . ' 2>&1';
    
    echo "Capturing {$png_name}...\n";
    exec($cmd, $output, $ret);
    if (file_exists($png_file) && filesize($png_file) > 1000) {
        echo "  [OK] Saved: {$png_file} (" . round(filesize($png_file) / 1024) . " KB)\n";
    } else {
        echo "  [ERROR] Failed to capture {$png_name}\n";
    }
}

// Cleanup temp_render
echo "Cleaning up temporary render files...\n";
foreach (glob($temp_dir . '/*.html') as $tfile) {
    unlink($tfile);
}
rmdir($temp_dir);

echo "Screenshots successfully generated and saved to {$output_dir}\n";
