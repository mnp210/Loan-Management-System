<?php 
require_once __DIR__ . '/security.php';

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'loan_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Could not connect to mysql: " . $conn->connect_error);
}
