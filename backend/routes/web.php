<?php
// backend/routes/web.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controller/UserController.php';

$controller = new UserController($pdo);

// If the URL looks like: backend/index.php?route=register
$route = $_GET['route'] ?? '';

header('Content-Type: application/json');

if ($route === 'register') {
    $controller->registerUser();
} else {
    echo json_encode(["status" => "error", "message" => "Route not found"]);
}
