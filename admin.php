<?php
session_start();

define('ADMIN_PASSWORD', 'triboverde123');

$desbloqueado = $_SESSION['admin_desbloqueado'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_desbloqueado'] = true;
        $desbloqueado = true;
    } else {
        $erro_password = true;
    }
}

if (!$desbloqueado) { ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .password-box { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 400px; width: 100%; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="password-box">
        <div class="text-center mb-4">
            <i class="fas fa-lock fa-4x text-success mb-3"></i>
            <h2>Painel Admin</h2>
            <p class="text-muted">Insere a password para aceder</p>
        </div>
        <?php if (isset($erro_password)): ?>
        <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Password incorreta!</div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="password" name="password" class="form-control form-control-lg" placeholder="Password" required autofocus>
            </div>
            <button type="submit" class="btn btn-success btn-lg w-100">
                <i class="fas fa-unlock me-2"></i>Entrar
            </button>
        </form>
    </div>
</body>
</html>
<?php exit; }

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

// ── Atualizar vagas ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_vagas'])) {
    foreach ($_POST['vagas'] as $ativ_id => $vagas_valor) {
        $info_email = $_POST['info_email'][$ativ_id] ?? '';
        $pdo->prepare("UPDATE atividades SET vagas_mes = ?, info_email = ? WHERE id = ?")
            ->execute([(int)$vagas_valor, $info_email, (int)$ativ_id]);
    }
    header('Location: admin.php?vagas_ok=1'); exit;
}

// ── Pausas: atualizar ano ativo ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_ano_pausas'])) {
    $novo_ano = (int)$_POST['ano_pausas'];
    if ($novo_ano >= 2024 && $novo_ano <= 2030) {
        $pdo->prepare("UPDATE pausas_letivas_config SET ano_ativo = ?")->execute([$novo_ano]);
    }
    header('Location: admin.php?ano_pausas_ok=1'); exit;
}

// ── Pausas: gerar dias úteis ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gerar_dias_pausas'])) {
    $stmt_cfg = $pdo->query("SELECT ano_ativo FROM pausas_letivas_config LIMIT 1");
    $ano_g = (int)($stmt_cfg->fetchColumn() ?: date('Y'));
    foreach ([6, 7, 8] as $mes) {
        $total_dias = cal_days_in_month(CAL_GREGORIAN, $mes, $ano_g);
        for ($d = 1; $d <= $total_dias; $d++) {
            $data    = "$ano_g-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dia_sem = date('N', strtotime($data));
            if ($dia_sem <= 5) {
                $vagas = ($dia_sem == 5) ? 35 : 30;
                $pdo->prepare("INSERT IGNORE INTO pausas_letivas_dias (data, vagas_total, vagas_ocupadas, encerrado) VALUES (?, ?, 0, 0)")
                    ->execute([$data, $vagas]);
            }
        }
    }
    header('Location: admin.php?dias_gerados=1'); exit;
}

// ── Pausas: atualizar dia ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_dia_pausa'])) {
    $data_dia    = $_POST['data_dia'] ?? '';
    $encerrado   = isset($_POST['encerrado']) ? 1 : 0;
    $vagas_total = (int)($_POST['vagas_total'] ?? 35);
    $pdo->prepare("UPDATE pausas_letivas_dias SET encerrado = ?, vagas_total = ? WHERE data = ?")
        ->execute([$encerrado, $vagas_total, $data_dia]);
    header('Location: admin.php?dia_ok=1'); exit;
}

// ── Dados gerais ──────────────────────────────────────────────
$mes_atual = date('Y-m');
$ativs = $pdo->prepare("
    SELECT a.*, COUNT(i.id) as inscritos_mes,
           GREATEST(a.vagas_mes - COUNT(i.id), 0) as vagas_disponiveis
    FROM atividades a
    LEFT JOIN inscricoes i ON i.atividade_id = a.id
        AND DATE_FORMAT(i.data_inscricao, '%Y-%m') = ?
    GROUP BY a.id ORDER BY a.nome ASC
");
$ativs->execute([$mes_atual]);
$lista_ativs = $ativs->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT i.id, i.nome_responsavel, i.email, i.telefone,
           i.nome_crianca, i.data_nascimento, i.mensagem,
           i.data_inscricao, a.nome as atividade_nome
    FROM inscricoes i
    LEFT JOIN atividades a ON i.atividade_id = a.id
    ORDER BY i.data_inscricao DESC
");
$total = $stmt->rowCount();
$todas_inscricoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_mes = $pdo->prepare("SELECT COUNT(*) FROM inscricoes WHERE DATE_FORMAT(data_inscricao, '%Y-%m') = ?");
$stmt_mes->execute([$mes_atual]);
$total_mes = $stmt_mes->fetchColumn();

$stmt_aniv      = $pdo->query("SELECT * FROM reservas_aniversarios ORDER BY data_inscricao DESC");
$todas_reservas = $stmt_aniv->fetchAll(PDO::FETCH_ASSOC);
$total_aniv     = count($todas_reservas);
$total_aniv_pendente = count(array_filter($todas_reservas, fn($r) => $r['estado'] === 'pendente'));

$hoje_visitas = $pdo->query("SELECT COUNT(DISTINCT ip) FROM visitas WHERE DATE(data_visita) = CURDATE()")->fetchColumn();
$mes_visitas  = $pdo->query("SELECT COUNT(DISTINCT ip) FROM visitas WHERE DATE_FORMAT(data_visita, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')")->fetchColumn();

// ── Pausas Lectivas — dados ───────────────────────────────────
$stmt_cfg_pausas = $pdo->query("SELECT ano_ativo FROM pausas_letivas_config LIMIT 1");
$ano_ativo_pausas = (int)($stmt_cfg_pausas->fetchColumn() ?: date('Y'));

$stmt_dias_admin = $pdo->prepare("SELECT * FROM pausas_letivas_dias WHERE YEAR(data) = ? ORDER BY data ASC");
$stmt_dias_admin->execute([$ano_ativo_pausas]);
$dias_admin = [];
while ($row = $stmt_dias_admin->fetch(PDO::FETCH_ASSOC)) {
    $dias_admin[$row['data']] = $row;
}

$stmt_pedidos    = $pdo->query("SELECT * FROM pausas_letivas_pedidos ORDER BY created_at DESC");
$pedidos_pausas  = $stmt_pedidos->fetchAll(PDO::FETCH_ASSOC);
$total_pedidos   = count($pedidos_pausas);
$total_pausas_pendentes = count(array_filter($pedidos_pausas, fn($p) => $p['estado'] === 'pendente'));

$frases = [
    "A natureza é a melhor sala de aula. Tu abres a porta todos os dias. 🌿",
    "Cada criança que cresce na Tribo Verde leva um bocado de floresta para a vida. 🌳",
    "Empreender com propósito é construir algo que importa de verdade. 💚",
];
$frase_hoje = $frases[date('z') % count($frases)];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão - Tribo Verde</title>
    <link rel="icon" href="img/favicon-admin.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; -webkit-tap-highlight-color: transparent; }
        .admin-container { background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
        .header-admin { background: linear-gradient(135deg, #4a8c65 0%, #3a7055 100%); color: white; padding: 30px; border-radius: 15px 15px 0 0; margin: -40px -40px 30px -40px; }
        .table-responsive { max-height: 600px; overflow-y: auto; overflow-x: hidden; }
        .badge-atividade { font-size: 0.85rem; padding: 6px 10px; }
        .btn-detalhes { transition: all 0.3s; }
        .btn-detalhes:hover { transform: scale(1.1); }
        .ativ-item:last-child { border-bottom: none !important; }
        .ativ-header { transition: background 0.2s ease; -webkit-tap-highlight-color: transparent; }
        .ativ-header:hover { background: #f8fffe; }
        mark { background: #fff3cd; padding: 0 2px; border-radius: 3px; }
        .toast-admin { position: fixed; top: 100px; right: -400px; width: 320px; z-index: 9999; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); padding: 18px 20px; display: flex; align-items: center; gap: 14px; animation: toastIn 0.5s ease-out forwards; }
        .toast-admin.hide { animation: toastOut 0.5s ease-in forwards; }
        @keyframes toastIn { to { right: 30px; } }
        @keyframes toastOut { to { right: -400px; opacity: 0; } }
        .info-label { font-weight: 600; color: #6c757d; font-size: .85rem; margin-bottom: 4px; }
        .info-value { font-size: 1rem; color: #212529; }
        .info-row { padding: 10px 0; border-bottom: 1px solid #e9ecef; }
        .section-header { background: #f8fffe; border-left: 4px solid #4a8c65; padding: 8px 12px; border-radius: 0 6px 6px 0; margin-bottom: 10px; color: #4a8c65; font-weight: bold; }
        .mensagem-box { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #4a8c65; }
        @media (max-width: 768px) {
            body { padding: 15px 0; }
            .admin-container { padding: 20px; border-radius: 12px; }
            .header-admin { margin: -20px -20px 20px -20px; padding: 20px; }
            .header-admin .d-flex { flex-direction: column; align-items: flex-start !important; gap: 15px; }
            .header-admin a { width: 100%; }
            * { -webkit-tap-highlight-color: transparent !important; }
            .table-responsive { overflow-x: hidden !important; }
            #tabela-inscricoes thead { display: none; }
            #tabela-inscricoes tbody tr.linha-inscricao { display: flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f0f0f0 !important; gap: 10px; }
            .table-hover > tbody > tr:hover > *, .table-hover > tbody > tr:active > * { --bs-table-bg-state: transparent !important; background-color: transparent !important; box-shadow: none !important; }
            #tabela-inscricoes td { display: none !important; }
            #tabela-inscricoes td:nth-child(2), #tabela-inscricoes td:last-child { display: block !important; }
            #tabela-inscricoes td:nth-child(2) { flex: 1; min-width: 0; }
            #tabela-inscricoes td:last-child { display: flex !important; gap: 6px; flex-shrink: 0; }
            #tabela-inscricoes td:nth-child(2) .fw-semibold { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .toast-admin { width: 90%; right: -100%; }
            @keyframes toastIn { to { right: 5%; } }
        }
    </style>
</head>
<body>
<div class="container">
<div class="admin-container">

    <!-- Header -->
    <div class="header-admin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-2"><i class="fas fa-users-cog me-3"></i>Painel de Administração</h1>
                <p class="mb-0 opacity-75">Gestão de Inscrições - Tribo Verde</p>
            </div>
            <a href="index.php" class="btn btn-light btn-lg"><i class="fas fa-arrow-left me-2"></i>Voltar ao Site</a>
        </div>
    </div>

    <!-- Frase do Dia -->
    <div class="alert border-0 mb-4 p-4" style="background:linear-gradient(135deg,#f0fff4,#e6f7ed);border-left:5px solid #4a8c65 !important;border-radius:12px;">
        <div class="d-flex align-items-center">
            <span style="font-size:2rem;margin-right:15px;">🌿</span>
            <p class="mb-0 fw-semibold" style="color:#2b3319;font-size:1.05rem;"><?php echo $frase_hoje; ?></p>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="card border-0 shadow-sm mb-4" style="border-left:5px solid #4a8c65 !important;">
        <div class="card-body">
            <h5 class="fw-bold mb-3" style="color:#2b3319;"><i class="fas fa-chart-bar me-2 text-success"></i>Resumo do Painel</h5>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><i class="fas fa-clipboard-check text-success me-2"></i>Inscrições total</span>
                <span class="fw-bold fs-5"><?php echo $total; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><i class="fas fa-calendar-check me-2" style="color:#20c997;"></i>Inscrições este mês <small class="text-muted">(<?php echo date('F Y'); ?>)</small></span>
                <span class="fw-bold fs-5"><?php echo $total_mes; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><i class="fas fa-birthday-cake me-2" style="color:#e83e8c;"></i>Aniversários a aguardar pagamento</span>
                <span class="fw-bold fs-5 text-warning"><?php echo $total_aniv_pendente; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><i class="fas fa-sun me-2" style="color:#f39c12;"></i>Pedidos Pausas Lectivas pendentes</span>
                <span class="fw-bold fs-5" style="color:#f39c12;"><?php echo $total_pausas_pendentes; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <span><i class="fas fa-calendar-day me-2" style="color:#6f42c1;"></i>Visitantes hoje <small class="text-muted">(<?php echo date('d/m/Y'); ?>)</small></span>
                <span class="fw-bold fs-5"><?php echo $hoje_visitas; ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2">
                <span><i class="fas fa-chart-line me-2" style="color:#fd7e14;"></i>Visitantes este mês <small class="text-muted">(<?php echo date('F Y'); ?>)</small></span>
                <span class="fw-bold fs-5"><?php echo $mes_visitas; ?> <small class="text-muted fw-normal" style="font-size:0.7rem;">*inclui bots</small></span>
            </div>
        </div>
    </div>

    <!-- Vagas -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Vagas por Atividade — <?php echo date('F Y'); ?></h4>
        </div>
        <div class="card-body p-0">
            <form method="POST" id="form-vagas">
                <input type="hidden" name="atualizar_vagas" value="1">
                <?php foreach ($lista_ativs as $i => $a):
                    $v       = (int)$a['vagas_disponiveis'];
                    $cor     = $v === 0 ? 'danger' : ($v <= 3 ? 'warning' : 'success');
                    $cor_hex = $v === 0 ? '#dc3545' : ($v <= 3 ? '#ffc107' : '#4a8c65');
                    $perc    = $a['vagas_mes'] > 0 ? min(round(($a['inscritos_mes'] / $a['vagas_mes']) * 100), 100) : 0;
                ?>
                <div class="ativ-item border-bottom">
                    <div class="ativ-header d-flex align-items-center gap-3 px-3 py-3" onclick="toggleAtiv(<?php echo $i; ?>)" style="cursor:pointer;user-select:none;">
                        <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $cor_hex; ?>;flex-shrink:0;"></div>
                        <div class="flex-grow-1 fw-semibold" style="color:#2b3319;font-size:0.95rem;"><?php echo htmlspecialchars($a['nome']); ?></div>
                        <div class="d-flex align-items-center gap-2 me-1">
                            <span class="text-muted" style="font-size:0.78rem;"><i class="fas fa-users" style="font-size:0.7rem;"></i> <?php echo $a['inscritos_mes']; ?>/<?php echo $a['vagas_mes']; ?></span>
                            <span class="badge bg-<?php echo $cor; ?> rounded-pill" style="font-size:0.75rem;min-width:28px;"><?php echo $v; ?></span>
                        </div>
                        <i class="fas fa-chevron-down text-muted ativ-chevron-<?php echo $i; ?>" style="font-size:0.75rem;transition:transform .25s;flex-shrink:0;"></i>
                    </div>
                    <div style="height:3px;background:#e9ecef;"><div style="height:3px;width:<?php echo $perc; ?>%;background:<?php echo $cor_hex; ?>;transition:width .4s;"></div></div>
                    <div class="ativ-body-<?php echo $i; ?>" style="display:none;background:#f8fffe;">
                        <div class="p-3">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold mb-1" style="color:#4a8c65;"><i class="fas fa-users me-1"></i>Total vagas</label>
                                    <input type="number" name="vagas[<?php echo $a['id']; ?>]" value="<?php echo $a['vagas_mes']; ?>" min="0" max="999" class="form-control form-control-sm text-center fw-bold" style="font-size:1.1rem;height:44px;">
                                </div>
                                <div class="col-6 d-flex flex-column justify-content-end pb-1">
                                    <div class="d-flex justify-content-between mb-1"><small class="text-muted">Inscritos</small><small class="fw-bold text-secondary"><?php echo $a['inscritos_mes']; ?></small></div>
                                    <div class="d-flex justify-content-between"><small class="text-muted">Disponíveis</small><small class="fw-bold text-<?php echo $cor; ?>"><?php echo $v; ?></small></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold mb-1" style="color:#4a8c65;"><i class="fas fa-envelope me-1"></i>Email "Mais Informações"</label>
                                    <textarea name="info_email[<?php echo $a['id']; ?>]" rows="3" class="form-control form-control-sm" placeholder="Texto enviado quando alguém pede mais informações..."><?php echo htmlspecialchars($a['info_email'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="p-3">
                    <button type="submit" class="btn btn-success w-100" style="height:48px;font-size:1rem;"><i class="fas fa-save me-2"></i>Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pesquisa -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold"><i class="fas fa-search me-2 text-success"></i>Pesquisar</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="pesquisa-live" class="form-control border-start-0 ps-0" placeholder="Nome, email, telefone, ID..." autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" onclick="limparPesquisa()"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold"><i class="fas fa-filter me-2 text-success"></i>Atividade</label>
                    <select id="filtro-atividade-live" class="form-select form-select-lg">
                        <option value="">Todas as atividades</option>
                        <?php $atividades_filtro = $pdo->query("SELECT * FROM atividades ORDER BY nome ASC");
                        while ($ativ = $atividades_filtro->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($ativ['nome'])); ?>"><?php echo htmlspecialchars($ativ['nome']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 text-center">
                    <div class="p-2 rounded" style="background:#e6f7ed;">
                        <div class="fw-bold fs-3" style="color:#4a8c65;" id="contador-resultados"><?php echo $total; ?></div>
                        <small class="text-muted">resultado(s)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Inscrições -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Inscrições Recebidas (<?php echo $total; ?>)</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x:hidden;">
                <table class="table table-hover mb-0" id="tabela-inscricoes">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="d-none d-md-table-cell" style="width:60px;">ID</th>
                            <th>Responsável</th>
                            <th class="d-none d-md-table-cell">Criança</th>
                            <th class="d-none d-lg-table-cell">Data Nasc.</th>
                            <th class="d-none d-md-table-cell">Email</th>
                            <th class="d-none d-lg-table-cell">Telefone</th>
                            <th class="d-none d-md-table-cell">Atividade</th>
                            <th class="d-none d-lg-table-cell">Inscrição</th>
                            <th style="width:90px;white-space:nowrap;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-body">
                        <?php if ($total > 0): ?>
                            <?php foreach ($todas_inscricoes as $inscricao):
                                $dn = $inscricao['data_nascimento'] ? (new DateTime($inscricao['data_nascimento']))->format('d/m/Y') : 'N/A';
                                $di = (new DateTime($inscricao['data_inscricao']))->format('d/m/Y H:i');
                            ?>
                            <tr class="linha-inscricao"
                                data-search="<?php echo strtolower($inscricao['id'].' '.($inscricao['nome_responsavel']??'').' '.($inscricao['nome_crianca']??'').' '.($inscricao['email']??'').' '.($inscricao['telefone']??'')); ?>"
                                data-atividade="<?php echo strtolower(htmlspecialchars($inscricao['atividade_nome'] ?? '')); ?>">
                                <td class="fw-bold d-none d-md-table-cell" style="color:#4a8c65;">#<?php echo $inscricao['id']; ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:0.9rem;"><?php echo htmlspecialchars($inscricao['nome_responsavel'] ?? 'N/A'); ?></div>
                                    <div class="d-md-none mt-1">
                                        <small class="text-muted"><i class="fas fa-child me-1"></i><?php echo htmlspecialchars($inscricao['nome_crianca'] ?? ''); ?></small><br>
                                        <span class="badge bg-success mt-1" style="font-size:0.7rem;"><?php echo htmlspecialchars($inscricao['atividade_nome'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($inscricao['nome_crianca'] ?? 'N/A'); ?></td>
                                <td class="d-none d-lg-table-cell"><?php echo $dn; ?></td>
                                <td class="d-none d-md-table-cell">
                                    <a href="mailto:<?php echo htmlspecialchars($inscricao['email']); ?>" class="text-decoration-none text-truncate d-block" style="max-width:180px;">
                                        <i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($inscricao['email']); ?>
                                    </a>
                                </td>
                                <td class="d-none d-lg-table-cell" style="white-space:nowrap;">
                                    <a href="tel:<?php echo htmlspecialchars($inscricao['telefone'] ?? ''); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($inscricao['telefone'] ?? 'N/A'); ?>
                                    </a>
                                </td>
                                <td class="d-none d-md-table-cell"><span class="badge badge-atividade bg-success"><?php echo htmlspecialchars($inscricao['atividade_nome'] ?? 'N/A'); ?></span></td>
                                <td class="d-none d-lg-table-cell" style="font-size:0.85rem;color:#6c757d;"><?php echo $di; ?></td>
                                <td style="white-space:nowrap;">
                                    <button class="btn btn-sm btn-primary btn-detalhes" onclick="verDetalhes(<?php echo $inscricao['id']; ?>)"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-sm btn-danger ms-1" onclick="confirmarDelete(<?php echo $inscricao['id']; ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Nenhuma inscrição recebida ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="sem-resultados" class="text-center py-5 text-muted" style="display:none;">
                <i class="fas fa-search fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
                <p class="mb-1">Nenhuma inscrição encontrada para</p>
                <p class="fw-bold" id="termo-pesquisa-display" style="color:#4a8c65;"></p>
                <button class="btn btn-sm btn-outline-success mt-2" onclick="limparPesquisa()"><i class="fas fa-times me-1"></i>Limpar pesquisa</button>
            </div>
        </div>
    </div>

    <!-- ══ RESERVAS DE ANIVERSÁRIOS ══ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:linear-gradient(135deg,#e83e8c,#c2185b);">
            <h4 class="mb-0">
                <i class="fas fa-birthday-cake me-2"></i>Reservas de Aniversários (<?php echo $total_aniv; ?>)
                <?php if ($total_aniv_pendente > 0): ?>
                <span class="badge bg-warning text-dark ms-2" style="font-size:0.75rem;"><?php echo $total_aniv_pendente; ?> pendente(s)</span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="card-body p-0">
            <?php if ($total_aniv > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabela-aniversarios">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Responsável</th>
                            <th>Aniversariante</th>
                            <th>Data Festa</th>
                            <th>Período</th>
                            <th>Crianças</th>
                            <th>Estado</th>
                            <th style="width:120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($todas_reservas as $r):
                        $data_f        = date('d/m/Y', strtotime($r['data_festa']));
                        $periodo_label = $r['periodo'] === 'manha' ? '☀️ Manhã' : '🌙 Tarde';
                        $limite_fmt    = $r['data_limite_pagamento'] ? date('d/m/Y H:i', strtotime($r['data_limite_pagamento'])) : 'N/A';
                        switch ($r['estado']) {
                            case 'pago':      $badge = 'bg-success'; $label = '✅ Pago'; break;
                            case 'cancelado': $badge = 'bg-danger';  $label = '❌ Cancelado'; break;
                            default:          $badge = 'bg-warning text-dark'; $label = '⏳ Pendente'; break;
                        }
                    ?>
                    <tr>
                        <td class="fw-bold" style="color:#e83e8c;">#<?php echo $r['id']; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($r['nome_responsavel']); ?></div>
                            <small><a href="mailto:<?php echo htmlspecialchars($r['email']); ?>" class="text-decoration-none text-muted"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($r['email']); ?></a></small><br>
                            <small><a href="tel:<?php echo htmlspecialchars($r['telefone']); ?>" class="text-decoration-none text-muted"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($r['telefone']); ?></a></small>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($r['nome_aniversariante']); ?></div>
                            <small class="text-muted"><?php echo $r['idade_aniversariante']; ?> anos</small>
                        </td>
                        <td>
                            <div class="fw-semibold"><?php echo $data_f; ?></div>
                            <?php if ($r['estado'] === 'pendente'): ?>
                            <small class="text-danger"><i class="fas fa-clock me-1"></i>Limite: <?php echo $limite_fmt; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $periodo_label; ?></td>
                        <td class="text-center fw-bold"><?php echo $r['num_criancas']; ?></td>
                        <td><span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo $label; ?></span></td>
                        <td>
                            <div class="d-flex flex-column gap-1" style="min-width:100px;">
                                <button class="btn btn-sm btn-outline-secondary" onclick="verDetalhesAniv(<?php echo $r['id']; ?>)"><i class="fas fa-eye me-1"></i>Ver</button>
                                <?php if ($r['estado'] === 'pendente'): ?>
                                <button class="btn btn-sm btn-success" onclick="confirmarPagamento(<?php echo $r['id']; ?>, '<?php echo htmlspecialchars($r['nome_responsavel']); ?>')"><i class="fas fa-check me-1"></i>Confirmar</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelarReserva(<?php echo $r['id']; ?>)"><i class="fas fa-times me-1"></i>Cancelar</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="apagarReserva(<?php echo $r['id']; ?>)"><i class="fas fa-trash me-1"></i>Apagar</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-birthday-cake fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
                <p>Nenhuma reserva de aniversário ainda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ PAUSAS LECTIVAS ══ -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header text-white" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
            <h4 class="mb-0">
                <i class="fas fa-sun me-2"></i>Pausas Lectivas <?php echo $ano_ativo_pausas; ?>
                <?php if ($total_pausas_pendentes > 0): ?>
                <span class="badge bg-warning text-dark ms-2" style="font-size:0.75rem;"><?php echo $total_pausas_pendentes; ?> pendente(s)</span>
                <?php endif; ?>
            </h4>
        </div>
        <div class="card-body">

            <!-- Configuração -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 bg-light p-3">
                        <h6 class="fw-bold mb-3"><i class="fas fa-cog me-2" style="color:#f39c12;"></i>Ano Activo</h6>
                        <form method="POST" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="atualizar_ano_pausas" value="1">
                            <input type="number" name="ano_pausas" value="<?php echo $ano_ativo_pausas; ?>" min="2024" max="2030"
                                   class="form-control form-control-sm text-center fw-bold" style="width:90px;font-size:1.1rem;">
                            <button type="submit" class="btn btn-sm btn-warning fw-bold"><i class="fas fa-save me-1"></i>Guardar</button>
                        </form>
                        <small class="text-muted mt-2">O site mostra o calendário do ano activo.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light p-3">
                        <h6 class="fw-bold mb-3"><i class="fas fa-calendar-plus me-2" style="color:#f39c12;"></i>Gerar Dias Úteis</h6>
                        <form method="POST">
                            <input type="hidden" name="gerar_dias_pausas" value="1">
                            <button type="submit" class="btn btn-sm btn-outline-warning fw-bold"
                                    onclick="return confirm('Gerar todos os dias úteis de Jun/Jul/Ago de <?php echo $ano_ativo_pausas; ?>?\n\nDias já existentes não serão alterados.')">
                                <i class="fas fa-magic me-1"></i>Gerar Jun/Jul/Ago <?php echo $ano_ativo_pausas; ?>
                            </button>
                        </form>
                        <small class="text-muted mt-2">Seg–Qui: 30 vagas &nbsp;·&nbsp; Sex: 35 vagas. Dias existentes não são alterados.</small>
                    </div>
                </div>
            </div>

            <!-- Calendário admin -->
            <h6 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2" style="color:#f39c12;"></i>Calendário <?php echo $ano_ativo_pausas; ?></h6>
            <?php $meses_nomes = [6=>'Junho', 7=>'Julho', 8=>'Agosto']; ?>
            <div class="row g-3 mb-4">
            <?php foreach ([6,7,8] as $mes):
                $nome_mes    = $meses_nomes[$mes];
                $total_dias  = cal_days_in_month(CAL_GREGORIAN, $mes, $ano_ativo_pausas);
                $primeiro_dia = date('N', strtotime("$ano_ativo_pausas-" . str_pad($mes,2,'0',STR_PAD_LEFT) . "-01"));
            ?>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header text-white text-center fw-bold" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                        <?php echo $nome_mes; ?> <?php echo $ano_ativo_pausas; ?>
                    </div>
                    <div class="card-body p-2">
                        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:2px;margin-bottom:4px;">
                            <?php foreach (['Seg','Ter','Qua','Qui','Sex'] as $ds): ?>
                            <div style="text-align:center;font-size:0.7rem;font-weight:700;color:#6c757d;padding:4px 0;"><?php echo $ds; ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:2px;">
                            <?php
                            $offset = $primeiro_dia - 1;
                            for ($i = 0; $i < $offset; $i++) echo '<div></div>';
                            for ($d = 1; $d <= $total_dias; $d++):
                                $data_str = "$ano_ativo_pausas-" . str_pad($mes,2,'0',STR_PAD_LEFT) . "-" . str_pad($d,2,'0',STR_PAD_LEFT);
                                $dia_sem  = date('N', strtotime($data_str));
                                if ($dia_sem > 5) continue;
                                $info = $dias_admin[$data_str] ?? null;
                                if (!$info) { $bg = '#f0f0f0'; $cor = '#ccc'; $title = 'Não configurado'; }
                                elseif ($info['encerrado']) { $bg = '#f0f0f0'; $cor = '#888'; $title = 'Encerrado'; }
                                elseif ($info['vagas_ocupadas'] >= $info['vagas_total']) { $bg = '#fdecea'; $cor = '#dc3545'; $title = 'Cheio'; }
                                elseif (($info['vagas_total'] - $info['vagas_ocupadas']) <= 5) { $bg = '#fff8e1'; $cor = '#856404'; $title = ($info['vagas_total'] - $info['vagas_ocupadas']) . ' vagas'; }
                                else { $bg = '#f0faf4'; $cor = '#2d6a4f'; $title = ($info['vagas_total'] - $info['vagas_ocupadas']) . ' vagas'; }
                            ?>
                            <div onclick="abrirModalDia('<?php echo $data_str; ?>', <?php echo $info ? $info['vagas_total'] : 35; ?>, <?php echo $info ? (int)$info['encerrado'] : 0; ?>, <?php echo $info ? $info['vagas_ocupadas'] : 0; ?>)"
                                 style="background:<?php echo $bg; ?>;color:<?php echo $cor; ?>;border-radius:6px;padding:6px 2px;text-align:center;cursor:pointer;font-size:0.8rem;font-weight:600;transition:all 0.2s;"
                                 title="<?php echo $title; ?>"
                                 onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">
                                <?php echo $d; ?>
                                <?php if ($info && $info['vagas_ocupadas'] > 0): ?>
                                <div style="font-size:0.55rem;"><?php echo $info['vagas_ocupadas']; ?>/<?php echo $info['vagas_total']; ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;font-size:0.75rem;">
                            <span><span style="color:#2d6a4f">●</span> Disponível</span>
                            <span><span style="color:#856404">●</span> Poucas vagas</span>
                            <span><span style="color:#dc3545">●</span> Cheio</span>
                            <span><span style="color:#888">●</span> Encerrado</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Pedidos -->
            <h6 class="fw-bold mb-3"><i class="fas fa-clipboard-list me-2" style="color:#f39c12;"></i>Pedidos de Inscrição (<?php echo $total_pedidos; ?>)</h6>
            <?php if ($total_pedidos > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th>Responsável</th>
                            <th class="d-none d-md-table-cell">Criança</th>
                            <th class="d-none d-md-table-cell">Dias</th>
                            <th>Estado</th>
                            <th style="width:130px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pedidos_pausas as $p):
                        $dias_p   = array_filter(array_map('trim', explode(',', $p['dias_pedidos'])));
                        sort($dias_p);
                        $primeiro = $dias_p ? date('d/m/Y', strtotime(reset($dias_p))) : 'N/A';
                        $ultimo   = $dias_p ? date('d/m/Y', strtotime(end($dias_p))) : 'N/A';
                        switch ($p['estado']) {
                            case 'confirmado': $badge = 'bg-success'; $label = '✅ Confirmado'; break;
                            case 'cancelado':  $badge = 'bg-danger';  $label = '❌ Cancelado'; break;
                            default:           $badge = 'bg-warning text-dark'; $label = '⏳ Pendente'; break;
                        }
                    ?>
                    <tr>
                        <td class="fw-bold" style="color:#f39c12;">#<?php echo $p['id']; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($p['nome_responsavel']); ?></div>
                            <small><a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" class="text-muted text-decoration-none"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($p['email']); ?></a></small><br>
                            <small><a href="tel:<?php echo htmlspecialchars($p['telefone']); ?>" class="text-muted text-decoration-none"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($p['telefone']); ?></a></small>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="fw-semibold"><?php echo htmlspecialchars($p['nome_crianca']); ?></div>
                            <?php if (!empty($p['cuidados_saude'])): ?>
                            <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>Cuidados especiais</small>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <strong><?php echo count($dias_p); ?> dia(s)</strong><br>
                            <small class="text-muted"><?php echo $primeiro; ?> → <?php echo $ultimo; ?></small>
                        </td>
                        <td><span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2" style="font-size:0.75rem;"><?php echo $label; ?></span></td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <button class="btn btn-sm btn-outline-secondary" onclick="verDetalhesPausa(<?php echo $p['id']; ?>)"><i class="fas fa-eye me-1"></i>Ver</button>
                                <?php if ($p['estado'] === 'pendente'): ?>
                                <button class="btn btn-sm btn-success" onclick="confirmarPausa(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nome_responsavel']); ?>')"><i class="fas fa-check me-1"></i>Confirmar</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelarPausa(<?php echo $p['id']; ?>)"><i class="fas fa-times me-1"></i>Cancelar</button>
                                <?php elseif ($p['estado'] === 'confirmado'): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="cancelarPausa(<?php echo $p['id']; ?>)"><i class="fas fa-times me-1"></i>Cancelar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-sun fa-3x mb-3 d-block" style="color:#dee2e6;"></i>
                <p>Nenhum pedido de inscrição ainda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Toasts -->
<?php if (isset($_GET['vagas_ok'])): ?>
<div class="toast-admin" id="toastAdmin"><span style="font-size:2rem;">✅</span><div><div class="fw-bold">Guardado com sucesso!</div><div style="font-size:0.85rem;opacity:.85;">As alterações foram guardadas.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['aniv_confirmado'])): ?>
<div class="toast-admin" id="toastAdmin"><span style="font-size:2rem;">🎉</span><div><div class="fw-bold">Pagamento confirmado!</div><div style="font-size:0.85rem;opacity:.85;">Email enviado ao cliente.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['aniv_cancelado'])): ?>
<div class="toast-admin" id="toastAdmin" style="background:linear-gradient(135deg,#dc3545,#c82333);"><span style="font-size:2rem;">❌</span><div><div class="fw-bold">Reserva cancelada.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['aniv_apagado'])): ?>
<div class="toast-admin" id="toastAdmin" style="background:linear-gradient(135deg,#6c757d,#495057);"><span style="font-size:2rem;">🗑️</span><div><div class="fw-bold">Reserva apagada.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['pausa_confirmada'])): ?>
<div class="toast-admin" id="toastAdmin"><span style="font-size:2rem;">✅</span><div><div class="fw-bold">Inscrição confirmada!</div><div style="font-size:0.85rem;opacity:.85;">Email enviado e vagas descontadas.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['pausa_cancelada'])): ?>
<div class="toast-admin" id="toastAdmin" style="background:linear-gradient(135deg,#dc3545,#c82333);"><span style="font-size:2rem;">❌</span><div><div class="fw-bold">Pedido cancelado.</div><div style="font-size:0.85rem;opacity:.85;">Vagas devolvidas.</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['dias_gerados'])): ?>
<div class="toast-admin" id="toastAdmin" style="background:linear-gradient(135deg,#f39c12,#e67e22);"><span style="font-size:2rem;">📅</span><div><div class="fw-bold">Dias gerados com sucesso!</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['ano_pausas_ok'])): ?>
<div class="toast-admin" id="toastAdmin" style="background:linear-gradient(135deg,#f39c12,#e67e22);"><span style="font-size:2rem;">🔄</span><div><div class="fw-bold">Ano activo actualizado!</div></div></div>
<?php endif; ?>
<?php if (isset($_GET['dia_ok'])): ?>
<div class="toast-admin" id="toastAdmin"><span style="font-size:2rem;">✅</span><div><div class="fw-bold">Dia actualizado!</div></div></div>
<?php endif; ?>

<!-- Modal Inscrições -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-user me-2"></i>Ficha de Inscrição</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCorpo">
                <div class="text-center py-5"><div class="spinner-border text-success" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aniversários -->
<div class="modal fade" id="modalDetalhesAniv" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#e83e8c,#c2185b);">
                <h5 class="modal-title"><i class="fas fa-birthday-cake me-2"></i>Detalhes da Reserva</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCorpoAniv">
                <div class="text-center py-5"><div class="spinner-border" style="color:#e83e8c;" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausas -->
<div class="modal fade" id="modalDetalhesPausa" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                <h5 class="modal-title"><i class="fas fa-sun me-2"></i>Detalhes do Pedido</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalCorpoPausa">
                <div class="text-center py-5"><div class="spinner-border" style="color:#f39c12;" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Dia -->
<div class="modal fade" id="modalDiaPausa" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#f39c12,#e67e22);border-radius:12px 12px 0 0;">
                <h5 class="modal-title"><i class="fas fa-calendar-day me-2"></i>Gerir Dia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="atualizar_dia_pausa" value="1">
                <input type="hidden" name="data_dia" id="modal_data_dia">
                <div class="modal-body p-4">
                    <p class="fw-bold text-center fs-5" id="modal_dia_label" style="color:#f39c12;"></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total de vagas neste dia</label>
                        <input type="number" name="vagas_total" id="modal_vagas_total" class="form-control form-control-lg text-center fw-bold" min="0" max="35" style="font-size:1.3rem;">
                        <div class="form-text" id="modal_vagas_info"></div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="encerrado" id="modal_encerrado" style="width:3em;height:1.5em;">
                        <label class="form-check-label fw-bold ms-2" for="modal_encerrado">Dia Encerrado</label>
                        <div class="form-text">Quando encerrado, aparece como indisponível no site.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold"><i class="fas fa-save me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

</div><!-- fim admin-container -->
</div><!-- fim container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalhes(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    const corpo = document.getElementById('modalCorpo');
    corpo.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-success" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>';
    modal.show();
    fetch('detalhes_inscricao.php?id=' + id + '&modal=1').then(r => r.text()).then(html => { corpo.innerHTML = html; }).catch(() => { corpo.innerHTML = '<div class="alert alert-danger m-3">Erro ao carregar.</div>'; });
}
function confirmarDelete(id) {
    if (confirm('⚠️ Tens a certeza que queres APAGAR esta inscrição?\n\nEsta ação não pode ser desfeita!')) {
        fetch('delete_inscricao.php?id=' + id + '&ajax=1')
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    const linhas = document.querySelectorAll('#tabela-inscricoes tbody tr');
                    linhas.forEach(tr => {
                        if (tr.dataset.search && tr.dataset.search.startsWith(id + ' ')) {
                            tr.style.transition = 'opacity 0.3s';
                            tr.style.opacity = '0';
                            setTimeout(() => {
                                tr.remove();
                                const contador = document.getElementById('contador-resultados');
                                if (contador) contador.textContent = parseInt(contador.textContent) - 1;
                                const restantes = document.querySelectorAll('#tabela-inscricoes tbody tr');
                                if (restantes.length === 0) {
                                    document.querySelector('#tabela-inscricoes tbody').innerHTML =
                                        '<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-3x mb-3 d-block"></i>Nenhuma inscrição recebida ainda.</td></tr>';
                                }
                            }, 300);
                        }
                    });
                }
            });
    }
}
function verDetalhesAniv(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesAniv'));
    const corpo = document.getElementById('modalCorpoAniv');
    corpo.innerHTML = '<div class="text-center py-5"><div class="spinner-border" style="color:#e83e8c;" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>';
    modal.show();
    fetch('detalhes_aniversario.php?id=' + id + '&modal=1').then(r => r.text()).then(html => { corpo.innerHTML = html; }).catch(() => { corpo.innerHTML = '<div class="alert alert-danger m-3">Erro ao carregar.</div>'; });
}
function confirmarPagamento(id, nome) {
    if (confirm('✅ Confirmar pagamento de ' + nome + '?\n\nSerá enviado email de confirmação ao cliente.')) window.location.href = 'confirmar_aniversario.php?id=' + id;
}
function cancelarReserva(id) {
    if (confirm('❌ Cancelar esta reserva?')) window.location.href = 'cancelar_aniversario.php?id=' + id;
}
function apagarReserva(id) {
    if (confirm('🗑️ Apagar permanentemente esta reserva?\n\nEsta ação não pode ser desfeita!')) {
        fetch('apagar_aniversario.php?id=' + id + '&ajax=1')
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    const linhas = document.querySelectorAll('#tabela-aniversarios tbody tr');
                    linhas.forEach(tr => {
                        const cell = tr.querySelector('td');
                        if (cell && cell.textContent.replace(/\s/g, '') === '#' + id) {
                            tr.style.transition = 'opacity 0.3s';
                            tr.style.opacity = '0';
                            setTimeout(() => {
                                tr.remove();
                                const restantes = document.querySelectorAll('#tabela-aniversarios tbody tr');
                                if (restantes.length === 0) {
                                    document.querySelector('#tabela-aniversarios').closest('.table-responsive').innerHTML =
                                        '<div class="text-center py-5 text-muted"><i class="fas fa-birthday-cake fa-3x mb-3 d-block" style="color:#dee2e6;"></i><p>Nenhuma reserva de aniversário ainda.</p></div>';
                                }
                            }, 300);
                        }
                    });
                }
            });
    }
}
function toggleAtiv(i) {
    const body    = document.querySelector('.ativ-body-' + i);
    const chevron = document.querySelector('.ativ-chevron-' + i);
    const isOpen  = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
function verDetalhesPausa(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesPausa'));
    const corpo = document.getElementById('modalCorpoPausa');
    corpo.innerHTML = '<div class="text-center py-5"><div class="spinner-border" style="color:#f39c12;" role="status"></div><p class="mt-3 text-muted">A carregar...</p></div>';
    modal.show();
    fetch('detalhes_pausa.php?id=' + id + '&modal=1').then(r => r.text()).then(html => { corpo.innerHTML = html; }).catch(() => { corpo.innerHTML = '<div class="alert alert-danger m-3">Erro ao carregar.</div>'; });
}
function confirmarPausa(id, nome) {
    if (confirm('✅ Confirmar inscrição de ' + nome + '?\n\nSerá enviado email de confirmação e as vagas serão descontadas.')) window.location.href = 'confirmar_pausa.php?id=' + id;
}
function cancelarPausa(id) {
    if (confirm('❌ Cancelar este pedido?\n\nSe estava confirmado, as vagas serão devolvidas.')) window.location.href = 'cancelar_pausa.php?id=' + id;
}
function abrirModalDia(data, vagasTotal, encerrado, vagasOcupadas) {
    const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    const dt    = new Date(data + 'T00:00:00');
    document.getElementById('modal_data_dia').value    = data;
    document.getElementById('modal_dia_label').textContent = dt.getDate() + ' de ' + meses[dt.getMonth()] + ' ' + dt.getFullYear();
    document.getElementById('modal_vagas_total').value = vagasTotal;
    document.getElementById('modal_encerrado').checked = encerrado === 1;
    document.getElementById('modal_vagas_info').textContent = vagasOcupadas + ' vagas já ocupadas neste dia.';
    new bootstrap.Modal(document.getElementById('modalDiaPausa')).show();
}
document.getElementById('modalCorpo').addEventListener('click', function(e) {
    if (e.target.closest('#btn-imprimir-ficha')) {
        const conteudo = document.getElementById('modalCorpo').innerHTML;
        const janela = window.open('', '_blank', 'width=800,height=700');
        janela.document.write('<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Ficha</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><style>body{background:white;padding:30px;}.info-label{font-weight:600;color:#6c757d;font-size:.85rem;margin-bottom:4px;}.info-value{font-size:1rem;color:#212529;}.info-row{padding:10px 0;border-bottom:1px solid #e9ecef;}.section-header{background:#f8fffe;border-left:4px solid #4a8c65;padding:8px 12px;border-radius:0 6px 6px 0;margin-bottom:10px;color:#4a8c65;font-weight:bold;}.mensagem-box{background:#f8f9fa;padding:15px;border-radius:8px;border-left:4px solid #4a8c65;}.d-flex.gap-2,button,a.btn{display:none!important;}</style></head><body>' + conteudo + '<script>window.onload=function(){window.print();window.onafterprint=function(){window.close();};};<\/script></body></html>');
        janela.document.close();
    }
});
document.getElementById('modalCorpoPausa').addEventListener('click', function(e) {
    const btnC = e.target.closest('[onclick^="confirmarPausa"]');
    const btnX = e.target.closest('[onclick^="cancelarPausa"]');
    if (btnC) eval(btnC.getAttribute('onclick'));
    if (btnX) eval(btnX.getAttribute('onclick'));
});
const toastAdmin = document.getElementById('toastAdmin');
if (toastAdmin) {
    setTimeout(() => { toastAdmin.classList.add('hide'); setTimeout(() => toastAdmin.remove(), 500); }, 4000);
    if (window.history.replaceState) {
        window.history.replaceState({}, document.title, 'admin.php');
    }
}
const inputPesquisa = document.getElementById('pesquisa-live');
const selectAtivFiltro = document.getElementById('filtro-atividade-live');
const linhas        = document.querySelectorAll('.linha-inscricao');
const semResultados = document.getElementById('sem-resultados');
const contador      = document.getElementById('contador-resultados');
const termoDisplay  = document.getElementById('termo-pesquisa-display');
function filtrarTabela() {
    const termo = inputPesquisa.value.toLowerCase().trim();
    const ativ  = selectAtivFiltro.value.toLowerCase();
    let visiveis = 0;
    linhas.forEach(tr => {
        const ok = (!termo || tr.dataset.search.includes(termo)) && (!ativ || tr.dataset.atividade === ativ);
        tr.style.display = ok ? '' : 'none';
        if (ok) visiveis++;
    });
    contador.textContent = visiveis;
    if (visiveis === 0 && linhas.length > 0) { semResultados.style.display = 'block'; termoDisplay.textContent = '"' + (inputPesquisa.value || selectAtivFiltro.options[selectAtivFiltro.selectedIndex].text) + '"'; }
    else { semResultados.style.display = 'none'; }
}
function limparPesquisa() { inputPesquisa.value = ''; selectAtivFiltro.value = ''; filtrarTabela(); inputPesquisa.focus({ preventScroll: true }); }
inputPesquisa.addEventListener('input', filtrarTabela);
selectAtivFiltro.addEventListener('change', filtrarTabela);
inputPesquisa.focus({ preventScroll: true });
</script>
</body>
</html>