<?php
session_start();
if (!($_SESSION['admin_desbloqueado'] ?? false)) { http_response_code(403); exit; }

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit;

$stmt = $pdo->prepare("SELECT * FROM pausas_letivas_pedidos WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) exit;

$meses_pt       = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
$dias_semana_pt = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

$dias = array_filter(array_map('trim', explode(',', $p['dias_pedidos'])));
sort($dias);
$dias_fmt = [];
foreach ($dias as $d) {
    $dt = new DateTime($d);
    $dias_fmt[] = $dias_semana_pt[$dt->format('w')] . ', ' . $dt->format('d') . ' de ' . $meses_pt[$dt->format('m')] . ' de ' . $dt->format('Y');
}

$almoco_map       = ['sim_pago' => 'Sim, por um valor adicional (5€/dia)', 'nao_casa' => 'Não, levamos de casa'];
$como_conheceu_map = ['redes_sociais' => 'Redes Sociais', 'site' => 'Site', 'amigos_familia' => 'Amigos ou Familiares'];
$fotos_map        = ['sim' => 'Sim', 'sim_nao_reconhecivel' => 'Sim, caso não seja possível reconhecer a criança', 'nao' => 'Não'];

$nasc_fmt     = $p['data_nascimento_crianca'] ? date('d/m/Y', strtotime($p['data_nascimento_crianca'])) : 'N/A';
$criacao_fmt  = date('d/m/Y H:i', strtotime($p['created_at']));

switch ($p['estado']) {
    case 'confirmado': $badge = 'bg-success'; $label = '✅ Confirmado'; break;
    case 'cancelado':  $badge = 'bg-danger';  $label = '❌ Cancelado'; break;
    default:           $badge = 'bg-warning text-dark'; $label = '⏳ Pendente'; break;
}
?>
<div class="p-2">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h6 class="fw-bold mb-0" style="color:#4a8c65;">Pedido #<?= $p['id'] ?></h6>
        <span class="badge <?= $badge ?> px-3 py-2"><?= $label ?></span>
    </div>

    <!-- Responsável -->
    <div class="section-header mb-2">👤 Responsável</div>
    <div class="info-row"><div class="info-label">Nome</div><div class="info-value"><?= htmlspecialchars($p['nome_responsavel']) ?></div></div>
    <div class="info-row"><div class="info-label">NIF</div><div class="info-value"><?= htmlspecialchars($p['nif_responsavel'] ?? 'N/A') ?></div></div>
    <div class="info-row"><div class="info-label">Email</div><div class="info-value"><a href="mailto:<?= htmlspecialchars($p['email']) ?>"><?= htmlspecialchars($p['email']) ?></a></div></div>
    <div class="info-row"><div class="info-label">Telefone</div><div class="info-value"><a href="tel:<?= htmlspecialchars($p['telefone']) ?>"><?= htmlspecialchars($p['telefone']) ?></a></div></div>
    <div class="info-row"><div class="info-label">Zona</div><div class="info-value"><?= htmlspecialchars($p['zona_residencia'] ?? 'N/A') ?></div></div>
    <div class="info-row"><div class="info-label">Como conheceu</div><div class="info-value"><?= $como_conheceu_map[$p['como_conheceu']] ?? $p['como_conheceu'] ?></div></div>
    <div class="info-row"><div class="info-label">Autoriza fotos</div><div class="info-value"><?= $fotos_map[$p['autoriza_fotos']] ?? $p['autoriza_fotos'] ?></div></div>

    <!-- Criança -->
    <div class="section-header mt-3 mb-2">🧒 Criança</div>
    <div class="info-row"><div class="info-label">Nome</div><div class="info-value fw-bold"><?= htmlspecialchars($p['nome_crianca']) ?></div></div>
    <div class="info-row"><div class="info-label">Data de Nascimento</div><div class="info-value"><?= $nasc_fmt ?></div></div>
    <div class="info-row"><div class="info-label">NIF</div><div class="info-value"><?= htmlspecialchars($p['nif_crianca'] ?? 'N/A') ?></div></div>
    <div class="info-row"><div class="info-label">Sesta</div><div class="info-value"><?= $p['sesta'] === 'sim' ? 'Sim' : 'Não' ?></div></div>
    <div class="info-row"><div class="info-label">Serviço de Almoço</div><div class="info-value"><?= $almoco_map[$p['almoco']] ?? $p['almoco'] ?></div></div>
    <?php if (!empty($p['cuidados_saude'])): ?>
    <div class="info-row" style="background:#fff3cd;">
        <div class="info-label">⚠️ Cuidados Saúde</div>
        <div class="info-value fw-bold"><?= htmlspecialchars($p['cuidados_saude']) ?></div>
    </div>
    <?php endif; ?>
    <div class="info-row"><div class="info-label">Autoriza fármacos</div><div class="info-value"><?= $p['farmacos'] === 'sim' ? 'Sim' : 'Não' ?></div></div>

    <!-- Dias -->
    <div class="section-header mt-3 mb-2">📅 Dias Pedidos (<?= count($dias_fmt) ?> dia(s))</div>
    <ul style="padding-left:20px;margin:0 0 12px;">
        <?php foreach ($dias_fmt as $df): ?>
        <li style="padding:3px 0;"><?= $df ?></li>
        <?php endforeach; ?>
    </ul>

    <?php if (!empty($p['mensagem'])): ?>
    <div class="section-header mt-3 mb-2">💬 Observações</div>
    <div class="mensagem-box"><?= nl2br(htmlspecialchars($p['mensagem'])) ?></div>
    <?php endif; ?>

    <div class="info-row mt-2"><div class="info-label">Recebido em</div><div class="info-value text-muted"><?= $criacao_fmt ?></div></div>

    <!-- Ações -->
    <?php if ($p['estado'] === 'pendente'): ?>
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <button onclick="confirmarPausa(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome_responsavel']) ?>')" class="btn btn-success">
            <i class="fas fa-check me-1"></i>Confirmar Inscrição
        </button>
        <button onclick="cancelarPausa(<?= $p['id'] ?>)" class="btn btn-outline-danger">
            <i class="fas fa-times me-1"></i>Cancelar
        </button>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 mt-2">
        <button id="btn-imprimir-ficha" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-print me-1"></i>Imprimir
        </button>
    </div>
</div>