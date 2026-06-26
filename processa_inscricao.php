<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/Exception.php';

define('MEU_EMAIL',  '!!!!!!!!!!!!!!!!!!');
define('GMAIL_PASS', '!!!!!!!!!!!!!!!!!!!!');
define('TG_TOKEN',   '!!!!!!!!!!!!!!!!!!!!');
define('TG_CHAT_ID', '!!!!!!!!!!!!!');

function notificarTelegram($mensagem) {
    $url  = "https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage";
    $dados = ['chat_id' => TG_CHAT_ID, 'text' => $mensagem, 'parse_mode' => 'HTML'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dados);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
}

function enviarEmail($mail_config) {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = MEU_EMAIL;
    $mail->Password   = GMAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($mail_config['from'], $mail_config['from_name']);
    foreach ($mail_config['to'] as $addr) { $mail->addAddress($addr[0], $addr[1]); }
    if (!empty($mail_config['reply_to'])) { $mail->addReplyTo($mail_config['reply_to'][0], $mail_config['reply_to'][1]); }
    $mail->isHTML(true);
    $mail->Subject = $mail_config['subject'];
    $mail->Body    = $mail_config['body'];
    $mail->AltBody = $mail_config['alt_body'];
    $mail->send();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contacto');
    exit;
}

$tipo_form        = $_POST['tipo_form']             ?? 'geral';
$nome_responsavel = trim($_POST['nome_responsavel'] ?? '');
$email            = trim($_POST['email']            ?? '');
$telefone         = trim($_POST['telefone']         ?? '');
$mensagem         = trim($_POST['mensagem']         ?? '');
$atividade_id     = $_POST['atividade_id']          ?? null;

if (empty($nome_responsavel) || empty($email) || empty($telefone) || empty($atividade_id)) {
    header('Location: /contacto?erro=campos_vazios'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /contacto?erro=email_invalido'); exit;
}

// ════════════════════════════════════════════════════════════════
// ANIVERSÁRIOS
// ════════════════════════════════════════════════════════════════
if ($tipo_form === 'aniversario') {

    $nome_aniversariante  = trim($_POST['nome_aniversariante']  ?? '');
    $idade_aniversariante = (int)($_POST['idade_aniversariante'] ?? 0);
    $num_criancas         = (int)($_POST['num_criancas']         ?? 0);
    $data_festa           = trim($_POST['data_festa']            ?? '');
    $periodo              = trim($_POST['periodo']               ?? '');

    if (empty($nome_aniversariante) || $idade_aniversariante < 2 || empty($data_festa) || empty($periodo) || $num_criancas < 1) {
        header('Location: /contacto?erro=campos_vazios'); exit;
    }
    if (!in_array($periodo, ['manha', 'tarde'])) {
        header('Location: /contacto?erro=data_invalida'); exit;
    }

    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM reservas_aniversarios WHERE data_festa = ? AND periodo = ? AND estado IN ('pendente','pago')");
    $stmt_check->execute([$data_festa, $periodo]);
    if ($stmt_check->fetchColumn() > 0) { header('Location: /contacto?erro=data_ocupada'); exit; }

    try {
        $preco_base  = 180.00;
        $extra       = max(0, $num_criancas - 12) * 8.50;
        $preco_total = $preco_base + $extra;
        $data_limite = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("INSERT INTO reservas_aniversarios (data_festa, periodo, nome_responsavel, email, telefone, nome_aniversariante, idade_aniversariante, num_criancas, mensagem, estado, data_limite_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', ?)");
        $stmt->execute([$data_festa, $periodo, $nome_responsavel, $email, $telefone, $nome_aniversariante, $idade_aniversariante, $num_criancas, $mensagem, $data_limite]);
        $nova_id = $pdo->lastInsertId();

        $periodo_label   = $periodo === 'manha' ? 'Manhã (até às 13h00)' : 'Tarde (a partir das 14h00)';
        $data_formatada  = date('d/m/Y', strtotime($data_festa));
        $data_limite_fmt = date('d/m/Y \à\s H:i', strtotime($data_limite));

        try {
            enviarEmail([
                'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde',
                'to' => [[$email, $nome_responsavel]], 'reply_to' => [MEU_EMAIL, 'Tribo Verde'],
                'subject' => "🎂 Pedido de Reserva de Aniversário — Tribo Verde",
                'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>🎂 Pedido de Reserva Recebido!</h2></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><p>Olá <strong>" . htmlspecialchars($nome_responsavel) . "</strong>,</p><p>Recebemos o teu pedido de reserva para a festa de aniversário do/a <strong>" . htmlspecialchars($nome_aniversariante) . "</strong>. 🌿</p><table style='width:100%;border-collapse:collapse;background:#fff;'><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;width:40%;'>Aniversariante</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($nome_aniversariante) . "</strong> ($idade_aniversariante anos)</td></tr><tr><td style='padding:10px 15px;color:#666;'>Data</td><td style='padding:10px 15px;'><strong>$data_formatada</strong></td></tr><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Período</td><td style='padding:10px 15px;'><strong>$periodo_label</strong></td></tr><tr><td style='padding:10px 15px;color:#666;'>Nº de crianças</td><td style='padding:10px 15px;'><strong>$num_criancas crianças</strong></td></tr><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Valor estimado</td><td style='padding:10px 15px;'><strong>" . number_format($preco_total, 2, ',', '.') . "€</strong></td></tr></table><div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin-top:20px;'><strong>⏰ Atenção:</strong> Tens até <strong>$data_limite_fmt</strong> para efectuar o pagamento do sinal de <strong>90€</strong> via MBway para <strong>964 634 140</strong>. Após esse prazo, a reserva será cancelada automaticamente.</div>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:25px;'>💬 A tua mensagem</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "</div></div>",
                'alt_body' => "Pedido de Reserva\nAniversariante: $nome_aniversariante ($idade_aniversariante anos)\nData: $data_formatada\nPeríodo: $periodo_label\nNº crianças: $num_criancas\nValor: " . number_format($preco_total, 2, ',', '.') . "€\nPaga o sinal de 90€ via MBway para 964 634 140 até $data_limite_fmt.\nTribo Verde · " . date('d/m/Y H:i')
            ]);
        } catch (Exception $e) { error_log("Email cliente aniversário erro: " . $e->getMessage()); }

        try {
            enviarEmail([
                'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde - Website',
                'to' => [[MEU_EMAIL, 'Admin Tribo Verde']], 'reply_to' => [$email, $nome_responsavel],
                'subject' => "🎂 Nova Reserva Aniversário #$nova_id — $data_formatada ($periodo_label)",
                'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>🎂 Nova Reserva de Aniversário #$nova_id</h2><p style='margin:5px 0 0;opacity:.85;'>$data_formatada · $periodo_label</p></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><h3 style='color:#4a8c65;'>👤 Responsável</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_responsavel) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr><tr><td style='padding:8px;color:#666;'>Telefone</td><td style='padding:8px;'><a href='tel:" . htmlspecialchars($telefone) . "'>" . htmlspecialchars($telefone) . "</a></td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>🎂 Festa</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Aniversariante</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_aniversariante) . "</strong> ($idade_aniversariante anos)</td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Data</td><td style='padding:8px;'><strong>$data_formatada</strong></td></tr><tr><td style='padding:8px;color:#666;'>Período</td><td style='padding:8px;'><strong>$periodo_label</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Nº crianças</td><td style='padding:8px;'><strong>$num_criancas</strong></td></tr><tr><td style='padding:8px;color:#666;'>Valor estimado</td><td style='padding:8px;'><strong>" . number_format($preco_total, 2, ',', '.') . "€</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Limite pagamento</td><td style='padding:8px;color:#dc3545;'><strong>$data_limite_fmt</strong></td></tr></table>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:20px;'>💬 Mensagem</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · Notificação automática · " . date('d/m/Y H:i') . "</div></div>",
                'alt_body' => "Nova Reserva Aniversário #$nova_id\nResponsável: $nome_responsavel\nEmail: $email\nTelefone: $telefone\nAniversariante: $nome_aniversariante ($idade_aniversariante anos)\nData: $data_formatada\nPeríodo: $periodo_label\nNº crianças: $num_criancas\nValor: " . number_format($preco_total, 2, ',', '.') . "€\nLimite: $data_limite_fmt\nRecebido em: " . date('d/m/Y H:i')
            ]);
        } catch (Exception $e) { error_log("Email admin aniversário erro: " . $e->getMessage()); }

        notificarTelegram("🎂 <b>Nova Reserva Aniversário #$nova_id</b>\n\n📅 <b>Data:</b> $data_formatada\n🕐 <b>Período:</b> $periodo_label\n\n👤 <b>Responsável:</b> " . htmlspecialchars($nome_responsavel) . "\n📧 " . htmlspecialchars($email) . "\n📞 " . htmlspecialchars($telefone) . "\n\n🎈 <b>Aniversariante:</b> " . htmlspecialchars($nome_aniversariante) . " ($idade_aniversariante anos)\n👧 <b>Nº crianças:</b> $num_criancas\n💰 <b>Valor estimado:</b> " . number_format($preco_total, 2, ',', '.') . "€\n⏰ <b>Limite pagamento:</b> $data_limite_fmt" . (!empty($mensagem) ? "\n\n💬 " . htmlspecialchars($mensagem) : ""));

        header('Location: /contacto?sucesso=1&aniversario=1'); exit;

    } catch (PDOException $e) {
        error_log("BD aniversário erro: " . $e->getMessage());
        header('Location: /contacto?erro=bd'); exit;
    }
}

// ════════════════════════════════════════════════════════════════
// ESCOLA VAI À TRIBO VERDE
// ════════════════════════════════════════════════════════════════
if ($tipo_form === 'escola') {

    $nome_escola            = trim($_POST['nome_escola']            ?? '');
    $num_alunos             = (int)($_POST['num_alunos']            ?? 0);
    $ano_escolar            = trim($_POST['ano_escolar']            ?? '');
    $data_pretendida_escola = trim($_POST['data_pretendida_escola'] ?? '');

    if (empty($nome_escola) || $num_alunos < 1 || empty($ano_escolar) || empty($data_pretendida_escola)) {
        header('Location: /contacto?erro=campos_vazios'); exit;
    }

    $data_fmt = date('d/m/Y', strtotime($data_pretendida_escola));

    try {
        enviarEmail([
            'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde',
            'to' => [[$email, $nome_responsavel]], 'reply_to' => [MEU_EMAIL, 'Tribo Verde'],
            'subject' => "🌿 Pedido de Visita Escolar Recebido — Tribo Verde",
            'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>🌿 Pedido de Visita Escolar Recebido!</h2></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><p>Olá <strong>" . htmlspecialchars($nome_responsavel) . "</strong>,</p><p>Recebemos o pedido de visita da <strong>" . htmlspecialchars($nome_escola) . "</strong> à Tribo Verde. 🌿</p><table style='width:100%;border-collapse:collapse;background:#fff;'><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;width:40%;'>Escola</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($nome_escola) . "</strong></td></tr><tr><td style='padding:10px 15px;color:#666;'>Nº de alunos</td><td style='padding:10px 15px;'><strong>$num_alunos alunos</strong></td></tr><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Ano escolar</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($ano_escolar) . "</strong></td></tr><tr><td style='padding:10px 15px;color:#666;'>Data pretendida</td><td style='padding:10px 15px;'><strong>$data_fmt</strong></td></tr></table><div style='background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:18px;margin-top:20px;'>Entraremos em contacto brevemente para confirmar a disponibilidade da data.</div>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:25px;'>💬 A tua mensagem</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "</div></div>",
            'alt_body' => "Pedido de Visita Escolar\nEscola: $nome_escola\nNº alunos: $num_alunos\nAno escolar: $ano_escolar\nData pretendida: $data_fmt\nTribo Verde · " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email cliente escola erro: " . $e->getMessage()); }

    try {
        enviarEmail([
            'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde - Website',
            'to' => [[MEU_EMAIL, 'Admin Tribo Verde']], 'reply_to' => [$email, $nome_responsavel],
            'subject' => "🏫 Nova Visita Escolar — " . htmlspecialchars($nome_escola) . " · $data_fmt",
            'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>🏫 Nova Visita Escolar</h2><p style='margin:5px 0 0;opacity:.85;'>" . htmlspecialchars($nome_escola) . " · $data_fmt</p></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><h3 style='color:#4a8c65;'>👤 Contacto</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Professor/Responsável</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_responsavel) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr><tr><td style='padding:8px;color:#666;'>Telefone</td><td style='padding:8px;'><a href='tel:" . htmlspecialchars($telefone) . "'>" . htmlspecialchars($telefone) . "</a></td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>🏫 Visita</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Escola</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_escola) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Nº alunos</td><td style='padding:8px;'><strong>$num_alunos</strong></td></tr><tr><td style='padding:8px;color:#666;'>Ano escolar</td><td style='padding:8px;'><strong>" . htmlspecialchars($ano_escolar) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Data pretendida</td><td style='padding:8px;color:#2d6a4f;'><strong>$data_fmt</strong></td></tr></table>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:20px;'>💬 Mensagem</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · Notificação automática · " . date('d/m/Y H:i') . "</div></div>",
            'alt_body' => "Nova Visita Escolar\nEscola: $nome_escola\nNº alunos: $num_alunos\nAno escolar: $ano_escolar\nData pretendida: $data_fmt\nContacto: $nome_responsavel\nEmail: $email\nTelefone: $telefone\nRecebido em: " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email admin escola erro: " . $e->getMessage()); }

    notificarTelegram("🏫 <b>Nova Visita Escolar</b>\n\n🏫 <b>Escola:</b> " . htmlspecialchars($nome_escola) . "\n👥 <b>Nº alunos:</b> $num_alunos\n📚 <b>Ano escolar:</b> " . htmlspecialchars($ano_escolar) . "\n📅 <b>Data pretendida:</b> $data_fmt\n\n👤 <b>Contacto:</b> " . htmlspecialchars($nome_responsavel) . "\n📧 " . htmlspecialchars($email) . "\n📞 " . htmlspecialchars($telefone) . (!empty($mensagem) ? "\n\n💬 " . htmlspecialchars($mensagem) : ""));

    header('Location: /contacto?sucesso=1&escola=1'); exit;
}

// ════════════════════════════════════════════════════════════════
// PAUSAS LECTIVAS
// ════════════════════════════════════════════════════════════════
if ($tipo_form === 'pausas') {

    $pausas_nome_crianca    = trim($_POST['pausas_nome_crianca']      ?? '');
    $pausas_data_nascimento = trim($_POST['pausas_data_nascimento']   ?? '');
    $pausas_nif_crianca     = trim($_POST['pausas_nif_crianca']       ?? '');
    $pausas_sesta           = trim($_POST['pausas_sesta']             ?? '');
    $pausas_almoco          = trim($_POST['pausas_almoco']            ?? '');
    $pausas_cuidados_saude  = trim($_POST['pausas_cuidados_saude']    ?? '');
    $pausas_farmacos        = trim($_POST['pausas_farmacos']          ?? '');
    $pausas_zona_residencia = trim($_POST['pausas_zona_residencia']   ?? '');
    $pausas_nif_responsavel = trim($_POST['pausas_nif_responsavel']   ?? '');
    $pausas_como_conheceu   = trim($_POST['pausas_como_conheceu']     ?? '');
    $pausas_fotos           = trim($_POST['pausas_fotos']             ?? '');
    $pausas_dias_raw        = trim($_POST['pausas_dias_selecionados'] ?? '');

    if (empty($pausas_nome_crianca) || empty($pausas_data_nascimento) || empty($pausas_nif_crianca) ||
        empty($pausas_sesta) || empty($pausas_almoco) || empty($pausas_farmacos) ||
        empty($pausas_zona_residencia) || empty($pausas_nif_responsavel) ||
        empty($pausas_como_conheceu) || empty($pausas_fotos)) {
        header('Location: /contacto?erro=campos_vazios'); exit;
    }

    $nasc_dt       = new DateTime($pausas_data_nascimento);
    $hoje_dt       = new DateTime();
    $idade_crianca = $hoje_dt->diff($nasc_dt)->y;
    if ($idade_crianca < 3 || $idade_crianca > 9) {
        header('Location: /contacto?erro=idade_pausas'); exit;
    }

    if (empty($pausas_dias_raw)) {
        header('Location: /contacto?erro=sem_dias'); exit;
    }
    $dias_array = array_filter(array_map('trim', explode(',', $pausas_dias_raw)));
    if (empty($dias_array)) { header('Location: /contacto?erro=sem_dias'); exit; }
    sort($dias_array);

    $meses_pt       = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
    $dias_semana_pt = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    $dias_formatados = [];
    foreach ($dias_array as $d) {
        $dt = new DateTime($d);
        $dias_formatados[] = $dias_semana_pt[$dt->format('w')] . ', ' . $dt->format('d') . ' de ' . $meses_pt[$dt->format('m')] . ' de ' . $dt->format('Y');
    }

    $sesta_label         = $pausas_sesta === 'sim' ? 'Sim' : 'Não';
    $almoco_label        = $pausas_almoco === 'sim_pago' ? 'Sim, por um valor adicional (5€/dia)' : 'Não, levamos de casa';
    $farmacos_label      = $pausas_farmacos === 'sim' ? 'Sim' : 'Não';
    $como_conheceu_map   = ['redes_sociais' => 'Redes Sociais', 'site' => 'Site', 'amigos_familia' => 'Amigos ou Familiares'];
    $como_conheceu_label = $como_conheceu_map[$pausas_como_conheceu] ?? $pausas_como_conheceu;
    $fotos_map           = ['sim' => 'Sim', 'sim_nao_reconhecivel' => 'Sim, caso não seja possível reconhecer a criança', 'nao' => 'Não'];
    $fotos_label         = $fotos_map[$pausas_fotos] ?? $pausas_fotos;
    $nasc_fmt            = date('d/m/Y', strtotime($pausas_data_nascimento));
    $total_dias          = count($dias_array);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO pausas_letivas_pedidos
            (nome_responsavel, email, telefone, nome_crianca, data_nascimento_crianca,
             nif_crianca, sesta, almoco, cuidados_saude, farmacos, zona_residencia,
             nif_responsavel, como_conheceu, autoriza_fotos, dias_pedidos, mensagem, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')
        ");
        $stmt->execute([
            $nome_responsavel, $email, $telefone,
            $pausas_nome_crianca, $pausas_data_nascimento, $pausas_nif_crianca,
            $pausas_sesta, $pausas_almoco, $pausas_cuidados_saude, $pausas_farmacos,
            $pausas_zona_residencia, $pausas_nif_responsavel, $pausas_como_conheceu,
            $pausas_fotos, implode(',', $dias_array), $mensagem
        ]);
        $nova_id = $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("BD pausas erro: " . $e->getMessage());
        header('Location: /contacto?erro=bd'); exit;
    }

    $dias_html = '';
    foreach ($dias_formatados as $df) { $dias_html .= "<li style='padding:4px 0;'>📅 $df</li>"; }

    try {
        enviarEmail([
            'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde',
            'to' => [[$email, $nome_responsavel]], 'reply_to' => [MEU_EMAIL, 'Tribo Verde'],
            'subject' => "☀️ Pedido de Inscrição nas Pausas Lectivas — Tribo Verde",
            'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>☀️ Pedido de Inscrição Recebido!</h2></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><p>Olá <strong>" . htmlspecialchars($nome_responsavel) . "</strong>,</p><p>Recebemos o pedido de inscrição de <strong>" . htmlspecialchars($pausas_nome_crianca) . "</strong> nas Pausas Lectivas da Tribo Verde. 🌿</p><h3 style='color:#4a8c65;'>📋 Dados da Criança</h3><table style='width:100%;border-collapse:collapse;background:#fff;'><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;width:40%;'>Nome</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($pausas_nome_crianca) . "</strong></td></tr><tr><td style='padding:10px 15px;color:#666;'>Data de Nascimento</td><td style='padding:10px 15px;'><strong>$nasc_fmt</strong></td></tr><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>NIF</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($pausas_nif_crianca) . "</strong></td></tr><tr><td style='padding:10px 15px;color:#666;'>Sesta</td><td style='padding:10px 15px;'>$sesta_label</td></tr><tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Serviço de Almoço</td><td style='padding:10px 15px;'>$almoco_label</td></tr>" . (!empty($pausas_cuidados_saude) ? "<tr><td style='padding:10px 15px;color:#666;'>Cuidados de Saúde</td><td style='padding:10px 15px;'>" . htmlspecialchars($pausas_cuidados_saude) . "</td></tr>" : "") . "<tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Autoriza fármacos</td><td style='padding:10px 15px;'>$farmacos_label</td></tr><tr><td style='padding:10px 15px;color:#666;'>Zona de Residência</td><td style='padding:10px 15px;'>" . htmlspecialchars($pausas_zona_residencia) . "</td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>📅 Dias Pedidos ($total_dias dia(s))</h3><ul style='background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:15px 15px 15px 35px;margin:0;'>$dias_html</ul><div style='background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:18px;margin-top:20px;'><strong>📞 Próximos passos</strong><br><br>Entraremos em contacto brevemente para confirmar os dias pretendidos e fornecer os dados de pagamento. 🌿</div>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:25px;'>💬 Observações</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "</div></div>",
            'alt_body' => "Pedido de Inscrição Pausas Lectivas\nCriança: $pausas_nome_crianca\nNascimento: $nasc_fmt\nSesta: $sesta_label\nAlmoço: $almoco_label\nFármacos: $farmacos_label\nZona: $pausas_zona_residencia\nDias pedidos:\n" . implode("\n", $dias_formatados) . "\nEntraremos em contacto brevemente.\nTribo Verde · " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email cliente pausas erro: " . $e->getMessage()); }

    try {
        enviarEmail([
            'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde - Website',
            'to' => [[MEU_EMAIL, 'Admin Tribo Verde']], 'reply_to' => [$email, $nome_responsavel],
            'subject' => "☀️ Nova Inscrição Pausas Lectivas #$nova_id — " . htmlspecialchars($pausas_nome_crianca),
            'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#f39c12,#e67e22);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>☀️ Nova Inscrição Pausas Lectivas #$nova_id</h2><p style='margin:5px 0 0;opacity:.85;'>" . htmlspecialchars($pausas_nome_crianca) . " · $total_dias dia(s)</p></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><h3 style='color:#4a8c65;'>👤 Responsável</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_responsavel) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>NIF</td><td style='padding:8px;'>" . htmlspecialchars($pausas_nif_responsavel) . "</td></tr><tr><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Telefone</td><td style='padding:8px;'><a href='tel:" . htmlspecialchars($telefone) . "'>" . htmlspecialchars($telefone) . "</a></td></tr><tr><td style='padding:8px;color:#666;'>Zona</td><td style='padding:8px;'>" . htmlspecialchars($pausas_zona_residencia) . "</td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Como conheceu</td><td style='padding:8px;'>$como_conheceu_label</td></tr><tr><td style='padding:8px;color:#666;'>Autoriza fotos</td><td style='padding:8px;'>$fotos_label</td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>🧒 Criança</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($pausas_nome_crianca) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Nascimento</td><td style='padding:8px;'>$nasc_fmt</td></tr><tr><td style='padding:8px;color:#666;'>NIF</td><td style='padding:8px;'>" . htmlspecialchars($pausas_nif_crianca) . "</td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Sesta</td><td style='padding:8px;'>$sesta_label</td></tr><tr><td style='padding:8px;color:#666;'>Almoço</td><td style='padding:8px;'>$almoco_label</td></tr>" . (!empty($pausas_cuidados_saude) ? "<tr style='background:#fff3cd;'><td style='padding:8px;color:#666;'>⚠️ Cuidados Saúde</td><td style='padding:8px;'><strong>" . htmlspecialchars($pausas_cuidados_saude) . "</strong></td></tr>" : "") . "<tr><td style='padding:8px;color:#666;'>Autoriza fármacos</td><td style='padding:8px;'>$farmacos_label</td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>📅 Dias Pedidos ($total_dias dia(s))</h3><ul style='background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:15px 15px 15px 35px;margin:0;'>$dias_html</ul>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:20px;'>💬 Observações</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · Notificação automática · " . date('d/m/Y H:i') . "</div></div>",
            'alt_body' => "Nova Inscrição Pausas Lectivas #$nova_id\nResponsável: $nome_responsavel / NIF: $pausas_nif_responsavel\nEmail: $email / Tel: $telefone\nCriança: $pausas_nome_crianca / Nasc: $nasc_fmt / NIF: $pausas_nif_crianca\nSesta: $sesta_label / Almoço: $almoco_label / Fármacos: $farmacos_label\nZona: $pausas_zona_residencia\nDias ($total_dias):\n" . implode("\n", $dias_formatados) . "\nComo conheceu: $como_conheceu_label / Fotos: $fotos_label\nRecebido em: " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email admin pausas erro: " . $e->getMessage()); }

    $tg_dias = implode("\n", array_map(fn($d) => "📅 $d", $dias_formatados));
    notificarTelegram("☀️ <b>Nova Inscrição Pausas Lectivas #$nova_id</b>\n\n👤 <b>Responsável:</b> " . htmlspecialchars($nome_responsavel) . "\n📧 " . htmlspecialchars($email) . "\n📞 " . htmlspecialchars($telefone) . "\n\n🧒 <b>Criança:</b> " . htmlspecialchars($pausas_nome_crianca) . "\n🎂 $nasc_fmt\n\n<b>Dias pedidos ($total_dias):</b>\n$tg_dias" . (!empty($pausas_cuidados_saude) ? "\n\n⚠️ <b>Cuidados saúde:</b> " . htmlspecialchars($pausas_cuidados_saude) : "") . (!empty($mensagem) ? "\n\n💬 " . htmlspecialchars($mensagem) : ""));

    header('Location: /contacto?sucesso=1&pausas=1'); exit;
}

// ════════════════════════════════════════════════════════════════
// MONTESSORI / SEXTAS NA TRIBO
// ════════════════════════════════════════════════════════════════
if ($tipo_form === 'montessori') {

    $mont_nome_crianca    = trim($_POST['mont_nome_crianca']    ?? '');
    $mont_data_nascimento = trim($_POST['mont_data_nascimento'] ?? '');
    $mont_cc              = trim($_POST['mont_cc']              ?? '');
    $mont_nif_crianca     = trim($_POST['mont_nif_crianca']     ?? '');
    $mont_morada          = trim($_POST['mont_morada']          ?? '');
    $mont_cuidados_saude  = trim($_POST['mont_cuidados_saude']  ?? '');
    $mont_farmacos        = trim($_POST['mont_farmacos']        ?? '');
    $mont_almoco          = trim($_POST['mont_almoco']          ?? '');
    $mont_nif_responsavel = trim($_POST['mont_nif_responsavel'] ?? '');
    $mont_fotos           = trim($_POST['mont_fotos']           ?? '');

    if (empty($mont_nome_crianca) || empty($mont_data_nascimento) ||
        empty($mont_nif_crianca) || empty($mont_nif_responsavel) ||
        empty($mont_farmacos) || empty($mont_fotos)) {
        header('Location: /contacto?erro=campos_vazios'); exit;
    }

    // Validar idade (3-6 anos)
    $nasc_dt       = new DateTime($mont_data_nascimento);
    $hoje_dt       = new DateTime();
    $idade_crianca = $hoje_dt->diff($nasc_dt)->y;
    if ($idade_crianca < 3 || $idade_crianca > 6) {
        header('Location: /contacto?erro=idade_montessori'); exit;
    }

    // Guardar na BD
    $dados_extra = json_encode([
        'cc'              => $mont_cc,
        'nif_crianca'     => $mont_nif_crianca,
        'morada'          => $mont_morada,
        'cuidados_saude'  => $mont_cuidados_saude,
        'farmacos'        => $mont_farmacos,
        'almoco'          => $mont_almoco,
        'nif_responsavel' => $mont_nif_responsavel,
        'fotos'           => $mont_fotos,
    ]);

    try {
        $stmt_ins = $pdo->prepare("INSERT INTO inscricoes (atividade_id, nome_responsavel, email, telefone, nome_crianca, data_nascimento, mensagem, dados_extra) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_ins->execute([$atividade_id, $nome_responsavel, $email, $telefone, $mont_nome_crianca, $mont_data_nascimento, $mensagem, $dados_extra]);
    } catch (PDOException $e) {
        error_log("BD montessori erro: " . $e->getMessage());
    }

    // Buscar nome da atividade
    $stmt_ativ = $pdo->prepare("SELECT nome FROM atividades WHERE id = ?");
    $stmt_ativ->execute([$atividade_id]);
    $ativ_row    = $stmt_ativ->fetch(PDO::FETCH_ASSOC);
    $ativ_nome   = $ativ_row ? $ativ_row['nome'] : 'Programa';

    $nasc_fmt     = date('d/m/Y', strtotime($mont_data_nascimento));
    $farmacos_lbl = $mont_farmacos === 'sim' ? 'Sim' : 'Não';
    $almoco_lbl   = $mont_almoco === 'sim' ? 'Sim' : ($mont_almoco === 'nao' ? 'Não' : 'Não indicado');
    $fotos_map    = ['sim' => 'Sim', 'sim_nao_reconhecivel' => 'Sim, caso não seja possível reconhecer a criança', 'nao' => 'Não'];
    $fotos_lbl    = $fotos_map[$mont_fotos] ?? $mont_fotos;

    // Email ao cliente
    try {
        enviarEmail([
            'from'      => MEU_EMAIL, 'from_name' => 'Tribo Verde',
            'to'        => [[$email, $nome_responsavel]],
            'reply_to'  => [MEU_EMAIL, 'Tribo Verde'],
            'subject'   => "🌿 Inscrição Recebida — $ativ_nome · Tribo Verde",
            'body'      => "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
                <div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'>
                    <h2 style='margin:0;'>🌿 Inscrição Recebida!</h2>
                    <p style='margin:8px 0 0;opacity:.85;'>$ativ_nome · Tribo Verde</p>
                </div>
                <div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'>
                    <p>Olá <strong>" . htmlspecialchars($nome_responsavel) . "</strong>,</p>
                    <p>Recebemos a inscrição de <strong>" . htmlspecialchars($mont_nome_crianca) . "</strong> no programa <strong>$ativ_nome</strong>. 🌿</p>
                    <h3 style='color:#4a8c65;'>🧒 Dados da Criança</h3>
                    <table style='width:100%;border-collapse:collapse;background:#fff;'>
                        <tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;width:40%;'>Nome</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($mont_nome_crianca) . "</strong></td></tr>
                        <tr><td style='padding:10px 15px;color:#666;'>Data de Nascimento</td><td style='padding:10px 15px;'>$nasc_fmt</td></tr>
                        " . (!empty($mont_cc) ? "<tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Nº CC</td><td style='padding:10px 15px;'>" . htmlspecialchars($mont_cc) . "</td></tr>" : "") . "
                        <tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>NIF</td><td style='padding:10px 15px;'>" . htmlspecialchars($mont_nif_crianca) . "</td></tr>
                        " . (!empty($mont_morada) ? "<tr><td style='padding:10px 15px;color:#666;'>Morada</td><td style='padding:10px 15px;'>" . htmlspecialchars($mont_morada) . "</td></tr>" : "") . "
                        " . (!empty($mont_cuidados_saude) ? "<tr style='background:#fff3cd;'><td style='padding:10px 15px;color:#666;'>⚠️ Cuidados Saúde</td><td style='padding:10px 15px;'><strong>" . htmlspecialchars($mont_cuidados_saude) . "</strong></td></tr>" : "") . "
                        <tr><td style='padding:10px 15px;color:#666;'>Autoriza fármacos</td><td style='padding:10px 15px;'>$farmacos_lbl</td></tr>
                        <tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Serviço de almoço</td><td style='padding:10px 15px;'>$almoco_lbl</td></tr>
                    </table>
                    <h3 style='color:#4a8c65;margin-top:20px;'>👤 Dados do Responsável</h3>
                    <table style='width:100%;border-collapse:collapse;background:#fff;'>
                        <tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;width:40%;'>Nome</td><td style='padding:10px 15px;'>" . htmlspecialchars($nome_responsavel) . "</td></tr>
                        <tr><td style='padding:10px 15px;color:#666;'>NIF</td><td style='padding:10px 15px;'>" . htmlspecialchars($mont_nif_responsavel) . "</td></tr>
                        <tr style='background:#f0faf4;'><td style='padding:10px 15px;color:#666;'>Telefone</td><td style='padding:10px 15px;'>" . htmlspecialchars($telefone) . "</td></tr>
                        <tr><td style='padding:10px 15px;color:#666;'>Autoriza fotos</td><td style='padding:10px 15px;'>$fotos_lbl</td></tr>
                    </table>
                    <div style='background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:18px;margin-top:20px;'>
                        Entraremos em contacto brevemente com mais informações sobre o programa. 🌿
                    </div>
                    " . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:25px;'>💬 Observações</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "
                </div>
                <div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>
                    Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "
                </div>
            </div>",
            'alt_body'  => "Inscrição Recebida — $ativ_nome\nCriança: $mont_nome_crianca\nNascimento: $nasc_fmt\nNIF: $mont_nif_crianca\nFármacos: $farmacos_lbl\nAlmoço: $almoco_lbl\nResponsável: $nome_responsavel / NIF: $mont_nif_responsavel\nEntraremos em contacto brevemente.\nTribo Verde · " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email cliente montessori erro: " . $e->getMessage()); }

    // Email ao admin
    try {
        enviarEmail([
            'from'      => MEU_EMAIL, 'from_name' => 'Tribo Verde - Website',
            'to'        => [[MEU_EMAIL, 'Admin Tribo Verde']],
            'reply_to'  => [$email, $nome_responsavel],
            'subject'   => "🌿 Nova Inscrição — $ativ_nome · " . htmlspecialchars($mont_nome_crianca),
            'body'      => "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
                <div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'>
                    <h2 style='margin:0;'>🌿 Nova Inscrição — $ativ_nome</h2>
                    <p style='margin:5px 0 0;opacity:.85;'>" . htmlspecialchars($mont_nome_crianca) . " · " . date('d/m/Y H:i') . "</p>
                </div>
                <div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'>
                    <h3 style='color:#4a8c65;'>👤 Responsável</h3>
                    <table style='width:100%;border-collapse:collapse;'>
                        <tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_responsavel) . "</strong></td></tr>
                        <tr style='background:#fff;'><td style='padding:8px;color:#666;'>NIF</td><td style='padding:8px;'>" . htmlspecialchars($mont_nif_responsavel) . "</td></tr>
                        <tr><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr>
                        <tr style='background:#fff;'><td style='padding:8px;color:#666;'>Telefone</td><td style='padding:8px;'><a href='tel:" . htmlspecialchars($telefone) . "'>" . htmlspecialchars($telefone) . "</a></td></tr>
                        <tr><td style='padding:8px;color:#666;'>Autoriza fotos</td><td style='padding:8px;'>$fotos_lbl</td></tr>
                    </table>
                    <h3 style='color:#4a8c65;margin-top:20px;'>🧒 Criança</h3>
                    <table style='width:100%;border-collapse:collapse;'>
                        <tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($mont_nome_crianca) . "</strong></td></tr>
                        <tr style='background:#fff;'><td style='padding:8px;color:#666;'>Nascimento</td><td style='padding:8px;'>$nasc_fmt</td></tr>
                        " . (!empty($mont_cc) ? "<tr><td style='padding:8px;color:#666;'>Nº CC</td><td style='padding:8px;'>" . htmlspecialchars($mont_cc) . "</td></tr>" : "") . "
                        <tr style='background:#fff;'><td style='padding:8px;color:#666;'>NIF</td><td style='padding:8px;'>" . htmlspecialchars($mont_nif_crianca) . "</td></tr>
                        " . (!empty($mont_morada) ? "<tr><td style='padding:8px;color:#666;'>Morada</td><td style='padding:8px;'>" . htmlspecialchars($mont_morada) . "</td></tr>" : "") . "
                        " . (!empty($mont_cuidados_saude) ? "<tr style='background:#fff3cd;'><td style='padding:8px;color:#666;'>⚠️ Cuidados Saúde</td><td style='padding:8px;'><strong>" . htmlspecialchars($mont_cuidados_saude) . "</strong></td></tr>" : "") . "
                        <tr><td style='padding:8px;color:#666;'>Autoriza fármacos</td><td style='padding:8px;'>$farmacos_lbl</td></tr>
                        <tr style='background:#fff;'><td style='padding:8px;color:#666;'>Serviço de almoço</td><td style='padding:8px;'>$almoco_lbl</td></tr>
                    </table>
                    " . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:20px;'>💬 Observações</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "
                </div>
                <div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>
                    Tribo Verde · Notificação automática · " . date('d/m/Y H:i') . "
                </div>
            </div>",
            'alt_body'  => "Nova Inscrição $ativ_nome\nResponsável: $nome_responsavel / NIF: $mont_nif_responsavel\nEmail: $email / Tel: $telefone\nCriança: $mont_nome_crianca / Nasc: $nasc_fmt / NIF: $mont_nif_crianca\nFármacos: $farmacos_lbl / Almoço: $almoco_lbl\nCuidados: " . ($mont_cuidados_saude ?: 'Nenhum') . "\nAutoriza fotos: $fotos_lbl\nRecebido em: " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email admin montessori erro: " . $e->getMessage()); }

    notificarTelegram("🌿 <b>Nova Inscrição — $ativ_nome</b>\n\n👤 <b>Responsável:</b> " . htmlspecialchars($nome_responsavel) . "\n📧 " . htmlspecialchars($email) . "\n📞 " . htmlspecialchars($telefone) . "\n\n🧒 <b>Criança:</b> " . htmlspecialchars($mont_nome_crianca) . "\n🎂 $nasc_fmt\n✅ Fármacos: $farmacos_lbl | Almoço: $almoco_lbl" . (!empty($mont_cuidados_saude) ? "\n⚠️ <b>Cuidados saúde:</b> " . htmlspecialchars($mont_cuidados_saude) : "") . (!empty($mensagem) ? "\n\n💬 " . htmlspecialchars($mensagem) : ""));

    header('Location: /contacto?sucesso=1&montessori=1'); exit;
}

// ════════════════════════════════════════════════════════════════
// INSCRIÇÃO GERAL
// ════════════════════════════════════════════════════════════════
$nome_crianca    = trim($_POST['nome_crianca']    ?? '');
$data_nascimento = trim($_POST['data_nascimento'] ?? '');

if (empty($nome_crianca) || empty($data_nascimento)) {
    header('Location: /contacto?erro=campos_vazios'); exit;
}

$nascimento = new DateTime($data_nascimento);
$hoje       = new DateTime();
$idade      = $hoje->diff($nascimento)->y;

if ($idade < 1 || $idade > 6) {
    header('Location: /contacto?erro=idade_invalida'); exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO inscricoes (atividade_id, nome_responsavel, email, telefone, nome_crianca, data_nascimento, mensagem) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$atividade_id, $nome_responsavel, $email, $telefone, $nome_crianca, $data_nascimento, $mensagem]);
    $nova_id = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("SELECT nome FROM atividades WHERE id = ?");
    $stmt2->execute([$atividade_id]);
    $atividade      = $stmt2->fetch(PDO::FETCH_ASSOC);
    $atividade_nome = $atividade ? $atividade['nome'] : 'ID ' . $atividade_id;

    try {
        enviarEmail([
            'from' => MEU_EMAIL, 'from_name' => 'Tribo Verde - Website',
            'to' => [[MEU_EMAIL, 'Admin Tribo Verde']], 'reply_to' => [$email, $nome_responsavel],
            'subject' => "🌿 Nova Inscrição #$nova_id - $atividade_nome",
            'body' => "<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'><div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'><h2 style='margin:0;'>🌿 Nova Inscrição #$nova_id</h2><p style='margin:5px 0 0;opacity:.85;'>Atividade: <strong>$atividade_nome</strong></p></div><div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'><h3 style='color:#4a8c65;'>👤 Responsável</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_responsavel) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Email</td><td style='padding:8px;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr><tr><td style='padding:8px;color:#666;'>Telefone</td><td style='padding:8px;'><a href='tel:" . htmlspecialchars($telefone) . "'>" . htmlspecialchars($telefone) . "</a></td></tr></table><h3 style='color:#4a8c65;margin-top:20px;'>🧒 Criança</h3><table style='width:100%;border-collapse:collapse;'><tr><td style='padding:8px;color:#666;width:35%;'>Nome</td><td style='padding:8px;'><strong>" . htmlspecialchars($nome_crianca) . "</strong></td></tr><tr style='background:#fff;'><td style='padding:8px;color:#666;'>Nascimento</td><td style='padding:8px;'>" . date('d/m/Y', strtotime($data_nascimento)) . "</td></tr></table>" . (!empty($mensagem) ? "<h3 style='color:#4a8c65;margin-top:20px;'>💬 Mensagem</h3><div style='background:#fff;padding:15px;border-left:4px solid #4a8c65;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>" : "") . "</div><div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>Tribo Verde · Notificação automática · " . date('d/m/Y H:i') . "</div></div>",
            'alt_body' => "Nova inscrição #$nova_id\nAtividade: $atividade_nome\nNome: $nome_responsavel\nEmail: $email\nTelefone: $telefone\nCriança: $nome_crianca\nNascimento: " . date('d/m/Y', strtotime($data_nascimento)) . "\nRecebido em: " . date('d/m/Y H:i')
        ]);
    } catch (Exception $e) { error_log("Email admin inscrição erro: " . $e->getMessage()); }

    notificarTelegram("🌿 <b>Nova Inscrição #$nova_id</b>\n\n🏕️ <b>Atividade:</b> $atividade_nome\n\n👤 <b>Responsável:</b> " . htmlspecialchars($nome_responsavel) . "\n📧 " . htmlspecialchars($email) . "\n📞 " . htmlspecialchars($telefone) . "\n\n🧒 <b>Criança:</b> " . htmlspecialchars($nome_crianca) . "\n🎂 " . date('d/m/Y', strtotime($data_nascimento)) . (!empty($mensagem) ? "\n\n💬 " . htmlspecialchars($mensagem) : ""));

    header('Location: /contacto?sucesso=1'); exit;

} catch (PDOException $e) {
    error_log("BD inscrição erro: " . $e->getMessage());
    header('Location: /contacto?erro=bd'); exit;
}
?>