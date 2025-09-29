<?php
// backend/routes/web.php

require_once __DIR__ . '/../config/db.php';           // creates $pdo
require_once __DIR__ . '/../controller/UserController.php';
require_once __DIR__ . '/../helpers/auth.php';       // token/role helpers
require_once __DIR__ . '/../controller/ProductionController.php';
require_once __DIR__ . '/../controller/ProcurementController.php';

$procController = new ProcurementController($pdo);

$prodController = new ProductionController($pdo);

$controller = new UserController($pdo);

$route = $_GET['route'] ?? '';

header('Content-Type: application/json');

// ----------------- Public Routes -----------------

// Registration
if ($route === 'register') {
    $controller->registerUser();
    exit;
}

// Login
if ($route === 'login') {
    $controller->loginUser();
    exit;
}

// Reset password (current-password based option)
if ($route === 'reset_password') {
    $controller->resetPassword();
    exit;
}

// Send OTP to email
if ($route === 'send_reset_otp') {
    $controller->sendResetOTP();
    exit;
}

// Reset password with OTP
if ($route === 'reset_password_otp') {
    $controller->resetPasswordWithOTP();
    exit;
}

// ----------------- Protected Routes -----------------

// Admin create user
if ($route === 'admin_create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // require token and admin role (role_id = 1)
    $admin = requireRoleToken($pdo, 1); // will exit(403) if not admin
    $controller->adminCreateUser();
    exit;
}

// Authenticated user’s own profile
if ($route === 'my_profile' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuthToken($pdo); // returns user array if token ok
    echo json_encode(['status' => 'success', 'user' => $user]);
    exit;
}




// ----------------- Production Routes -----------------

// Farmer creates production plan
if ($route === 'create_production' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer = requireRoleToken($pdo, 2); // Only farmers
    $prodController->createProduction($farmer);
    exit;
}

// Farmer logs a production stage (new)
if ($route === 'add_production_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $farmer = requireRoleToken($pdo, 2); // Only farmers
    $prodController->addProductionUpdate($farmer);
    exit;
}

// List production plans (admin sees all, farmer sees own)
if ($route === 'list_production' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuthToken($pdo);
    $prodController->listProduction($user);
    exit;
}

// List production updates for a plan
if ($route === 'list_production_updates' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuthToken($pdo);
    $production_id = $_GET['production_id'] ?? 0;
    $prodController->listProductionUpdates($user, $production_id);
    exit;
}

// List all production stage updates (all plans)
if ($route === 'list_all_production_updates' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuthToken($pdo); // must be logged in
    $prodController->listAllProductionUpdates($user);
    exit;
}



// ----------------- Procurement Routes -----------------

// Procurement officer creates procurement record
if ($route === 'create_procurement' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $officer = requireRoleToken($pdo, 3); // 3 = Procurement Officer
    $procController->createProcurement($officer);
    exit;
}

// Update procurement status (admin or officer)
if ($route === 'update_procurement_status' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $user = requireAuthToken($pdo); // admin or officer
    $id = $_GET['id'] ?? 0;
    $procController->updateProcurementStatus($user, $id);
    exit;
}

// List procurements (admin sees all, officer sees theirs, farmer sees their own)
if ($route === 'list_procurements' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuthToken($pdo);
    $procController->listProcurements($user);
    exit;
}

// ----------------- Fallback -----------------
http_response_code(404);
echo json_encode(["status" => "error", "message" => "Route not found"]);
exit;