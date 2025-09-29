<?php
// backend/helpers/auth.php
// Helper functions for token-based auth (and small session helpers)

// Get the Authorization header
function getAuthorizationHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        return trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                return trim($value);
            }
        }
    }
    // fallback for certain servers
    foreach ($_SERVER as $key => $value) {
        if (strtolower($key) === 'http_authorization') return trim($value);
    }
    return null;
}

// Extract Bearer token from header
function getBearerToken() {
    $header = getAuthorizationHeader();
    if ($header && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

// Return user record for a given token (or null)
function getUserByToken($pdo, $token) {
    if (!$token) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE api_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

// Require a valid token; returns the user array or exits with 401
function requireAuthToken($pdo) {
    header('Content-Type: application/json');
    $token = getBearerToken();
    $user = getUserByToken($pdo, $token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized - invalid or missing token']);
        exit;
    }
    return $user;
}

// Require token and also require a specific role (by role_id)
function requireRoleToken($pdo, $role_id) {
    $user = requireAuthToken($pdo);
    if ((int)$user['role_id'] !== (int)$role_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden - insufficient permissions']);
        exit;
    }
    return $user;
}

/* --- small session helpers (optional) --- */

// start session if not started
function startSessionIfNeeded() {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// require user logged in via session
function requireLoginSession() {
    startSessionIfNeeded();
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Not logged in (session)']);
        exit;
    }
    return $_SESSION;
}

// require session role
function requireRoleSession($role_id) {
    $s = requireLoginSession();
    if ((int)$s['role'] !== (int)$role_id) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden - session role mismatch']);
        exit;
    }
    return $s;
}
