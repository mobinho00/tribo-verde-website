<?php
// Proteção por chave secreta
$chave = $_GET['chave'] ?? '';
if ($chave !== 'tv_cron_2024_xk9') {
    http_response_code(403);
    die('Acesso negado.');
}

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/Exception.php';

define('MEU_EMAIL',  'gonfer.chita2@gmail.com');
define('GMAIL_PASS', 'kdwt pbez fgpm xefl');

// Buscar todas as reservas pendentes com prazo expirado
$stmt = $pdo->query("
    SELECT * FROM reservas_aniversarios
    WHERE estado = 'pendente'
    AND data_limite_pagamento < DATE_ADD(NOW(), INTERVAL 1 HOUR)
");
$expiradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($expiradas)) {
    echo "[" . date('d/m/Y H:i') . "] Nenhuma reserva expirada.\n";
    exit;
}

foreach ($expiradas as $r) {

    // Cancelar na BD
    $pdo->prepare("UPDATE reservas_aniversarios SET estado = 'cancelado' WHERE id = ?")
        ->execute([$r['id']]);

    $data_f        = date('d/m/Y', strtotime($r['data_festa']));
    $periodo_label = $r['periodo'] === 'manha' ? 'Manhã (até às 13h00)' : 'Tarde (a partir das 14h00)';

    // Email 3 — Cliente
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MEU_EMAIL;
        $mail->Password   = GMAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MEU_EMAIL, 'Tribo Verde');
        $mail->addAddress($r['email'], $r['nome_responsavel']);
        $mail->addReplyTo(MEU_EMAIL, 'Tribo Verde');

        $mail->isHTML(true);
        $mail->Subject = "⚠️ Reserva #{$r['id']} Cancelada — Tribo Verde";
        $mail->Body    = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
            <div style='background:linear-gradient(135deg,#6c757d,#495057);color:white;padding:30px;border-radius:10px 10px 0 0;text-align:center;'>
                <div style='font-size:2.5rem;'>⏰</div>
                <h2 style='margin:10px 0 5px;'>Reserva Cancelada</h2>
                <p style='margin:0;opacity:.9;'>Reserva #{$r['id']} · Tribo Verde</p>
            </div>
            <div style='background:#f9f9f9;padding:30px;border:1px solid #e0e0e0;'>

                <p>Olá <strong>" . htmlspecialchars($r['nome_responsavel']) . "</strong>,</p>
                <p>Infelizmente a tua reserva para a festa de aniversário do/a <strong>" . htmlspecialchars($r['nome_aniversariante']) . "</strong> foi cancelada automaticamente porque o pagamento do sinal não foi efectuado dentro do prazo de 24 horas.</p>

                <div style='background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:18px;margin:20px 0;'>
                    <strong>📋 Detalhes da Reserva Cancelada</strong><br><br>
                    🎂 Aniversariante: <strong>" . htmlspecialchars($r['nome_aniversariante']) . "</strong> (" . $r['idade_aniversariante'] . " anos)<br>
                    📅 Data: <strong>$data_f</strong><br>
                    🕐 Período: <strong>$periodo_label</strong>
                </div>

                <p>Se ainda tens interesse em celebrar na Tribo Verde, podes fazer uma nova reserva — sujeita à disponibilidade da data. 🌿</p>

                <div style='text-align:center;margin:25px 0;'>
                    <a href='https://triboverde.pt/contacto'
                       style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:14px 30px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:1rem;'>
                        🎉 Fazer Nova Reserva
                    </a>
                </div>

                <p style='color:#6c757d;font-size:0.9rem;'>Se achas que isto foi um erro ou tens alguma questão, responde a este email ou contacta-nos pelo <strong>964 634 140</strong>.</p>

            </div>
            <div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>
                Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "
            </div>
        </div>";

        $mail->AltBody = "Reserva #{$r['id']} Cancelada\n\n"
            . "Olá " . $r['nome_responsavel'] . ",\n\n"
            . "A tua reserva foi cancelada por não ter sido efectuado o pagamento dentro do prazo.\n\n"
            . "Aniversariante: " . $r['nome_aniversariante'] . " (" . $r['idade_aniversariante'] . " anos)\n"
            . "Data: $data_f\n"
            . "Período: $periodo_label\n\n"
            . "Podes fazer uma nova reserva em triboverde.pt/contacto\n\n"
            . "Tribo Verde · " . date('d/m/Y H:i');

        $mail->send();
        echo "[" . date('d/m/Y H:i') . "] Reserva #{$r['id']} cancelada — email enviado a {$r['email']}\n";

    } catch (Exception $e) {
        error_log("Email cancelamento cron erro reserva #{$r['id']}: " . $e->getMessage());
        echo "[" . date('d/m/Y H:i') . "] Reserva #{$r['id']} cancelada — ERRO email: " . $e->getMessage() . "\n";
    }
}