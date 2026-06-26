<?php 
include 'includes/header.php'; 
include 'config.php';

$atividade_selecionada = $_GET['atividade_id'] ?? null;
$stmt = $pdo->query("SELECT * FROM atividades ORDER BY nome ASC");
$atividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_tipos = $pdo->query("SELECT id, tipo FROM atividades WHERE tipo IS NOT NULL");
$ids_por_tipo = [];
while ($row = $stmt_tipos->fetch(PDO::FETCH_ASSOC)) {
    $ids_por_tipo[$row['tipo']] = (int)$row['id'];
}
$id_aniversarios = $ids_por_tipo['aniversario'] ?? 0;
$id_escola       = $ids_por_tipo['escola']       ?? 0;
$id_pausas       = $ids_por_tipo['pausas']       ?? 0;
$id_montessori   = $ids_por_tipo['montessori']   ?? 0;
$id_sextas       = $ids_por_tipo['sextas']       ?? 0;

$stmt_datas = $pdo->query("SELECT data_festa, periodo FROM reservas_aniversarios WHERE estado IN ('pendente','pago') AND data_festa >= CURDATE()");
$datas_ocupadas = [];
while ($row = $stmt_datas->fetch(PDO::FETCH_ASSOC)) {
    $datas_ocupadas[$row['data_festa']][] = $row['periodo'];
}

$stmt_config = $pdo->query("SELECT ano_ativo FROM pausas_letivas_config LIMIT 1");
$config_pausas = $stmt_config->fetch(PDO::FETCH_ASSOC);
$ano_pausas = $config_pausas ? (int)$config_pausas['ano_ativo'] : date('Y');

$stmt_dias = $pdo->prepare("SELECT data, vagas_total, vagas_ocupadas, encerrado FROM pausas_letivas_dias WHERE YEAR(data) = ?");
$stmt_dias->execute([$ano_pausas]);
$dias_pausas_raw = $stmt_dias->fetchAll(PDO::FETCH_ASSOC);
$dias_pausas = [];
foreach ($dias_pausas_raw as $d) {
    $dias_pausas[$d['data']] = [
        'vagas_total'    => (int)$d['vagas_total'],
        'vagas_ocupadas' => (int)$d['vagas_ocupadas'],
        'encerrado'      => (bool)$d['encerrado'],
        'disponiveis'    => max(0, (int)$d['vagas_total'] - (int)$d['vagas_ocupadas'])
    ];
}

$sucesso = isset($_GET['sucesso']);
$erro = $_GET['erro'] ?? null;
?>

<style>
.notification-card {
    position: fixed; top: 100px; right: -400px;
    width: 350px; z-index: 9999;
    animation: slideIn 0.5s ease-out forwards;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}
@keyframes slideIn { to { right: 30px; } }
@keyframes slideOut { to { right: -400px; opacity: 0; } }
.notification-card.hide { animation: slideOut 0.5s ease-in forwards; }
.notification-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; }
.notification-error { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; }
.notification-icon { font-size: 3rem; margin-bottom: 15px; animation: bounce 1s infinite; }
@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
.notification-close {
    position: absolute; top: 10px; right: 10px;
    background: rgba(255,255,255,0.3); border: none; color: white;
    font-size: 1.2rem; width: 30px; height: 30px;
    border-radius: 50%; cursor: pointer; transition: all 0.3s;
}
.notification-close:hover { background: rgba(255,255,255,0.5); transform: rotate(90deg); }
.calendario-wrapper { user-select: none; }
.calendario-header {
    display: flex; justify-content: space-between; align-items: center;
    background: linear-gradient(135deg,#4a8c65,#3a7055);
    color: white; padding: 12px 16px; border-radius: 10px 10px 0 0;
}
.calendario-header button {
    background: rgba(255,255,255,0.2); border: none; color: white;
    width: 32px; height: 32px; border-radius: 50%; font-size: 1rem;
    cursor: pointer; transition: background 0.2s;
}
.calendario-header button:hover { background: rgba(255,255,255,0.4); }
.calendario-grid {
    display: grid; grid-template-columns: repeat(7, 1fr);
    border: 1px solid #dee2e6; border-top: none;
    border-radius: 0 0 10px 10px; overflow: hidden;
}
.calendario-dia-semana {
    text-align: center; padding: 8px 4px;
    font-size: 0.75rem; font-weight: 700;
    background: #f8f9fa; color: #6c757d;
    border-bottom: 1px solid #dee2e6;
}
.calendario-dia {
    text-align: center; padding: 10px 4px; font-size: 0.9rem; cursor: default;
    border: 1px solid #f0f0f0; min-height: 44px; display: flex;
    align-items: center; justify-content: center; flex-direction: column; gap: 2px;
}
.calendario-dia.vazio { background: #fff; }
.calendario-dia.fim-semana { background: #f8f9fa; color: #ccc; }
.calendario-dia.passado { background: #f8f9fa; color: #ccc; }
.calendario-dia.disponivel { background: #f0faf4; color: #2d6a4f; cursor: pointer; font-weight: 600; transition: all 0.2s; }
.calendario-dia.disponivel:hover { background: #d4edda; transform: scale(1.05); }
.calendario-dia.disponivel.selecionado-pausas { background: #2d6a4f !important; color: white !important; border-radius: 6px; }
.calendario-dia.parcial { background: #fff8e1; color: #856404; cursor: pointer; font-weight: 600; transition: all 0.2s; }
.calendario-dia.parcial:hover { background: #ffeaa7; }
.calendario-dia.parcial.selecionado-pausas { background: #856404 !important; color: white !important; border-radius: 6px; }
.calendario-dia.cheio { background: #fdecea; color: #ccc; cursor: not-allowed; }
.calendario-dia.encerrado { background: #f0f0f0; color: #aaa; cursor: not-allowed; font-style: italic; }
.calendario-dia.fora-periodo { background: #fafafa; color: #ddd; cursor: not-allowed; }
.calendario-dia.selecionado { background: #2d6a4f !important; color: white !important; border-radius: 6px; }
.calendario-dia .dot { font-size: 0.6rem; }
.calendario-dia .vagas-label { font-size: 0.6rem; margin-top: 1px; }
.periodo-btn { border: 2px solid #dee2e6; border-radius: 8px; padding: 12px; cursor: pointer; transition: all 0.2s; text-align: center; background: white; }
.periodo-btn:hover { border-color: #4a8c65; background: #f0faf4; }
.periodo-btn.selecionado { border-color: #4a8c65; background: #4a8c65; color: white; }
.periodo-btn.ocupado { border-color: #dee2e6; background: #f8f9fa; color: #ccc; cursor: not-allowed; opacity: 0.6; }
.dias-selecionados-lista { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.dia-tag { background: #2d6a4f; color: white; border-radius: 20px; padding: 4px 12px; font-size: 0.82rem; display: flex; align-items: center; gap: 6px; }
.dia-tag button { background: none; border: none; color: white; font-size: 1rem; line-height: 1; cursor: pointer; padding: 0; }
.opcao-radio { display: flex; gap: 12px; flex-wrap: wrap; }
.opcao-radio label { border: 2px solid #dee2e6; border-radius: 8px; padding: 10px 16px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; flex: 1; min-width: 120px; }
.opcao-radio input[type=radio] { display: none; }
.opcao-radio input[type=radio]:checked + label,
.opcao-radio label:has(input[type=radio]:checked) { border-color: #4a8c65; background: #f0faf4; color: #2d6a4f; font-weight: 600; }
.opcao-radio label:hover { border-color: #4a8c65; }
@media (max-width: 768px) {
    .notification-card { width: 90%; right: -100%; }
    @keyframes slideIn { to { right: 5%; } }
    .opcao-radio label { min-width: 100%; }
}
.campos-animado { animation: fadeSlideIn 0.35s ease forwards; }
@keyframes fadeSlideIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
.campo-erro .form-control, .campo-erro .form-select, .campo-erro textarea { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.15) !important; }
.campo-erro .opcao-radio label { border-color: #dc3545 !important; }
.msg-erro-inline { color: #dc3545; font-size: 0.82rem; margin-top: 5px; display: none; }
.campo-erro .msg-erro-inline { display: block; }
.campo-ok .form-control, .campo-ok .form-select, .campo-ok textarea { border-color: #4a8c65 !important; }
#btnSubmit:disabled { opacity: 0.85; cursor: not-allowed; }
#wrapper_btn { display: block; }
#wrapper_btn .btn { display: block; width: 100%; }
</style>

<div class="container my-5" style="padding-top: 110px; padding-bottom: 60px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <?php if ($sucesso && !isset($_GET['aniversario']) && !isset($_GET['escola']) && !isset($_GET['pausas']) && !isset($_GET['montessori'])): ?>
            <div class="notification-card notification-success card border-0" id="notificationCard">
                <div class="card-body text-center p-4 position-relative">
                    <button class="notification-close" onclick="closeNotification()">×</button>
                    <div class="notification-icon"><i class="fas fa-check-circle"></i></div>
                    <h4 class="fw-bold mb-2" data-pt="Inscrição Enviada!" data-en="Registration Sent!">Inscrição Enviada!</h4>
                    <p class="mb-0" data-pt="Obrigado! Entraremos em contacto em breve." data-en="Thank you! We will be in touch soon.">Obrigado! Entraremos em contacto em breve.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($erro): ?>
            <div class="notification-card notification-error card border-0" id="notificationCard">
                <div class="card-body text-center p-4 position-relative">
                    <button class="notification-close" onclick="closeNotification()">×</button>
                    <div class="notification-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <h4 class="fw-bold mb-2" data-pt="Erro!" data-en="Error!">Erro!</h4>
                    <p class="mb-0">
                        <?php
                        $erros = [
                            'campos_vazios'  => 'Preenche todos os campos obrigatórios.',
                            'email_invalido' => 'Email inválido. Verifica e tenta novamente.',
                            'idade_invalida' => 'A criança deve ter entre 1 e 6 anos.',
                            'data_invalida'  => 'Seleciona uma data e período válidos.',
                            'data_ocupada'   => 'Essa data/período já não está disponível.',
                            'idade_pausas'   => 'A criança deve ter entre 3 e 9 anos para este programa.',
                            'sem_dias'       => 'Seleciona pelo menos um dia.',
                            'bd'             => 'Erro na base de dados. Tenta mais tarde.',
                            'default'        => 'Ocorreu um erro. Tenta novamente.'
                        ];
                        $key = array_key_exists($erro, $erros) ? $erro : 'default';
                        echo $erros[$key];
                        ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-lg border-0 no-hover">
                <div class="card-body p-4 p-md-5" id="formBody" style="visibility:hidden;">

                    <!-- Cabeçalho inicial: aparece quando nenhuma atividade selecionada -->
                    <div id="formCabecalho">
                        <div class="text-center mb-4">
                            <div style="font-size:3rem;">🌿</div>
                            <h2 class="fw-bold mt-2" style="color:#2b3319;"
                                data-pt="O que pretende fazer?"
                                data-en="What would you like to do?">
                                O que pretende fazer?
                            </h2>
                            <p class="text-muted mb-0"
                               data-pt="Seleciona uma opção para continuar."
                               data-en="Select an option to continue.">
                                Seleciona uma opção para continuar.
                            </p>
                        </div>
                    </div>

                    <!-- Título do formulário: aparece quando atividade selecionada -->
                    <div id="formTituloWrapper" style="display:none;" class="text-center mb-4">
                        <h2 class="fw-bold text-success" id="formTitulo">Inscrição</h2>
                        <p class="text-muted mb-0" id="formSubtitulo">Preencha o formulário para inscrever o seu filho/a na Tribo Verde</p>
                    </div>

                    <form action="processa_inscricao" method="POST" id="formInscricao" novalidate>
                        <input type="hidden" name="tipo_form" id="tipo_form" value="geral">

                        <!-- ATIVIDADE -->
                        <div class="mb-4">
                            <label for="atividade" class="form-label fw-bold">
                                <span data-pt="Atividade *" data-en="Activity *">Atividade *</span>
                            </label>
                            <select class="form-select form-select-lg" id="atividade" name="atividade_id" required>
                                <option value="" data-pt="Escolha uma opção" data-en="Choose an option">Escolha uma opção</option>
                                <?php foreach ($atividades as $ativ): ?>
                                    <?php if ($ativ['nome'] === 'Playgroups') continue; ?>
                                    <option value="<?= $ativ['id'] ?>"
                                            data-pt="<?= htmlspecialchars($ativ['nome']) ?>"
                                            data-en="<?= htmlspecialchars($ativ['nome_en'] ?? $ativ['nome']) ?>"
                                        <?php if ($atividade_selecionada == $ativ['id']) echo 'selected'; ?>>
                                        <?= htmlspecialchars($ativ['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <!-- CALENDÁRIO ANIVERSÁRIO — aparece logo após escolher a atividade -->
                        <div id="wrapper_calendario_aniv" style="display:none;">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar me-2 text-success"></i>
                                    <span data-pt="Data pretendida *" data-en="Preferred date *">Data pretendida *</span>
                                </label>
                                <input type="hidden" id="data_festa" name="data_festa">
                                <div class="calendario-wrapper">
                                    <div class="calendario-header">
                                        <button type="button" id="btnMesAnterior">&#8249;</button>
                                        <span id="mesAnoAtual" class="fw-bold"></span>
                                        <button type="button" id="btnProximoMes">&#8250;</button>
                                    </div>
                                    <div class="calendario-grid" id="calendarioGrid">
                                        <div class="calendario-dia-semana" data-pt="Dom" data-en="Sun">Dom</div>
                                        <div class="calendario-dia-semana" data-pt="Seg" data-en="Mon">Seg</div>
                                        <div class="calendario-dia-semana" data-pt="Ter" data-en="Tue">Ter</div>
                                        <div class="calendario-dia-semana" data-pt="Qua" data-en="Wed">Qua</div>
                                        <div class="calendario-dia-semana" data-pt="Qui" data-en="Thu">Qui</div>
                                        <div class="calendario-dia-semana" data-pt="Sex" data-en="Fri">Sex</div>
                                        <div class="calendario-dia-semana" data-pt="Sáb" data-en="Sat">Sáb</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-3 mt-2 flex-wrap">
                                    <small><span style="color:#2d6a4f">●</span> <span data-pt="Disponível" data-en="Available">Disponível</span></small>
                                    <small><span style="color:#856404">●</span> <span data-pt="Parcialmente disponível" data-en="Partially available">Parcialmente disponível</span></small>
                                    <small><span style="color:#dc3545">●</span> <span data-pt="Sem vagas" data-en="No spots">Sem vagas</span></small>
                                </div>
                                <div class="text-danger mt-1 d-none" id="data_festa_erro">Por favor seleciona uma data.</div>
                            </div>
                            <div class="mb-4 d-none" id="wrapper_periodo">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-clock me-2 text-success"></i>
                                    <span data-pt="Período *" data-en="Time slot *">Período *</span>
                                </label>
                                <input type="hidden" id="periodo" name="periodo">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="periodo-btn" id="btn_manha" onclick="selecionarPeriodo('manha')">
                                            <i class="fas fa-sun text-warning d-block mb-1" style="font-size:1.5rem;"></i>
                                            <strong data-pt="Manhã" data-en="Morning">Manhã</strong>
                                            <div class="small text-muted" data-pt="até às 13h00" data-en="until 1:00 PM">até às 13h00</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="periodo-btn" id="btn_tarde" onclick="selecionarPeriodo('tarde')">
                                            <i class="fas fa-moon text-primary d-block mb-1" style="font-size:1.5rem;"></i>
                                            <strong data-pt="Tarde" data-en="Afternoon">Tarde</strong>
                                            <div class="small text-muted" data-pt="a partir das 14h00" data-en="from 2:00 PM">a partir das 14h00</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-danger mt-1 d-none" id="periodo_erro">Por favor seleciona um período.</div>
                            </div>
                        </div>


                        <!-- CALENDÁRIO PAUSAS — aparece logo após escolher a atividade -->
                        <div id="wrapper_calendario_pausas" style="display:none;">
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar-alt me-2 text-success"></i>
                                    <span data-pt="Seleciona os Dias Pretendidos *" data-en="Select Preferred Days *">Seleciona os Dias Pretendidos *</span>
                                </label>
                                <input type="hidden" id="pausas_dias_selecionados" name="pausas_dias_selecionados" value="">
                                <div class="calendario-wrapper mb-3">
                                    <div class="calendario-header">
                                        <button type="button" id="btnPausasMesAnterior">&#8249;</button>
                                        <span id="pausasMesAnoAtual" class="fw-bold"></span>
                                        <button type="button" id="btnPausasProximoMes">&#8250;</button>
                                    </div>
                                    <div class="calendario-grid" id="pausasCalendarioGrid">
                                        <div class="calendario-dia-semana" data-pt="Dom" data-en="Sun">Dom</div>
                                        <div class="calendario-dia-semana" data-pt="Seg" data-en="Mon">Seg</div>
                                        <div class="calendario-dia-semana" data-pt="Ter" data-en="Tue">Ter</div>
                                        <div class="calendario-dia-semana" data-pt="Qua" data-en="Wed">Qua</div>
                                        <div class="calendario-dia-semana" data-pt="Qui" data-en="Thu">Qui</div>
                                        <div class="calendario-dia-semana" data-pt="Sex" data-en="Fri">Sex</div>
                                        <div class="calendario-dia-semana" data-pt="Sáb" data-en="Sat">Sáb</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-3 mt-2 flex-wrap mb-3">
                                    <small><span style="color:#2d6a4f">●</span> <span data-pt="Disponível" data-en="Available">Disponível</span></small>
                                    <small><span style="color:#856404">●</span> <span data-pt="Poucas vagas" data-en="Few spots">Poucas vagas</span></small>
                                    <small><span style="color:#dc3545">●</span> <span data-pt="Cheio" data-en="Full">Cheio</span></small>
                                    <small><span style="color:#aaa">●</span> <span data-pt="Encerrado" data-en="Closed">Encerrado</span></small>
                                </div>
                                <div id="pausas_dias_wrapper" class="d-none mb-3">
                                    <div class="fw-bold mb-2" style="color:#4a8c65;"><i class="fas fa-check-circle me-1"></i><span data-pt="Dias selecionados:" data-en="Selected days:">Dias selecionados:</span> <span id="pausas_contador_dias" class="badge bg-success ms-1">0</span></div>
                                    <div class="dias-selecionados-lista" id="pausas_dias_lista"></div>
                                </div>
                                <div class="text-danger mt-1 d-none" id="pausas_dias_erro">Por favor seleciona pelo menos um dia.</div>
                            </div>
                        </div>

                        <!-- CAMPOS COMUNS -->
                        <div class="mb-4" id="wrapper_nome" style="display:none;">
                            <label for="nome_responsavel" class="form-label fw-bold">
                                <i class="fas fa-user me-2 text-success"></i>
                                <span id="label_nome_responsavel" data-pt="Nome do Responsável *" data-en="Parent/Guardian Name *">Nome do Responsável *</span>
                            </label>
                            <input type="text" class="form-control form-control-lg" id="nome_responsavel" name="nome_responsavel"
                                   data-placeholder-pt="Nome completo" data-placeholder-en="Full name" placeholder="Nome completo" required>
                        </div>

                        <div class="mb-4" id="wrapper_email" style="display:none;">
                            <label for="email" class="form-label fw-bold">
                                <i class="fas fa-envelope me-2 text-success"></i>
                                <span data-pt="Email *" data-en="Email *">Email *</span>
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email"
                                   data-placeholder-pt="exemplo@email.com" data-placeholder-en="example@email.com"
                                   placeholder="exemplo@email.com" required>
                        </div>

                        <div class="mb-4" id="wrapper_telefone" style="display:none;">
                            <label for="telefone" class="form-label fw-bold">
                                <i class="fas fa-phone me-2 text-success"></i>
                                <span data-pt="Telefone *" data-en="Phone *">Telefone *</span>
                            </label>
                            <input type="tel" class="form-control form-control-lg" id="telefone" name="telefone"
                                   data-placeholder-pt="+351 912 345 678" data-placeholder-en="+351 912 345 678"
                                   placeholder="+351 912 345 678" required>
                        </div>

                        <!-- CAMPOS GERAL -->
                        <div id="campos_geral" class="d-none">
                            <div class="mb-4">
                                <label for="nome_crianca" class="form-label fw-bold">
                                    <i class="fas fa-child me-2 text-success"></i>
                                    <span data-pt="Nome da Criança *" data-en="Child's Name *">Nome da Criança *</span>
                                </label>
                                <input type="text" class="form-control form-control-lg" id="nome_crianca" name="nome_crianca"
                                       data-placeholder-pt="Nome completo da criança" data-placeholder-en="Child's full name"
                                       placeholder="Nome completo da criança">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-birthday-cake me-2 text-success"></i>
                                    <span data-pt="Data de Nascimento da Criança *" data-en="Child's Date of Birth *">Data de Nascimento da Criança *</span>
                                </label>
                                <input type="hidden" id="data_nascimento" name="data_nascimento">
                                <div class="row g-2" id="data_selects">
                                    <div class="col-4"><select class="form-select form-select-lg" id="sel_dia"><option value="" data-pt="Dia" data-en="Day">Dia</option></select></div>
                                    <div class="col-4">
                                        <select class="form-select form-select-lg" id="sel_mes">
                                            <option value="" data-pt="Mês" data-en="Month">Mês</option>
                                            <option value="01" data-pt="Janeiro" data-en="January">Janeiro</option>
                                            <option value="02" data-pt="Fevereiro" data-en="February">Fevereiro</option>
                                            <option value="03" data-pt="Março" data-en="March">Março</option>
                                            <option value="04" data-pt="Abril" data-en="April">Abril</option>
                                            <option value="05" data-pt="Maio" data-en="May">Maio</option>
                                            <option value="06" data-pt="Junho" data-en="June">Junho</option>
                                            <option value="07" data-pt="Julho" data-en="July">Julho</option>
                                            <option value="08" data-pt="Agosto" data-en="August">Agosto</option>
                                            <option value="09" data-pt="Setembro" data-en="September">Setembro</option>
                                            <option value="10" data-pt="Outubro" data-en="October">Outubro</option>
                                            <option value="11" data-pt="Novembro" data-en="November">Novembro</option>
                                            <option value="12" data-pt="Dezembro" data-en="December">Dezembro</option>
                                        </select>
                                    </div>
                                    <div class="col-4"><select class="form-select form-select-lg" id="sel_ano"><option value="" data-pt="Ano" data-en="Year">Ano</option></select></div>
                                </div>
                                <div class="form-text text-muted mt-1" data-pt="A criança deve ter entre 1 e 6 anos." data-en="The child must be between 1 and 6 years old.">A criança deve ter entre 1 e 6 anos.</div>
                                <div class="text-danger mt-1 d-none" id="data_erro_msg">A criança deve ter entre 1 e 6 anos de idade.</div>
                            </div>
                        </div>

                        <!-- CAMPOS ANIVERSÁRIOS -->
                        <div id="campos_aniversario" class="d-none">
                            <div class="mb-4">
                                <label for="nome_aniversariante" class="form-label fw-bold">
                                    <i class="fas fa-birthday-cake me-2 text-success"></i>
                                    <span data-pt="Nome do Aniversariante *" data-en="Birthday Child's Name *">Nome do Aniversariante *</span>
                                </label>
                                <input type="text" class="form-control form-control-lg" id="nome_aniversariante" name="nome_aniversariante"
                                       data-placeholder-pt="Nome completo do aniversariante" data-placeholder-en="Full name of the birthday person"
                                       placeholder="Nome completo do aniversariante">
                            </div>
                            <div class="mb-4">
                                <label for="idade_aniversariante" class="form-label fw-bold">
                                    <i class="fas fa-star me-2 text-success"></i>
                                    <span data-pt="Idade que faz *" data-en="Turning age *">Idade que faz *</span>
                                </label>
                                <select class="form-select form-select-lg" id="idade_aniversariante" name="idade_aniversariante">
                                    <option value="" data-pt="Seleciona a idade" data-en="Select age">Seleciona a idade</option>
                                    <?php for ($i = 2; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>" data-pt="<?= $i ?> anos" data-en="<?= $i ?> years old"><?= $i ?> anos</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="num_criancas" class="form-label fw-bold">
                                    <i class="fas fa-users me-2 text-success"></i>
                                    <span data-pt="Número de crianças previstas *" data-en="Expected number of children *">Número de crianças previstas *</span>
                                </label>
                                <input type="number" class="form-control form-control-lg" id="num_criancas" name="num_criancas" placeholder="Ex: 12" min="1" max="30">
                                <div class="form-text text-muted" data-pt="Festa base até 12 crianças — 180€. Criança extra — 8,50€." data-en="Base party up to 12 children — 180€. Extra child — 8.50€.">Festa base até 12 crianças — 180€. Criança extra — 8,50€.</div>
                            </div>

                        </div>

                        <!-- CAMPOS ESCOLA -->
                        <div id="campos_escola" class="d-none">
                            <div class="mb-4">
                                <label for="nome_escola" class="form-label fw-bold">
                                    <i class="fas fa-school me-2 text-success"></i>
                                    <span data-pt="Nome da Escola *" data-en="School Name *">Nome da Escola *</span>
                                </label>
                                <input type="text" class="form-control form-control-lg" id="nome_escola" name="nome_escola" placeholder="Nome da escola">
                            </div>
                            <div class="mb-4">
                                <label for="num_alunos" class="form-label fw-bold">
                                    <i class="fas fa-users me-2 text-success"></i>
                                    <span data-pt="Número de Alunos *" data-en="Number of Students *">Número de Alunos *</span>
                                </label>
                                <input type="number" class="form-control form-control-lg" id="num_alunos" name="num_alunos" placeholder="Ex: 25" min="1" max="100">
                            </div>
                            <div class="mb-4">
                                <label for="ano_escolar" class="form-label fw-bold">
                                    <i class="fas fa-graduation-cap me-2 text-success"></i>
                                    <span data-pt="Ano Escolar / Faixa Etária *" data-en="School Year / Age Range *">Ano Escolar / Faixa Etária *</span>
                                </label>
                                <select class="form-select form-select-lg" id="ano_escolar" name="ano_escolar">
                                    <option value="">Seleciona o ano</option>
                                    <option value="Pré-escolar (3-5 anos)">Pré-escolar (3-5 anos)</option>
                                    <option value="1.º ano">1.º ano</option>
                                    <option value="2.º ano">2.º ano</option>
                                    <option value="3.º ano">3.º ano</option>
                                    <option value="4.º ano">4.º ano</option>
                                    <option value="Misto">Misto</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="data_pretendida_escola" class="form-label fw-bold">
                                    <i class="fas fa-calendar me-2 text-success"></i>
                                    <span data-pt="Data Pretendida *" data-en="Preferred Date *">Data Pretendida *</span>
                                </label>
                                <input type="date" class="form-control form-control-lg" id="data_pretendida_escola" name="data_pretendida_escola"
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>

                        <!-- CAMPOS PAUSAS LECTIVAS -->
                        <div id="campos_pausas" class="d-none">
                            <div class="alert alert-info mb-4" style="border-left:4px solid #0dcaf0;">
                                <i class="fas fa-info-circle me-2"></i>
                                <span data-pt="Por favor, preencha um formulário para cada criança, ainda que sejam irmãos."
                                      data-en="Please fill in one form per child, even if they are siblings.">
                                    Por favor, preencha um formulário para cada criança, ainda que sejam irmãos.
                                </span>
                            </div>
                            <h5 class="fw-bold mb-3" style="color:#4a8c65;"><i class="fas fa-child me-2"></i><span data-pt="Dados da Criança" data-en="Child's Details">Dados da Criança</span></h5>
                            <div class="mb-4">
                                <label for="pausas_nome_crianca" class="form-label fw-bold"><i class="fas fa-user me-2 text-success"></i><span data-pt="Nome Completo da Criança *" data-en="Child's Full Name *">Nome Completo da Criança *</span></label>
                                <input type="text" class="form-control form-control-lg" id="pausas_nome_crianca" name="pausas_nome_crianca" data-placeholder-pt="Nome completo da criança" data-placeholder-en="Child's full name" placeholder="Nome completo da criança">
                            </div>
                            <div class="mb-4">
                                <label for="pausas_data_nascimento" class="form-label fw-bold"><i class="fas fa-birthday-cake me-2 text-success"></i><span data-pt="Data de Nascimento *" data-en="Date of Birth *">Data de Nascimento *</span></label>
                                <input type="date" class="form-control form-control-lg" id="pausas_data_nascimento" name="pausas_data_nascimento"
                                       min="<?php echo date('Y-m-d', strtotime('-9 years')); ?>" max="<?php echo date('Y-m-d', strtotime('-3 years')); ?>">
                                <div class="form-text text-muted" data-pt="A criança deve ter entre 3 e 9 anos." data-en="The child must be between 3 and 9 years old.">A criança deve ter entre 3 e 9 anos.</div>
                            </div>
                            <div class="mb-4">
                                <label for="pausas_nif_crianca" class="form-label fw-bold"><i class="fas fa-id-card me-2 text-success"></i><span data-pt="NIF da Criança *" data-en="Child's Tax ID (NIF) *">NIF da Criança *</span></label>
                                <input type="text" class="form-control form-control-lg" id="pausas_nif_crianca" name="pausas_nif_crianca" data-placeholder-pt="Necessário para a Apólice de Seguros" data-placeholder-en="Required for the Insurance Policy" placeholder="Necessário para a Apólice de Seguros">
                                <div class="form-text text-muted" data-pt="⚠️ Informação necessária para inscrição na nossa Apólice de Seguros." data-en="⚠️ Information required for enrollment in our Insurance Policy.">⚠️ Informação necessária para inscrição na nossa Apólice de Seguros.</div>
                            </div>
                            <h5 class="fw-bold mb-3 mt-4" style="color:#4a8c65;"><i class="fas fa-info-circle me-2"></i><span data-pt="Informações Complementares" data-en="Additional Information">Informações Complementares</span></h5>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-moon me-2 text-success"></i><span data-pt="Sesta *" data-en="Nap time *">Sesta *</span></label>
                                <div class="opcao-radio">
                                    <label><input type="radio" name="pausas_sesta" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="pausas_sesta" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-utensils me-2 text-success"></i><span data-pt="Serviço de Almoço *" data-en="Lunch Service *">Serviço de Almoço *</span></label>
                                <div class="opcao-radio" style="flex-direction:column;">
                                    <label><input type="radio" name="pausas_almoco" value="sim_pago"><span data-pt="Sim, por um valor adicional (5€/dia)" data-en="Yes, at an additional cost (€5/day)">Sim, por um valor adicional (5€/dia)</span></label>
                                    <label><input type="radio" name="pausas_almoco" value="nao_casa"><span data-pt="Não, levamos de casa" data-en="No, we bring from home">Não, levamos de casa</span></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="pausas_cuidados_saude" class="form-label fw-bold"><i class="fas fa-heartbeat me-2 text-success"></i><span data-pt="Cuidados Especiais de Saúde" data-en="Special Health Care">Cuidados Especiais de Saúde</span> <small class="text-muted fw-normal" data-pt="(intolerâncias, alergias ou outras situações)" data-en="(intolerances, allergies or other conditions)">(intolerâncias, alergias ou outras situações)</small></label>
                                <textarea class="form-control" id="pausas_cuidados_saude" name="pausas_cuidados_saude" rows="3" data-placeholder-pt="Descreve eventuais intolerâncias alimentares, alergias ou outras condições de saúde..." data-placeholder-en="Describe any food intolerances, allergies or other health conditions..." placeholder="Descreve eventuais intolerâncias alimentares, alergias ou outras condições de saúde..."></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-pills me-2 text-success"></i><span data-pt="Autoriza a aplicação de fármacos/pomadas (Ben-u-ron, Biafine, Bepanthene, etc.)? *" data-en="Do you authorise the use of medicines/ointments? *">Autoriza a aplicação de fármacos/pomadas (Ben-u-ron, Biafine, Bepanthene, etc.)? *</span></label>
                                <div class="opcao-radio">
                                    <label><input type="radio" name="pausas_farmacos" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="pausas_farmacos" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="pausas_zona_residencia" class="form-label fw-bold"><i class="fas fa-map-marker-alt me-2 text-success"></i><span data-pt="Zona de Residência *" data-en="Area of Residence *">Zona de Residência *</span></label>
                                <input type="text" class="form-control form-control-lg" id="pausas_zona_residencia" name="pausas_zona_residencia" data-placeholder-pt="Ex: Lisboa, Almada, Setúbal..." data-placeholder-en="E.g.: Lisbon, Almada, Setúbal..." placeholder="Ex: Lisboa, Almada, Setúbal...">
                            </div>
                            <h5 class="fw-bold mb-3 mt-4" style="color:#4a8c65;"><i class="fas fa-user-tie me-2"></i><span data-pt="Dados do Responsável" data-en="Guardian's Details">Dados do Responsável</span></h5>
                            <div class="mb-4">
                                <label for="pausas_nif_responsavel" class="form-label fw-bold"><i class="fas fa-id-card me-2 text-success"></i><span data-pt="NIF do Responsável *" data-en="Guardian's Tax ID (NIF) *">NIF do Responsável *</span></label>
                                <input type="text" class="form-control form-control-lg" id="pausas_nif_responsavel" name="pausas_nif_responsavel" data-placeholder-pt="NIF do responsável" data-placeholder-en="Guardian's NIF" placeholder="NIF do responsável">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-share-alt me-2 text-success"></i><span data-pt="Como conheceu a Tribo Verde? *" data-en="How did you find out about Tribo Verde? *">Como conheceu a Tribo Verde? *</span></label>
                                <div class="opcao-radio" style="flex-direction:column;">
                                    <label><input type="radio" name="pausas_como_conheceu" value="redes_sociais"><span data-pt="Redes Sociais" data-en="Social Media">Redes Sociais</span></label>
                                    <label><input type="radio" name="pausas_como_conheceu" value="site"><span data-pt="Site" data-en="Website">Site</span></label>
                                    <label><input type="radio" name="pausas_como_conheceu" value="amigos_familia"><span data-pt="Amigos ou Familiares" data-en="Friends or Family">Amigos ou Familiares</span></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-camera me-2 text-success"></i><span data-pt="Autoriza a partilha de fotos e vídeos da criança para divulgação do projeto? *" data-en="Do you authorise sharing photos and videos of the child? *">Autoriza a partilha de fotos e vídeos da criança para divulgação do projeto? *</span></label>
                                <div class="opcao-radio" style="flex-direction:column;">
                                    <label><input type="radio" name="pausas_fotos" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="pausas_fotos" value="sim_nao_reconhecivel"><span data-pt="Sim, caso não seja possível reconhecer a criança" data-en="Yes, as long as the child cannot be identified">Sim, caso não seja possível reconhecer a criança</span></label>
                                    <label><input type="radio" name="pausas_fotos" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>

                        </div>

                        <!-- CAMPOS MONTESSORI / SEXTAS -->
                        <div id="campos_montessori" class="d-none">
                            <div class="alert alert-info mb-4" style="border-left:4px solid #0dcaf0;">
                                <i class="fas fa-info-circle me-2"></i>
                                <span data-pt="Por favor, preencha um formulário para cada criança, com informações correctas para efeitos de seguro."
                                      data-en="Please fill in one form per child, with correct information for insurance purposes.">
                                    Por favor, preencha um formulário para cada criança, com informações correctas para efeitos de seguro.
                                </span>
                            </div>
                            <h5 class="fw-bold mb-3" style="color:#4a8c65;"><i class="fas fa-child me-2"></i><span data-pt="Dados da Criança" data-en="Child's Details">Dados da Criança</span></h5>
                            <div class="mb-4">
                                <label for="mont_nome_crianca" class="form-label fw-bold"><i class="fas fa-user me-2 text-success"></i><span data-pt="Nome Completo da Criança *" data-en="Child's Full Name *">Nome Completo da Criança *</span></label>
                                <input type="text" class="form-control form-control-lg" id="mont_nome_crianca" name="mont_nome_crianca" data-placeholder-pt="Nome completo da criança" data-placeholder-en="Child's full name" placeholder="Nome completo da criança">
                            </div>
                            <div class="mb-4">
                                <label for="mont_data_nascimento" class="form-label fw-bold"><i class="fas fa-birthday-cake me-2 text-success"></i><span data-pt="Data de Nascimento *" data-en="Date of Birth *">Data de Nascimento *</span></label>
                                <input type="date" class="form-control form-control-lg" id="mont_data_nascimento" name="mont_data_nascimento">
                                <div class="form-text text-muted" data-pt="A criança deve ter entre 3 e 6 anos." data-en="The child must be between 3 and 6 years old.">A criança deve ter entre 3 e 6 anos.</div>
                            </div>
                            <div class="mb-4">
                                <label for="mont_cc" class="form-label fw-bold"><i class="fas fa-id-card me-2 text-success"></i><span data-pt="Número de Identificação (C.C.)" data-en="ID Number (C.C.)">Número de Identificação (C.C.)</span></label>
                                <input type="text" class="form-control form-control-lg" id="mont_cc" name="mont_cc" data-placeholder-pt="Número do Cartão de Cidadão" data-placeholder-en="ID Card Number" placeholder="Número do Cartão de Cidadão">
                            </div>
                            <div class="mb-4">
                                <label for="mont_nif_crianca" class="form-label fw-bold"><i class="fas fa-id-card me-2 text-success"></i><span data-pt="NIF da Criança *" data-en="Child's Tax ID (NIF) *">NIF da Criança *</span></label>
                                <input type="text" class="form-control form-control-lg" id="mont_nif_crianca" name="mont_nif_crianca" data-placeholder-pt="NIF da criança" data-placeholder-en="Child's NIF" placeholder="NIF da criança">
                            </div>
                            <div class="mb-4">
                                <label for="mont_morada" class="form-label fw-bold"><i class="fas fa-map-marker-alt me-2 text-success"></i><span data-pt="Morada Completa" data-en="Full Address">Morada Completa</span></label>
                                <input type="text" class="form-control form-control-lg" id="mont_morada" name="mont_morada" data-placeholder-pt="Rua, número, código postal, localidade" data-placeholder-en="Street, number, postcode, city" placeholder="Rua, número, código postal, localidade">
                            </div>
                            <div class="mb-4">
                                <label for="mont_cuidados_saude" class="form-label fw-bold"><i class="fas fa-heartbeat me-2 text-success"></i><span data-pt="Cuidados Especiais de Saúde" data-en="Special Health Care">Cuidados Especiais de Saúde</span> <small class="text-muted fw-normal" data-pt="(intolerâncias, alergias ou outras situações)" data-en="(intolerances, allergies or other conditions)">(intolerâncias, alergias ou outras situações)</small></label>
                                <textarea class="form-control" id="mont_cuidados_saude" name="mont_cuidados_saude" rows="3" data-placeholder-pt="Descreve eventuais condições de saúde relevantes..." data-placeholder-en="Describe any relevant health conditions..." placeholder="Descreve eventuais condições de saúde relevantes..."></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-pills me-2 text-success"></i><span data-pt="Caso seja necessário, autoriza a aplicação de fármacos/pomadas (Ben-u-ron, Biafine, Bepanthene, etc.)? *" data-en="If necessary, do you authorise the use of medicines/ointments? *">Caso seja necessário, autoriza a aplicação de fármacos/pomadas (Ben-u-ron, Biafine, Bepanthene, etc.)? *</span></label>
                                <div class="opcao-radio">
                                    <label><input type="radio" name="mont_farmacos" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="mont_farmacos" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-utensils me-2 text-success"></i><span data-pt="Pretende aderir ao serviço de almoço do Sem Espiga? (pode solicitar este serviço mais tarde)" data-en="Do you wish to join the Sem Espiga lunch service? (you can request this later)">Pretende aderir ao serviço de almoço do Sem Espiga? (pode solicitar este serviço mais tarde)</span></label>
                                <div class="opcao-radio">
                                    <label><input type="radio" name="mont_almoco" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="mont_almoco" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-3 mt-4" style="color:#4a8c65;"><i class="fas fa-user-tie me-2"></i><span data-pt="Dados do Responsável" data-en="Guardian's Details">Dados do Responsável</span></h5>
                            <div class="mb-4">
                                <label for="mont_nif_responsavel" class="form-label fw-bold"><i class="fas fa-id-card me-2 text-success"></i><span data-pt="NIF do Responsável *" data-en="Guardian's Tax ID (NIF) *">NIF do Responsável *</span></label>
                                <input type="text" class="form-control form-control-lg" id="mont_nif_responsavel" name="mont_nif_responsavel" data-placeholder-pt="NIF do responsável" data-placeholder-en="Guardian's NIF" placeholder="NIF do responsável">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-camera me-2 text-success"></i><span data-pt="Para efeitos de divulgação do projecto, autoriza a partilha de fotos e vídeos da criança? *" data-en="For project promotion purposes, do you authorise sharing photos and videos of the child? *">Para efeitos de divulgação do projecto, autoriza a partilha de fotos e vídeos da criança? *</span></label>
                                <div class="opcao-radio" style="flex-direction:column;">
                                    <label><input type="radio" name="mont_fotos" value="sim"><span data-pt="Sim" data-en="Yes">Sim</span></label>
                                    <label><input type="radio" name="mont_fotos" value="sim_nao_reconhecivel"><span data-pt="Sim, caso não seja possível reconhecer a criança" data-en="Yes, as long as the child cannot be identified">Sim, caso não seja possível reconhecer a criança</span></label>
                                    <label><input type="radio" name="mont_fotos" value="nao"><span data-pt="Não" data-en="No">Não</span></label>
                                </div>
                            </div>
                        </div>

                        <!-- MENSAGEM -->
                        <div class="mb-4" id="wrapper_mensagem" style="display:none;">
                            <label for="mensagem" class="form-label fw-bold">
                                <i class="fas fa-comment me-2 text-success"></i>
                                <span data-pt="Observações (Opcional)" data-en="Notes (Optional)">Observações (Opcional)</span>
                            </label>
                            <textarea class="form-control" id="mensagem" name="mensagem" rows="4" data-placeholder-pt="Alguma informação adicional?" data-placeholder-en="Any additional information?" placeholder="Alguma informação adicional?"></textarea>
                        </div>

                        <!-- BOTÃO -->
                        <div id="wrapper_btn" style="display:none;">
                            <button type="submit" class="btn btn-success btn-lg py-3" id="btnSubmit">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Inscrição
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAIS -->
<div class="modal fade" id="modalProximosPassos" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;">
                <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Pedido Recebido!</h5>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4"><div style="font-size:3rem;">🎉</div><h5 class="fw-bold text-success mt-2">O teu pedido foi enviado!</h5><p class="text-muted">Enviámos um email com os detalhes da reserva para o teu endereço.</p></div>
                <div class="card border-0 bg-light p-3 mb-3">
                    <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2 text-success"></i>Próximos passos</h6>
                    <div class="d-flex gap-3 mb-2"><span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px;">1</span><span>Verifica o teu email com os dados da reserva</span></div>
                    <div class="d-flex gap-3 mb-2"><span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px;">2</span><span>Efectua o pagamento do sinal de <strong>90€</strong> via MBway</span></div>
                    <div class="d-flex gap-3 mb-2"><span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px;">3</span><span>Envia o comprovativo para <strong>tribo.verde.2022@gmail.com</strong></span></div>
                    <div class="d-flex gap-3"><span class="badge bg-success rounded-circle d-flex align-items-center justify-content-center" style="width:28px;height:28px;min-width:28px;">4</span><span>Confirmamos a tua reserva por email 🌿</span></div>
                </div>
                <div class="alert alert-warning mb-0 d-flex align-items-center gap-2"><i class="fas fa-clock"></i><span>Tens <strong>24 horas</strong> para efetuar o pagamento. Após esse prazo a reserva é cancelada automaticamente.</span></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4"><a href="atividades.php" class="btn btn-success w-100"><i class="fas fa-check me-2"></i>Entendido!</a></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEscola" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;"><h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Pedido Recebido!</h5></div>
            <div class="modal-body p-4">
                <div class="text-center mb-4"><div style="font-size:3rem;">🌿</div><h5 class="fw-bold text-success mt-2">O pedido foi enviado!</h5><p class="text-muted">Recebemos o pedido de visita da tua turma à Tribo Verde.</p></div>
                <div class="alert alert-success d-flex align-items-start gap-2 mb-3"><i class="fas fa-info-circle mt-1"></i><span>Entraremos em contacto brevemente para confirmar se a data pretendida está disponível ou para sugerir alternativas. 🌿</span></div>
                <div class="card border-0 bg-light p-3"><h6 class="fw-bold mb-2"><i class="fas fa-envelope me-2 text-success"></i>Verifica o teu email</h6><p class="mb-0 small text-muted">Enviámos uma confirmação com os detalhes do teu pedido para o teu endereço de email.</p></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4"><a href="atividades.php" class="btn btn-success w-100"><i class="fas fa-check me-2"></i>Entendido!</a></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPausas" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;"><h5 class="modal-title"><i class="fas fa-check-circle me-2"></i><span data-pt="Pedido Recebido!" data-en="Request Received!">Pedido Recebido!</span></h5></div>
            <div class="modal-body p-4">
                <div class="text-center mb-4"><div style="font-size:3rem;">🌿</div><h5 class="fw-bold text-success mt-2" data-pt="O teu pedido foi enviado!" data-en="Your request has been sent!">O teu pedido foi enviado!</h5><p class="text-muted" data-pt="Recebemos o pedido de inscrição nas Pausas Lectivas." data-en="We have received your Summer Programme registration request.">Recebemos o pedido de inscrição nas Pausas Lectivas.</p></div>
                <div class="alert alert-success d-flex align-items-start gap-2 mb-3"><i class="fas fa-info-circle mt-1"></i><span data-pt="Entraremos em contacto brevemente para confirmar os dias pretendidos e fornecer os dados de pagamento. 🌿" data-en="We will be in touch shortly to confirm the requested days and provide payment details. 🌿">Entraremos em contacto brevemente para confirmar os dias pretendidos e fornecer os dados de pagamento. 🌿</span></div>
                <div class="card border-0 bg-light p-3"><h6 class="fw-bold mb-2"><i class="fas fa-envelope me-2 text-success"></i><span data-pt="Verifica o teu email" data-en="Check your email">Verifica o teu email</span></h6><p class="mb-0 small text-muted" data-pt="Enviámos uma confirmação com os dias selecionados para o teu endereço de email." data-en="We sent a confirmation with the selected days to your email address.">Enviámos uma confirmação com os dias selecionados para o teu endereço de email.</p></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4"><a href="atividades.php" class="btn btn-success w-100"><i class="fas fa-check me-2"></i><span data-pt="Entendido!" data-en="Got it!">Entendido!</span></a></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMontessori" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0" style="background:linear-gradient(135deg,#4a8c65,#3a7055);border-radius:12px 12px 0 0;"><h5 class="modal-title"><i class="fas fa-check-circle me-2"></i><span data-pt="Inscrição Enviada!" data-en="Registration Sent!">Inscrição Enviada!</span></h5></div>
            <div class="modal-body p-4">
                <div class="text-center mb-4"><div style="font-size:3rem;">🌿</div><h5 class="fw-bold text-success mt-2" data-pt="O teu pedido foi enviado!" data-en="Your request has been sent!">O teu pedido foi enviado!</h5><p class="text-muted" data-pt="Recebemos a inscrição. Entraremos em contacto brevemente." data-en="We received your registration. We will be in touch shortly.">Recebemos a inscrição. Entraremos em contacto brevemente.</p></div>
                <div class="card border-0 bg-light p-3"><h6 class="fw-bold mb-2"><i class="fas fa-envelope me-2 text-success"></i><span data-pt="Verifica o teu email" data-en="Check your email">Verifica o teu email</span></h6><p class="mb-0 small text-muted" data-pt="Enviámos uma confirmação com os detalhes da inscrição para o teu endereço de email." data-en="We sent a confirmation with your registration details to your email address.">Enviámos uma confirmação com os detalhes da inscrição para o teu endereço de email.</p></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4"><a href="atividades.php" class="btn btn-success w-100"><i class="fas fa-check me-2"></i><span data-pt="Entendido!" data-en="Got it!">Entendido!</span></a></div>
        </div>
    </div>
</div>

<script>
const ID_ANIVERSARIOS = <?php echo $id_aniversarios; ?>;
const ID_ESCOLA       = <?php echo $id_escola; ?>;
const ID_PAUSAS       = <?php echo $id_pausas; ?>;
const ID_MONTESSORI   = <?php echo $id_montessori; ?>;
const ID_SEXTAS       = <?php echo $id_sextas; ?>;
const DATAS_OCUPADAS  = <?php echo json_encode($datas_ocupadas); ?>;
const ANO_PAUSAS      = <?php echo $ano_pausas; ?>;
const DIAS_PAUSAS     = <?php echo json_encode($dias_pausas); ?>;

const MESES_PT = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const MESES_EN = ['January','February','March','April','May','June','July','August','September','October','November','December'];

let calMes, calAno, dataSelecionada = null, periodoSelecionado = null;
let pausasCalMes = 5, pausasCalAno = ANO_PAUSAS, diasSelecionados = [];

function closeNotification() {
    const card = document.getElementById('notificationCard');
    if (card) { card.classList.add('hide'); setTimeout(() => card.remove(), 500); }
}
<?php if ($sucesso || $erro): ?>
setTimeout(() => { closeNotification(); }, 7000);
<?php endif; ?>
<?php if ($sucesso && isset($_GET['aniversario'])): ?>
document.addEventListener('DOMContentLoaded', function() { setTimeout(function() { new bootstrap.Modal(document.getElementById('modalProximosPassos')).show(); }, 300); });
<?php endif; ?>
<?php if ($sucesso && isset($_GET['escola'])): ?>
document.addEventListener('DOMContentLoaded', function() { setTimeout(function() { new bootstrap.Modal(document.getElementById('modalEscola')).show(); }, 300); });
<?php endif; ?>
<?php if ($sucesso && isset($_GET['pausas'])): ?>
document.addEventListener('DOMContentLoaded', function() { setTimeout(function() { new bootstrap.Modal(document.getElementById('modalPausas')).show(); }, 300); });
<?php endif; ?>
<?php if ($sucesso && isset($_GET['montessori'])): ?>
document.addEventListener('DOMContentLoaded', function() { setTimeout(function() { new bootstrap.Modal(document.getElementById('modalMontessori')).show(); }, 300); });
<?php endif; ?>

const selectAtiv = document.getElementById('atividade');

function atualizarFormulario() {
    const val          = parseInt(selectAtiv.value);
    const isAniv       = val === ID_ANIVERSARIOS;
    const isEscola     = val === ID_ESCOLA;
    const isPausas     = val === ID_PAUSAS;
    const isMontessori = val === ID_MONTESSORI || val === ID_SEXTAS;
    const lang         = localStorage.getItem('lang') || 'pt';
    const isNenhum     = !val || isNaN(val);

    // Cabeçalho inicial vs título do formulário
    document.getElementById('formCabecalho').style.display     = isNenhum ? '' : 'none';
    document.getElementById('formTituloWrapper').style.display = isNenhum ? 'none' : '';

    document.getElementById('campos_geral').classList.toggle('d-none',       isAniv || isEscola || isPausas || isMontessori || isNenhum);
    document.getElementById('campos_aniversario').classList.toggle('d-none', !isAniv);
    document.getElementById('campos_escola').classList.toggle('d-none',      !isEscola);
    document.getElementById('campos_pausas').classList.toggle('d-none',      !isPausas);
    document.getElementById('campos_montessori').classList.toggle('d-none',  !isMontessori);

    document.getElementById('wrapper_calendario_aniv').style.display   = isAniv    && !isNenhum ? '' : 'none';
    document.getElementById('wrapper_calendario_pausas').style.display = isPausas  && !isNenhum ? '' : 'none';
    document.getElementById('wrapper_nome').style.display     = isNenhum ? 'none' : '';
    document.getElementById('wrapper_email').style.display    = isNenhum ? 'none' : '';
    document.getElementById('wrapper_telefone').style.display = isNenhum ? 'none' : '';
    document.getElementById('wrapper_mensagem').style.display = isNenhum ? 'none' : '';
    document.getElementById('wrapper_btn').style.display      = isNenhum ? 'none' : '';

    document.getElementById('nome_crianca').required           = !isAniv && !isEscola && !isPausas && !isMontessori && !isNenhum;
    document.getElementById('nome_aniversariante').required    = isAniv;
    document.getElementById('idade_aniversariante').required   = isAniv;
    document.getElementById('num_criancas').required           = isAniv;
    document.getElementById('nome_escola').required            = isEscola;
    document.getElementById('num_alunos').required             = isEscola;
    document.getElementById('ano_escolar').required            = isEscola;
    document.getElementById('data_pretendida_escola').required = isEscola;
    document.getElementById('pausas_nome_crianca').required    = isPausas;
    document.getElementById('pausas_data_nascimento').required = isPausas;
    document.getElementById('pausas_nif_crianca').required     = isPausas;
    document.getElementById('pausas_nif_responsavel').required = isPausas;
    document.getElementById('pausas_zona_residencia').required = isPausas;
    document.getElementById('mont_nome_crianca').required      = isMontessori;
    document.getElementById('mont_data_nascimento').required   = isMontessori;
    document.getElementById('mont_nif_crianca').required       = isMontessori;
    document.getElementById('mont_nif_responsavel').required   = isMontessori;

    const labelNome = document.getElementById('label_nome_responsavel');
    if (isEscola) {
        labelNome.setAttribute('data-pt', 'Nome do Professor/Responsável *');
        labelNome.setAttribute('data-en', 'Teacher/Contact Name *');
        labelNome.textContent = lang === 'en' ? 'Teacher/Contact Name *' : 'Nome do Professor/Responsável *';
    } else {
        labelNome.setAttribute('data-pt', 'Nome do Responsável *');
        labelNome.setAttribute('data-en', 'Parent/Guardian Name *');
        labelNome.textContent = lang === 'en' ? 'Parent/Guardian Name *' : 'Nome do Responsável *';
    }

    const t = {
        titulo_aniv:      { pt: 'Reserva de Aniversário',            en: 'Birthday Reservation' },
        subtitulo_aniv:   { pt: 'Preenche o formulário para reservar a festa na Tribo Verde', en: 'Fill in the form to book your party at Tribo Verde' },
        btn_aniv:         { pt: '<i class="fas fa-calendar-check me-2"></i>Enviar Pedido de Reserva', en: '<i class="fas fa-calendar-check me-2"></i>Send Reservation Request' },
        titulo_escola:    { pt: '🌿 A Escola vai à Tribo Verde',         en: '🌿 School visit to Tribo Verde' },
        subtitulo_escola: { pt: 'Preenche o formulário para agendar a visita da tua turma', en: 'Fill in the form to schedule your class visit' },
        btn_escola:       { pt: '<i class="fas fa-paper-plane me-2"></i>Enviar Pedido', en: '<i class="fas fa-paper-plane me-2"></i>Send Request' },
        titulo_pausas:    { pt: '☀️ Pausas Lectivas na Tribo Verde',     en: '☀️ Summer Programme at Tribo Verde' },
        subtitulo_pausas: { pt: 'Preenche o formulário para inscrever o teu filho/a nas Pausas Lectivas', en: 'Fill in the form to enrol your child in the Summer Programme' },
        btn_pausas:       { pt: '<i class="fas fa-paper-plane me-2"></i>Enviar Pedido de Inscrição', en: '<i class="fas fa-paper-plane me-2"></i>Send Enrolment Request' },
        titulo_geral:     { pt: 'Inscrição',                             en: 'Registration' },
        subtitulo_geral:  { pt: 'Preencha o formulário para inscrever o seu filho/a na Tribo Verde', en: 'Fill in the form to register your child at Tribo Verde' },
        btn_geral:        { pt: '<i class="fas fa-paper-plane me-2"></i>Enviar Inscrição', en: '<i class="fas fa-paper-plane me-2"></i>Submit Registration' },
    };

    const formTitulo    = document.getElementById('formTitulo');
    const formSubtitulo = document.getElementById('formSubtitulo');
    const btnSubmit     = document.getElementById('btnSubmit');
    const tipoForm      = document.getElementById('tipo_form');

    if (isAniv) {
        formTitulo.textContent    = t.titulo_aniv[lang];
        formSubtitulo.textContent = t.subtitulo_aniv[lang];
        btnSubmit.innerHTML       = t.btn_aniv[lang];
        tipoForm.value            = 'aniversario';
    } else if (isEscola) {
        formTitulo.textContent    = t.titulo_escola[lang];
        formSubtitulo.textContent = t.subtitulo_escola[lang];
        btnSubmit.innerHTML       = t.btn_escola[lang];
        tipoForm.value            = 'escola';
    } else if (isPausas) {
        formTitulo.textContent    = t.titulo_pausas[lang];
        formSubtitulo.textContent = t.subtitulo_pausas[lang];
        btnSubmit.innerHTML       = t.btn_pausas[lang];
        tipoForm.value            = 'pausas';
        renderPausasCalendario();
    } else if (isMontessori) {
        formTitulo.textContent    = '🌿 ' + selectAtiv.options[selectAtiv.selectedIndex].textContent;
        formSubtitulo.textContent = lang === 'en' ? 'Fill in the form to register your child' : 'Preenche o formulário para inscrever o teu filho/a';
        btnSubmit.innerHTML       = lang === 'en' ? '<i class="fas fa-paper-plane me-2"></i>Submit Registration' : '<i class="fas fa-paper-plane me-2"></i>Enviar Inscrição';
        tipoForm.value            = 'montessori';
    } else {
        formTitulo.textContent    = t.titulo_geral[lang];
        formSubtitulo.textContent = t.subtitulo_geral[lang];
        btnSubmit.innerHTML       = t.btn_geral[lang];
        tipoForm.value            = 'geral';
    }
}

selectAtiv.addEventListener('change', () => {
    atualizarFormulario();
    aplicarTraducoes(localStorage.getItem('lang') || 'pt');
});

document.addEventListener('DOMContentLoaded', () => {
    _origAtualizar();
    aplicarTraducoes(localStorage.getItem('lang') || 'pt');
    document.getElementById('formBody').style.visibility = 'visible';
});

function iniciarCalendario() {
    const hoje = new Date();
    calMes = hoje.getMonth(); calAno = hoje.getFullYear();
    renderCalendario();
}

function renderCalendario() {
    const lang = localStorage.getItem('lang') || 'pt';
    const meses = lang === 'en' ? MESES_EN : MESES_PT;
    document.getElementById('mesAnoAtual').textContent = meses[calMes] + ' ' + calAno;
    const grid = document.getElementById('calendarioGrid');
    const cabecalhos = Array.from(grid.querySelectorAll('.calendario-dia-semana'));
    grid.innerHTML = ''; cabecalhos.forEach(c => grid.appendChild(c));
    const primeiroDia = new Date(calAno, calMes, 1).getDay();
    const totalDias   = new Date(calAno, calMes + 1, 0).getDate();
    const hoje        = new Date(); hoje.setHours(0,0,0,0);
    for (let i = 0; i < primeiroDia; i++) { const cel = document.createElement('div'); cel.className = 'calendario-dia vazio'; grid.appendChild(cel); }
    for (let d = 1; d <= totalDias; d++) {
        const data    = new Date(calAno, calMes, d);
        const diaSem  = data.getDay();
        const dataStr = `${calAno}-${String(calMes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cel     = document.createElement('div'); cel.textContent = d;
        if (diaSem !== 0 && diaSem !== 6) { cel.className = 'calendario-dia semana'; }
        else if (data < hoje) { cel.className = 'calendario-dia passado'; }
        else {
            const ocupados = DATAS_OCUPADAS[dataStr] || [];
            if (ocupados.length >= 2) { cel.className = 'calendario-dia cheio'; const dot = document.createElement('div'); dot.className = 'dot'; dot.style.color = '#dc3545'; dot.textContent = '●'; cel.appendChild(dot); }
            else if (ocupados.length === 1) { cel.className = 'calendario-dia parcial'; const dot = document.createElement('div'); dot.className = 'dot'; dot.style.color = '#856404'; dot.textContent = '●'; cel.appendChild(dot); cel.addEventListener('click', () => selecionarData(dataStr, cel, ocupados)); }
            else { cel.className = 'calendario-dia disponivel'; const dot = document.createElement('div'); dot.className = 'dot'; dot.style.color = '#2d6a4f'; dot.textContent = '●'; cel.appendChild(dot); cel.addEventListener('click', () => selecionarData(dataStr, cel, ocupados)); }
        }
        if (dataStr === dataSelecionada) cel.classList.add('selecionado');
        grid.appendChild(cel);
    }
}

function selecionarData(dataStr, cel, ocupados) {
    document.querySelectorAll('.calendario-dia.selecionado').forEach(c => c.classList.remove('selecionado'));
    cel.classList.add('selecionado'); dataSelecionada = dataStr;
    document.getElementById('data_festa').value = dataStr;
    document.getElementById('data_festa_erro').classList.add('d-none');
    document.getElementById('wrapper_periodo').classList.remove('d-none');
    periodoSelecionado = null; document.getElementById('periodo').value = '';
    document.getElementById('btn_manha').className = 'periodo-btn';
    document.getElementById('btn_tarde').className  = 'periodo-btn';
    if (ocupados.includes('manha')) document.getElementById('btn_manha').className = 'periodo-btn ocupado';
    if (ocupados.includes('tarde')) document.getElementById('btn_tarde').className  = 'periodo-btn ocupado';
}

function selecionarPeriodo(p) {
    const btn = document.getElementById('btn_' + p);
    if (btn.classList.contains('ocupado')) return;
    periodoSelecionado = p; document.getElementById('periodo').value = p;
    document.getElementById('btn_manha').className = 'periodo-btn' + (p === 'manha' ? ' selecionado' : '');
    document.getElementById('btn_tarde').className  = 'periodo-btn' + (p === 'tarde'  ? ' selecionado' : '');
    document.getElementById('periodo_erro').classList.add('d-none');
}

document.getElementById('btnMesAnterior').addEventListener('click', () => {
    const hoje = new Date();
    if (calAno > hoje.getFullYear() || (calAno === hoje.getFullYear() && calMes > hoje.getMonth())) {
        calMes--; if (calMes < 0) { calMes = 11; calAno--; } renderCalendario();
    }
});
document.getElementById('btnProximoMes').addEventListener('click', () => { calMes++; if (calMes > 11) { calMes = 0; calAno++; } renderCalendario(); });
iniciarCalendario();

function renderPausasCalendario() {
    const lang = localStorage.getItem('lang') || 'pt';
    const meses = lang === 'en' ? MESES_EN : MESES_PT;
    if (pausasCalMes < 5) { pausasCalMes = 5; pausasCalAno = ANO_PAUSAS; }
    if (pausasCalMes > 7) { pausasCalMes = 7; pausasCalAno = ANO_PAUSAS; }
    document.getElementById('pausasMesAnoAtual').textContent = meses[pausasCalMes] + ' ' + pausasCalAno;
    const grid = document.getElementById('pausasCalendarioGrid');
    const cabecalhos = Array.from(grid.querySelectorAll('.calendario-dia-semana'));
    grid.innerHTML = ''; cabecalhos.forEach(c => grid.appendChild(c));
    const primeiroDia = new Date(pausasCalAno, pausasCalMes, 1).getDay();
    const totalDias   = new Date(pausasCalAno, pausasCalMes + 1, 0).getDate();
    const hoje        = new Date(); hoje.setHours(0,0,0,0);
    for (let i = 0; i < primeiroDia; i++) { const cel = document.createElement('div'); cel.className = 'calendario-dia vazio'; grid.appendChild(cel); }
    for (let d = 1; d <= totalDias; d++) {
        const data    = new Date(pausasCalAno, pausasCalMes, d);
        const diaSem  = data.getDay();
        const dataStr = `${pausasCalAno}-${String(pausasCalMes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cel     = document.createElement('div'); cel.textContent = d;
        const isSelecionado = diasSelecionados.includes(dataStr);
        if (diaSem === 0 || diaSem === 6) { cel.className = 'calendario-dia fim-semana'; }
        else if (data < hoje) { cel.className = 'calendario-dia passado'; }
        else {
            const info = DIAS_PAUSAS[dataStr];
            if (!info) { cel.className = 'calendario-dia fora-periodo'; }
            else if (info.encerrado) { cel.className = 'calendario-dia encerrado'; const label = document.createElement('div'); label.className = 'vagas-label'; label.style.color = '#aaa'; label.textContent = lang === 'en' ? 'Closed' : 'Encerrado'; cel.appendChild(label); }
            else if (info.disponiveis <= 0) { cel.className = 'calendario-dia cheio'; const dot = document.createElement('div'); dot.className = 'dot'; dot.style.color = '#dc3545'; dot.textContent = '●'; cel.appendChild(dot); }
            else {
                const poucas = info.disponiveis <= 5;
                cel.className = 'calendario-dia ' + (poucas ? 'parcial' : 'disponivel');
                if (isSelecionado) cel.classList.add('selecionado-pausas');
                const dot = document.createElement('div'); dot.className = 'dot'; dot.style.color = poucas ? '#856404' : '#2d6a4f'; dot.textContent = '●'; cel.appendChild(dot);
                cel.addEventListener('click', () => toggleDiaPausa(dataStr, cel));
            }
        }
        grid.appendChild(cel);
    }
}

function toggleDiaPausa(dataStr, cel) {
    const idx = diasSelecionados.indexOf(dataStr);
    if (idx === -1) { diasSelecionados.push(dataStr); cel.classList.add('selecionado-pausas'); }
    else { diasSelecionados.splice(idx, 1); cel.classList.remove('selecionado-pausas'); }
    atualizarDiasSelecionados();
}

function atualizarDiasSelecionados() {
    const lang = localStorage.getItem('lang') || 'pt';
    const meses = lang === 'en' ? MESES_EN : MESES_PT;
    const diasOrdenados = [...diasSelecionados].sort();
    document.getElementById('pausas_dias_selecionados').value = diasOrdenados.join(',');
    document.getElementById('pausas_contador_dias').textContent = diasOrdenados.length;
    const lista = document.getElementById('pausas_dias_lista');
    const wrapper = document.getElementById('pausas_dias_wrapper');
    if (diasOrdenados.length > 0) {
        wrapper.classList.remove('d-none'); lista.innerHTML = '';
        const dSem = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
        const dSemEn = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        diasOrdenados.forEach(d => {
            const dt = new Date(d + 'T00:00:00');
            const ds = lang === 'en' ? dSemEn[dt.getDay()] : dSem[dt.getDay()];
            const tag = document.createElement('div'); tag.className = 'dia-tag';
            tag.innerHTML = `${ds}, ${dt.getDate()} ${meses[dt.getMonth()]} <button type="button" onclick="removerDia('${d}')">×</button>`;
            lista.appendChild(tag);
        });
        document.getElementById('pausas_dias_erro').classList.add('d-none');
    } else { wrapper.classList.add('d-none'); }
}

function removerDia(dataStr) {
    diasSelecionados = diasSelecionados.filter(d => d !== dataStr);
    atualizarDiasSelecionados(); renderPausasCalendario();
}

document.getElementById('btnPausasMesAnterior').addEventListener('click', () => { if (pausasCalMes > 5) { pausasCalMes--; renderPausasCalendario(); } });
document.getElementById('btnPausasProximoMes').addEventListener('click', () => { if (pausasCalMes < 7) { pausasCalMes++; renderPausasCalendario(); } });

(function() {
    const hoje = new Date();
    const selAno = document.getElementById('sel_ano');
    for (let a = hoje.getFullYear() - 1; a >= hoje.getFullYear() - 6; a--) { const opt = document.createElement('option'); opt.value = a; opt.textContent = a; selAno.appendChild(opt); }
    const selDia = document.getElementById('sel_dia');
    for (let d = 1; d <= 31; d++) { const opt = document.createElement('option'); opt.value = String(d).padStart(2,'0'); opt.textContent = d; selDia.appendChild(opt); }
    function atualizarData() {
        const dia = selDia.value, mes = document.getElementById('sel_mes').value, ano = selAno.value;
        document.getElementById('data_nascimento').value = (dia && mes && ano) ? `${ano}-${mes}-${dia}` : '';
    }
    ['sel_dia','sel_mes','sel_ano'].forEach(id => document.getElementById(id).addEventListener('change', atualizarData));
})();

document.getElementById('formInscricao').addEventListener('submit', function(e) {
    const val = parseInt(selectAtiv.value);
    const isAniv = val === ID_ANIVERSARIOS, isEscola = val === ID_ESCOLA, isPausas = val === ID_PAUSAS, isMontessori = val === ID_MONTESSORI || val === ID_SEXTAS;
    if (isAniv) {
        let ok = true;
        if (!dataSelecionada) { document.getElementById('data_festa_erro').classList.remove('d-none'); document.getElementById('calendarioGrid').scrollIntoView({ behavior: 'smooth', block: 'center' }); ok = false; }
        if (!periodoSelecionado) { document.getElementById('periodo_erro').classList.remove('d-none'); if (ok) document.getElementById('wrapper_periodo').scrollIntoView({ behavior: 'smooth', block: 'center' }); ok = false; }
        if (!ok) e.preventDefault();
    } else if (isPausas) {
        if (diasSelecionados.length === 0) { e.preventDefault(); document.getElementById('pausas_dias_erro').classList.remove('d-none'); document.getElementById('pausasCalendarioGrid').scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    } else if (!isEscola && !isMontessori) {
        const val2 = document.getElementById('data_nascimento').value;
        const erroMsg = document.getElementById('data_erro_msg');
        if (!val2) { e.preventDefault(); erroMsg.classList.remove('d-none'); document.getElementById('data_selects').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
        const nasc = new Date(val2), hoje2 = new Date();
        const min = new Date(hoje2); min.setFullYear(hoje2.getFullYear() - 6);
        const max = new Date(hoje2); max.setFullYear(hoje2.getFullYear() - 1);
        if (nasc < min || nasc > max) { e.preventDefault(); erroMsg.classList.remove('d-none'); document.getElementById('data_selects').scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        else { erroMsg.classList.add('d-none'); }
    }
});

// Animação suave
function animarCampos(id) {
    const el = document.getElementById(id);
    if (!el || el.classList.contains('d-none')) return;
    el.classList.remove('campos-animado'); void el.offsetWidth; el.classList.add('campos-animado');
}
const _origAtualizar = atualizarFormulario;
atualizarFormulario = function() {
    _origAtualizar();
    ['campos_geral','campos_aniversario','campos_escola','campos_pausas','campos_montessori'].forEach(animarCampos);
};

// Validação inline
function marcarErro(input, msg) {
    const wrapper = input.closest('.mb-4') || input.parentElement;
    wrapper.classList.add('campo-erro'); wrapper.classList.remove('campo-ok');
    let el = wrapper.querySelector('.msg-erro-inline');
    if (!el) { el = document.createElement('div'); el.className = 'msg-erro-inline'; wrapper.appendChild(el); }
    el.textContent = msg;
}
function marcarOk(input) {
    const wrapper = input.closest('.mb-4') || input.parentElement;
    wrapper.classList.remove('campo-erro');
    if (input.value.trim()) wrapper.classList.add('campo-ok');
    const el = wrapper.querySelector('.msg-erro-inline');
    if (el) el.textContent = '';
}
const msgs = {
    nome_responsavel: 'Por favor introduz o teu nome completo.',
    email: 'Por favor introduz um email válido.',
    telefone: 'Por favor introduz um número de telefone.',
    nome_crianca: 'Por favor introduz o nome da criança.',
    nome_aniversariante: 'Por favor introduz o nome do aniversariante.',
    num_criancas: 'Por favor indica o número de crianças.',
    nome_escola: 'Por favor introduz o nome da escola.',
    num_alunos: 'Por favor indica o número de alunos.',
    ano_escolar: 'Por favor selecciona o ano escolar.',
    data_pretendida_escola: 'Por favor selecciona uma data.',
    pausas_nome_crianca: 'Por favor introduz o nome da criança.',
    pausas_nif_crianca: 'Por favor introduz o NIF da criança.',
    pausas_zona_residencia: 'Por favor indica a zona de residência.',
    pausas_nif_responsavel: 'Por favor introduz o NIF do responsável.',
    mont_nome_crianca: 'Por favor introduz o nome da criança.',
    mont_nif_crianca: 'Por favor introduz o NIF da criança.',
    mont_nif_responsavel: 'Por favor introduz o NIF do responsável.',
};
Object.keys(msgs).forEach(id => {
    const el = document.getElementById(id); if (!el) return;
    el.addEventListener('blur', function() {
        if (!this.value.trim()) marcarErro(this, msgs[id]);
        else if (id === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value.trim())) marcarErro(this, 'Email inválido. Usa o formato: exemplo@email.com');
        else marcarOk(this);
    });
    el.addEventListener('input', function() { if (this.value.trim()) marcarOk(this); });
});

// Guardar progresso
const CAMPOS_GUARDAR = ['nome_responsavel','email','telefone','mensagem','nome_crianca','nome_aniversariante','num_criancas','nome_escola','num_alunos','ano_escolar','data_pretendida_escola','pausas_nome_crianca','pausas_data_nascimento','pausas_nif_crianca','pausas_zona_residencia','pausas_nif_responsavel','pausas_cuidados_saude','mont_nome_crianca','mont_data_nascimento','mont_cc','mont_nif_crianca','mont_morada','mont_cuidados_saude','mont_nif_responsavel'];

function guardarProgresso() {
    const dados = {};
    CAMPOS_GUARDAR.forEach(id => { const el = document.getElementById(id); if (el) dados[id] = el.value; });
    ['pausas_sesta','pausas_almoco','pausas_farmacos','pausas_como_conheceu','pausas_fotos','mont_farmacos','mont_almoco','mont_fotos'].forEach(name => { const checked = document.querySelector(`input[name="${name}"]:checked`); if (checked) dados[name] = checked.value; });
    dados['atividade_id'] = document.getElementById('atividade').value;
    sessionStorage.setItem('contacto_progresso', JSON.stringify(dados));
}

function restaurarProgresso() {
    // Se há atividade_id na URL, não restaurar sessão — respeitar o parâmetro
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('atividade_id')) return;

    const raw = sessionStorage.getItem('contacto_progresso');
    if (!raw) return;
    try {
        const dados = JSON.parse(raw);
        CAMPOS_GUARDAR.forEach(id => { const el = document.getElementById(id); if (el && dados[id]) el.value = dados[id]; });
        ['pausas_sesta','pausas_almoco','pausas_farmacos','pausas_como_conheceu','pausas_fotos','mont_farmacos','mont_almoco','mont_fotos'].forEach(name => {
            if (dados[name]) { const radio = document.querySelector(`input[name="${name}"][value="${dados[name]}"]`); if (radio) radio.checked = true; }
        });
        if (dados['atividade_id']) {
            document.getElementById('atividade').value = dados['atividade_id'];
            _origAtualizar();
            aplicarTraducoes(localStorage.getItem('lang') || 'pt');
        }
    } catch(e) {}
}

document.getElementById('formInscricao').addEventListener('input',  guardarProgresso);
document.getElementById('formInscricao').addEventListener('change', guardarProgresso);
document.addEventListener('DOMContentLoaded', restaurarProgresso);

<?php if ($sucesso): ?>
sessionStorage.removeItem('contacto_progresso');
<?php endif; ?>

// Botão loading
document.getElementById('formInscricao').addEventListener('submit', function() {
    setTimeout(() => {
        if (!document.querySelector('.campo-erro')) {
            const btn = document.getElementById('btnSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>A enviar...';
        }
    }, 80);
});

// Erro específico no submit
document.getElementById('formInscricao').addEventListener('submit', function(e) {
    const val = parseInt(selectAtiv.value);
    const isAniv = val === ID_ANIVERSARIOS, isEscola = val === ID_ESCOLA, isPausas = val === ID_PAUSAS, isMontessori = val === ID_MONTESSORI || val === ID_SEXTAS;
    let primeiroErro = null;
    function verificar(id, msg) {
        const el = document.getElementById(id);
        if (!el || el.closest('.d-none')) return;
        if (!el.value.trim()) { marcarErro(el, msg); if (!primeiroErro) primeiroErro = el; }
    }
    verificar('nome_responsavel', msgs.nome_responsavel);
    verificar('email', msgs.email);
    verificar('telefone', msgs.telefone);
    if (!isAniv && !isEscola && !isPausas && !isMontessori) verificar('nome_crianca', msgs.nome_crianca);
    if (isAniv) { verificar('nome_aniversariante', msgs.nome_aniversariante); verificar('num_criancas', msgs.num_criancas); }
    if (isEscola) { verificar('nome_escola', msgs.nome_escola); verificar('num_alunos', msgs.num_alunos); verificar('ano_escolar', msgs.ano_escolar); verificar('data_pretendida_escola', msgs.data_pretendida_escola); }
    if (isPausas) { verificar('pausas_nome_crianca', msgs.pausas_nome_crianca); verificar('pausas_nif_crianca', msgs.pausas_nif_crianca); verificar('pausas_zona_residencia', msgs.pausas_zona_residencia); verificar('pausas_nif_responsavel', msgs.pausas_nif_responsavel); }
    if (isMontessori) { verificar('mont_nome_crianca', msgs.mont_nome_crianca); verificar('mont_nif_crianca', msgs.mont_nif_crianca); verificar('mont_nif_responsavel', msgs.mont_nif_responsavel); }
    if (primeiroErro) { e.preventDefault(); primeiroErro.scrollIntoView({ behavior: 'smooth', block: 'center' }); primeiroErro.focus(); }
}, true);
</script>

<?php include 'includes/footer.php'; ?>