<?php
$host = 'localhost'; // Don't use 'mysql-user.cs.pluto.edu' unless told otherwise
$db   = 'sm74db';
$user = 'sm74';
$pass = 'password'; // 🔐 Replace this with your real password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>

