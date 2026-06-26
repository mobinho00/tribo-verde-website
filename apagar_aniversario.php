<?php
session_start();

$is_ajax = isset($_GET['ajax']);

if ($is_ajax) {
    header('Content-Type: application/json');
    ob_start(); // captura qualquer output de erro
}

if (!($_SESSION['admin_desbloqueado'] ?? false)) {
    if ($is_ajax) { ob_end_clean(); echo json_encode(['ok' => false, 'erro' => 'nao autorizado']); exit; }
    header('Location: admin.php'); exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    if ($is_ajax) { ob_end_clean(); echo json_encode(['ok' => false, 'erro' => 'id invalido']); exit; }
    header('Location: admin.php'); exit;
}

try {
    $pdo->prepare("DELETE FROM reservas_aniversarios WHERE id = ?")->execute([$id]);
    if ($is_ajax) { ob_end_clean(); echo json_encode(['ok' => true]); exit; }
    header('Location: admin.php?aniv_apagado=1'); exit;
} catch (PDOException $e) {
    if ($is_ajax) { ob_end_clean(); echo json_encode(['ok' => false, 'erro' => $e->getMessage()]); exit; }
    header('Location: admin.php'); exit;
}
?>