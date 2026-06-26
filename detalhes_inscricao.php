<?php
include 'config.php';

$id = $_GET['id'] ?? null;
$is_modal = isset($_GET['modal']);

if (!$id) die('ID inválido');

$stmt = $pdo->prepare("
    SELECT i.*, a.nome as atividade_nome
    FROM inscricoes i
    LEFT JOIN atividades a ON i.atividade_id = a.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) die('Inscrição não encontrada');

$hoje      = new DateTime();
$nascimento = new DateTime($inscricao['data_nascimento']);
$idade     = $hoje->diff($nascimento)->y;

// Dados extra (Montessori / Sextas)
$extra = [];
if (!empty($inscricao['dados_extra'])) {
    $extra = json_decode($inscricao['dados_extra'], true) ?: [];
}
$tem_extra = !empty($extra);

$fotos_map = [
    'sim'                  => 'Sim',
    'sim_nao_reconhecivel' => 'Sim, caso não seja possível reconhecer a criança',
    'nao'                  => 'Não',
];

if ($is_modal): ?>

<style>
.info-row { padding: 12px 20px; border-bottom: 1px solid #e9ecef; }
.info-row:last-child { border-bottom: none; }
.info-label { font-weight: 600; color: #6c757d; font-size: 0.85rem; margin-bottom: 4px; }
.info-value { font-size: 1rem; color: #212529; }
.mensagem-box { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #4a8c65; }
.modal-badge { background: rgba(255,255,255,0.2); color: white; padding: 6px 14px; border-radius: 50px; font-size: 0.95rem; font-weight: 600; }
.section-header { background: #f8fffe; border-left: 4px solid #4a8c65; padding: 10px 15px; border-radius: 0 8px 8px 0; margin-bottom: 0; }
.aviso-saude { background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 0 8px 8px 0; padding: 10px 15px; }
</style>

<!-- Sub-header -->
<div class="d-flex justify-content-between align-items-center px-4 py-3"
     style="background:linear-gradient(135deg,#4a8c65,#3a7055);">
    <div>
        <div class="text-white opacity-75" style="font-size:0.8rem;">Recebida em</div>
        <div class="text-white fw-semibold">
            <?php echo (new DateTime($inscricao['data_inscricao']))->format('d/m/Y \às H:i'); ?>
        </div>
    </div>
    <span class="modal-badge"><?php echo htmlspecialchars($inscricao['atividade_nome']); ?></span>
</div>

<div class="p-4">

    <!-- Responsável -->
    <h6 class="section-header text-success fw-bold mb-3">
        <i class="fas fa-user me-2"></i>Responsável
    </h6>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">Nome</div>
                <div class="info-value"><?php echo htmlspecialchars($inscricao['nome_responsavel']); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">
                    <a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($inscricao['email']); ?>"
                       target="_blank" class="text-decoration-none">
                        <i class="fas fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($inscricao['email']); ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">Telefone</div>
                <div class="info-value">
                    <a href="tel:<?php echo htmlspecialchars($inscricao['telefone']); ?>" class="text-decoration-none">
                        <i class="fas fa-phone me-1 text-muted"></i><?php echo htmlspecialchars($inscricao['telefone']); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php if ($tem_extra && !empty($extra['nif_responsavel'])): ?>
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">NIF do Responsável</div>
                <div class="info-value"><?php echo htmlspecialchars($extra['nif_responsavel']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($tem_extra && !empty($extra['fotos'])): ?>
        <div class="col-12">
            <div class="info-row">
                <div class="info-label">Autoriza fotos/vídeos</div>
                <div class="info-value"><?php echo $fotos_map[$extra['fotos']] ?? $extra['fotos']; ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Criança -->
    <h6 class="section-header text-success fw-bold mb-3">
        <i class="fas fa-child me-2"></i>Criança
    </h6>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="info-row">
                <div class="info-label">Nome</div>
                <div class="info-value fw-semibold"><?php echo htmlspecialchars($inscricao['nome_crianca']); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-row">
                <div class="info-label">Data de Nascimento</div>
                <div class="info-value"><?php echo (new DateTime($inscricao['data_nascimento']))->format('d/m/Y'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-row">
                <div class="info-label">Idade</div>
                <div class="info-value"><?php echo $idade; ?> anos</div>
            </div>
        </div>
        <?php if ($tem_extra && !empty($extra['cc'])): ?>
        <div class="col-md-4">
            <div class="info-row">
                <div class="info-label">Nº CC</div>
                <div class="info-value"><?php echo htmlspecialchars($extra['cc']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($tem_extra && !empty($extra['nif_crianca'])): ?>
        <div class="col-md-4">
            <div class="info-row">
                <div class="info-label">NIF</div>
                <div class="info-value"><?php echo htmlspecialchars($extra['nif_crianca']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($tem_extra && !empty($extra['morada'])): ?>
        <div class="col-12">
            <div class="info-row">
                <div class="info-label">Morada</div>
                <div class="info-value"><?php echo htmlspecialchars($extra['morada']); ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($tem_extra): ?>
    <!-- Informações adicionais Montessori/Sextas -->
    <h6 class="section-header text-success fw-bold mb-3">
        <i class="fas fa-info-circle me-2"></i>Informações Adicionais
    </h6>
    <div class="row mb-4">
        <?php if (!empty($extra['cuidados_saude'])): ?>
        <div class="col-12 mb-2">
            <div class="aviso-saude">
                <div class="info-label mb-1">⚠️ Cuidados Especiais de Saúde</div>
                <div class="info-value fw-bold"><?php echo htmlspecialchars($extra['cuidados_saude']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($extra['farmacos'])): ?>
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">Autoriza fármacos/pomadas</div>
                <div class="info-value"><?php echo $extra['farmacos'] === 'sim' ? 'Sim' : 'Não'; ?></div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($extra['almoco'])): ?>
        <div class="col-md-6">
            <div class="info-row">
                <div class="info-label">Serviço de almoço Sem Espiga</div>
                <div class="info-value"><?php echo $extra['almoco'] === 'sim' ? 'Sim' : 'Não'; ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Observações -->
    <?php if (!empty($inscricao['mensagem'])): ?>
    <h6 class="section-header text-success fw-bold mb-3">
        <i class="fas fa-comment me-2"></i>Observações
    </h6>
    <div class="mensagem-box mb-4">
        <p class="mb-0"><?php echo nl2br(htmlspecialchars($inscricao['mensagem'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Botões -->
    <div class="d-flex gap-2 pt-3 border-top flex-wrap">
        <button id="btn-imprimir-ficha" class="btn btn-success">
            <i class="fas fa-print me-2"></i>Imprimir
        </button>
        <a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($inscricao['email']); ?>&su=Resposta+à+sua+inscrição"
           target="_blank" class="btn btn-primary">
            <i class="fas fa-reply me-2"></i>Responder por Email
        </a>
        <button onclick="bootstrap.Modal.getInstance(document.getElementById('modalDetalhes')).hide()"
                class="btn btn-secondary ms-auto">
            <i class="fas fa-times me-2"></i>Fechar
        </button>
    </div>

</div>

<?php else: ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - Inscrição #<?php echo $id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 30px; }
        .detalhe-card { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .detalhe-header { background: linear-gradient(135deg, #4a8c65 0%, #3a7055 100%); color: white; padding: 30px; }
        .info-row { padding: 15px 20px; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #6c757d; margin-bottom: 4px; }
        .info-value { font-size: 1.05rem; color: #212529; }
        .mensagem-box { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #4a8c65; }
        .section-header { background: #f8fffe; border-left: 4px solid #4a8c65; padding: 10px 15px; border-radius: 0 8px 8px 0; }
        .aviso-saude { background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 0 8px 8px 0; padding: 10px 15px; }
        @media print { .d-flex.gap-3, .d-flex.gap-2 { display: none !important; } body { padding: 10px; background: white; } .detalhe-card { box-shadow: none; } }
    </style>
</head>
<body>
<div class="container">
    <div class="detalhe-card">
        <div class="detalhe-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2"><i class="fas fa-clipboard-check me-2"></i>Inscrição #<?php echo $inscricao['id']; ?></h2>
                    <p class="mb-0 opacity-75">Recebida em <?php echo (new DateTime($inscricao['data_inscricao']))->format('d/m/Y \às H:i'); ?></p>
                </div>
                <span class="badge bg-light text-success fs-5 px-4 py-2">
                    <?php echo htmlspecialchars($inscricao['atividade_nome']); ?>
                </span>
            </div>
        </div>

        <div class="p-4">
            <h4 class="section-header text-success mb-3"><i class="fas fa-user me-2"></i>Responsável</h4>
            <div class="row mb-4">
                <div class="col-md-6"><div class="info-row"><div class="info-label">Nome</div><div class="info-value"><?php echo htmlspecialchars($inscricao['nome_responsavel']); ?></div></div></div>
                <div class="col-md-6"><div class="info-row"><div class="info-label">Email</div><div class="info-value"><a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($inscricao['email']); ?>" target="_blank" class="text-decoration-none"><?php echo htmlspecialchars($inscricao['email']); ?></a></div></div></div>
                <div class="col-md-6"><div class="info-row"><div class="info-label">Telefone</div><div class="info-value"><a href="tel:<?php echo htmlspecialchars($inscricao['telefone']); ?>" class="text-decoration-none"><?php echo htmlspecialchars($inscricao['telefone']); ?></a></div></div></div>
                <?php if ($tem_extra && !empty($extra['nif_responsavel'])): ?>
                <div class="col-md-6"><div class="info-row"><div class="info-label">NIF</div><div class="info-value"><?php echo htmlspecialchars($extra['nif_responsavel']); ?></div></div></div>
                <?php endif; ?>
                <?php if ($tem_extra && !empty($extra['fotos'])): ?>
                <div class="col-12"><div class="info-row"><div class="info-label">Autoriza fotos/vídeos</div><div class="info-value"><?php echo $fotos_map[$extra['fotos']] ?? $extra['fotos']; ?></div></div></div>
                <?php endif; ?>
            </div>

            <h4 class="section-header text-success mb-3 mt-4"><i class="fas fa-child me-2"></i>Criança</h4>
            <div class="row mb-4">
                <div class="col-md-4"><div class="info-row"><div class="info-label">Nome</div><div class="info-value fw-semibold"><?php echo htmlspecialchars($inscricao['nome_crianca']); ?></div></div></div>
                <div class="col-md-4"><div class="info-row"><div class="info-label">Data de Nascimento</div><div class="info-value"><?php echo (new DateTime($inscricao['data_nascimento']))->format('d/m/Y'); ?></div></div></div>
                <div class="col-md-4"><div class="info-row"><div class="info-label">Idade</div><div class="info-value"><?php echo $idade; ?> anos</div></div></div>
                <?php if ($tem_extra && !empty($extra['cc'])): ?>
                <div class="col-md-4"><div class="info-row"><div class="info-label">Nº CC</div><div class="info-value"><?php echo htmlspecialchars($extra['cc']); ?></div></div></div>
                <?php endif; ?>
                <?php if ($tem_extra && !empty($extra['nif_crianca'])): ?>
                <div class="col-md-4"><div class="info-row"><div class="info-label">NIF</div><div class="info-value"><?php echo htmlspecialchars($extra['nif_crianca']); ?></div></div></div>
                <?php endif; ?>
                <?php if ($tem_extra && !empty($extra['morada'])): ?>
                <div class="col-12"><div class="info-row"><div class="info-label">Morada</div><div class="info-value"><?php echo htmlspecialchars($extra['morada']); ?></div></div></div>
                <?php endif; ?>
            </div>

            <?php if ($tem_extra): ?>
            <h4 class="section-header text-success mb-3 mt-4"><i class="fas fa-info-circle me-2"></i>Informações Adicionais</h4>
            <div class="row mb-4">
                <?php if (!empty($extra['cuidados_saude'])): ?>
                <div class="col-12 mb-2"><div class="aviso-saude"><div class="info-label mb-1">⚠️ Cuidados Especiais de Saúde</div><div class="info-value fw-bold"><?php echo htmlspecialchars($extra['cuidados_saude']); ?></div></div></div>
                <?php endif; ?>
                <?php if (!empty($extra['farmacos'])): ?>
                <div class="col-md-6"><div class="info-row"><div class="info-label">Autoriza fármacos</div><div class="info-value"><?php echo $extra['farmacos'] === 'sim' ? 'Sim' : 'Não'; ?></div></div></div>
                <?php endif; ?>
                <?php if (!empty($extra['almoco'])): ?>
                <div class="col-md-6"><div class="info-row"><div class="info-label">Serviço de almoço</div><div class="info-value"><?php echo $extra['almoco'] === 'sim' ? 'Sim' : 'Não'; ?></div></div></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($inscricao['mensagem'])): ?>
            <h4 class="section-header text-success mb-3 mt-4"><i class="fas fa-comment me-2"></i>Observações</h4>
            <div class="mensagem-box"><?php echo nl2br(htmlspecialchars($inscricao['mensagem'])); ?></div>
            <?php endif; ?>

            <div class="d-flex gap-3 mt-4 pt-4 border-top">
                <button onclick="window.print()" class="btn btn-success btn-lg"><i class="fas fa-print me-2"></i>Imprimir</button>
                <a href="https://mail.google.com/mail/?view=cm&to=<?php echo urlencode($inscricao['email']); ?>&su=Resposta+à+sua+inscrição" target="_blank" class="btn btn-primary btn-lg"><i class="fas fa-reply me-2"></i>Responder por Email</a>
                <button onclick="window.close()" class="btn btn-secondary btn-lg ms-auto"><i class="fas fa-times me-2"></i>Fechar</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>