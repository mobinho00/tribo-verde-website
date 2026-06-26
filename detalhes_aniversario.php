<?php
session_start();
if (!($_SESSION['admin_desbloqueado'] ?? false)) { header('Location: /admin.php'); exit; }

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<div class="alert alert-danger m-3">ID inválido.</div>'; exit; }

$stmt = $pdo->prepare("SELECT * FROM reservas_aniversarios WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r) { echo '<div class="alert alert-danger m-3">Reserva não encontrada.</div>'; exit; }

$data_f        = date('d/m/Y', strtotime($r['data_festa']));
$data_insc     = date('d/m/Y H:i', strtotime($r['data_inscricao']));
$limite_fmt    = $r['data_limite_pagamento'] ? date('d/m/Y H:i', strtotime($r['data_limite_pagamento'])) : 'N/A';
$periodo_label = $r['periodo'] === 'manha' ? '☀️ Manhã (até às 13h00)' : '🌙 Tarde (a partir das 14h00)';
$preco_base    = 180.00;
$extra         = max(0, $r['num_criancas'] - 12) * 8.50;
$preco_total   = $preco_base + $extra;
$restante      = $preco_total - 90.00;

switch ($r['estado']) {
    case 'pago':      $badge = 'bg-success'; $label = '✅ Pago'; break;
    case 'cancelado': $badge = 'bg-danger';  $label = '❌ Cancelado'; break;
    default:          $badge = 'bg-warning text-dark'; $label = '⏳ Pendente'; break;
}
?>

<div class="p-3">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0" style="color:#e83e8c;">Reserva #<?php echo $r['id']; ?></h5>
        <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2 fs-6"><?php echo $label; ?></span>
    </div>

    <div class="mb-4">
        <div class="section-header mb-3">👤 Responsável</div>
        <div class="info-row"><div class="info-label">Nome</div><div class="info-value fw-semibold"><?php echo htmlspecialchars($r['nome_responsavel']); ?></div></div>
        <div class="info-row"><div class="info-label">Email</div><div class="info-value"><a href="mailto:<?php echo htmlspecialchars($r['email']); ?>"><?php echo htmlspecialchars($r['email']); ?></a></div></div>
        <div class="info-row"><div class="info-label">Telefone</div><div class="info-value"><a href="tel:<?php echo htmlspecialchars($r['telefone']); ?>"><?php echo htmlspecialchars($r['telefone']); ?></a></div></div>
    </div>

    <div class="mb-4">
        <div class="section-header mb-3">🎂 Festa</div>
        <div class="info-row"><div class="info-label">Aniversariante</div><div class="info-value fw-semibold"><?php echo htmlspecialchars($r['nome_aniversariante']); ?> (<?php echo $r['idade_aniversariante']; ?> anos)</div></div>
        <div class="info-row"><div class="info-label">Data</div><div class="info-value fw-semibold"><?php echo $data_f; ?></div></div>
        <div class="info-row"><div class="info-label">Período</div><div class="info-value"><?php echo $periodo_label; ?></div></div>
        <div class="info-row"><div class="info-label">Nº de Crianças</div><div class="info-value"><?php echo $r['num_criancas']; ?></div></div>
    </div>

    <div class="mb-4">
        <div class="section-header mb-3">💰 Pagamento</div>
        <div class="info-row"><div class="info-label">Valor Total</div><div class="info-value fw-bold"><?php echo number_format($preco_total, 2, ',', '.'); ?>€</div></div>
        <div class="info-row"><div class="info-label">Sinal</div><div class="info-value text-success fw-bold">90,00€</div></div>
        <div class="info-row"><div class="info-label">Restante no dia</div><div class="info-value fw-bold" style="color:#e83e8c;"><?php echo number_format($restante, 2, ',', '.'); ?>€</div></div>
        <?php if ($r['estado'] === 'pendente'): ?>
        <div class="info-row"><div class="info-label">Limite Pagamento</div><div class="info-value text-danger fw-semibold"><?php echo $limite_fmt; ?></div></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($r['mensagem'])): ?>
    <div class="mb-4">
        <div class="section-header mb-3">💬 Mensagem</div>
        <div class="mensagem-box"><?php echo nl2br(htmlspecialchars($r['mensagem'])); ?></div>
    </div>
    <?php endif; ?>

    <div class="mb-2">
        <div class="section-header mb-3">📋 Registo</div>
        <div class="info-row"><div class="info-label">Data de Inscrição</div><div class="info-value"><?php echo $data_insc; ?></div></div>
    </div>

    <?php if ($r['estado'] === 'pendente'): ?>
    <div class="d-flex gap-2 mt-4">
        <a href="confirmar_aniversario.php?id=<?php echo $r['id']; ?>"
           class="btn btn-success flex-fill"
           onclick="return confirm('Confirmar pagamento e enviar email ao cliente?')">
            <i class="fas fa-check me-2"></i>Confirmar Pagamento
        </a>
        <a href="cancelar_aniversario.php?id=<?php echo $r['id']; ?>"
           class="btn btn-outline-danger flex-fill"
           onclick="return confirm('Cancelar esta reserva?')">
            <i class="fas fa-times me-2"></i>Cancelar
        </a>
    </div>
    <?php endif; ?>

</div>

<style>
.section-header { background:#fdf0f5; border-left:4px solid #e83e8c; padding:8px 12px; border-radius:0 6px 6px 0; color:#c2185b; font-weight:bold; }
.info-label { font-weight:600; color:#6c757d; font-size:0.85rem; margin-bottom:4px; }
.info-value { font-size:1rem; color:#212529; }
.info-row { padding:10px 0; border-bottom:1px solid #e9ecef; }
.mensagem-box { background:#f8f9fa; padding:15px; border-radius:8px; border-left:4px solid #e83e8c; }
</style>