<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the CSRF token for the active session.
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate incoming CSRF token from POST body or X-CSRF-Token header.
 * Returns true if valid, false otherwise.
 */
function validate_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safe HTML escaping for preventing Cross-Site Scripting (XSS).
 */
function escape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Enforce authentication for protected requests.
 * Terminates execution with an unauthorized message if not authenticated.
 */
function require_auth() {
    if (empty($_SESSION['login_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

/**
 * Enforce admin-only permission.
 */
function require_admin() {
    require_auth();
    if (($_SESSION['login_type'] ?? null) != 1) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
        exit;
    }
}
