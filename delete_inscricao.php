<?php
session_start();
if (!isset($_SESSION['admin_desbloqueado']) || $_SESSION['admin_desbloqueado'] !== true) {
    if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['ok' => false]); exit; }
    header('Location: admin.php'); exit;
}
require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['ok' => false]); exit; }
    header('Location: admin.php'); exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM inscricoes WHERE id = ?");
    $stmt->execute([$id]);

    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    } else {
        header('Location: admin.php?apagado=1');
    }
    exit;

} catch (PDOException $e) {
    if (isset($_GET['ajax'])) { header('Content-Type: application/json'); echo json_encode(['ok' => false]); exit; }
    header('Location: admin.php?erro_apagar=1'); exit;
}
?>