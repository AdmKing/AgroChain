<?php
// backend/index.php
// show errors in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// just include web.php
require_once __DIR__ . '/routes/web.php';
