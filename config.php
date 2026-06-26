<?php
$servidor = 'sql203.infinityfree.com';
$usuario = '!!!!!!!!!!';
$senha = '!!!!!!!!!!!!!';
$banco = 'if0_41175708_triboverde';

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8mb4", $usuario, $senha);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET time_zone = '+00:00'");
    
} catch(PDOException $e) {
    die("Erro na ligação: " . $e->getMessage());
}

// Configura timezone do PHP para Portugal
date_default_timezone_set('Europe/Lisbon');
?>