<?php

$dsn = 'mysql:host=localhost; dbname=myfirstdatabase; charset=utf8mb4';
$dbusername = 'root';
$dbpassword = '';

//error handling
try {
  $pdo = new PDO($dsn, $dbusername, $dbpassword);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Connection failed: " .  $e->getMessage());
} // echo "Database connected successfully";