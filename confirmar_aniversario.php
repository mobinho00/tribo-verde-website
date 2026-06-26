<?php
session_start();
if (!($_SESSION['admin_desbloqueado'] ?? false)) { header('Location: /admin.php'); exit; }

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/Exception.php';

define('MEU_EMAIL',  'gonfer.chita2@gmail.com');
define('GMAIL_PASS', 'kdwt pbez fgpm xefl');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM reservas_aniversarios WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$r || $r['estado'] !== 'pendente') { header('Location: /admin.php?erro=reserva_invalida'); exit; }

// Atualizar estado
$pdo->prepare("UPDATE reservas_aniversarios SET estado = 'pago' WHERE id = ?")
    ->execute([$id]);

// Formatar dados
$data_f        = date('d/m/Y', strtotime($r['data_festa']));
$periodo_label = $r['periodo'] === 'manha' ? 'Manhã (até às 13h00)' : 'Tarde (a partir das 14h00)';
$preco_base    = 180.00;
$extra         = max(0, $r['num_criancas'] - 12) * 8.50;
$preco_total   = $preco_base + $extra;
$restante      = $preco_total - 90.00;

// Email 2 — Cliente
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
    $mail->Subject = "🎉 Festa Confirmada! Aniversário #{$id} — Tribo Verde";
    $mail->Body    = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
        <div style='background:linear-gradient(135deg,#e83e8c,#c2185b);color:white;padding:30px;border-radius:10px 10px 0 0;text-align:center;'>
            <div style='font-size:3rem;'>🎉🎂🎈</div>
            <h2 style='margin:10px 0 5px;'>Festa Confirmada!</h2>
        </div>
        <div style='background:#f9f9f9;padding:30px;border:1px solid #e0e0e0;'>

            <p style='font-size:1.05rem;'>Olá <strong>" . htmlspecialchars($r['nome_responsavel']) . "</strong>! 🌿</p>
            <p>Recebemos o teu comprovativo e o pagamento do sinal foi confirmado. A festa do/a <strong>" . htmlspecialchars($r['nome_aniversariante']) . "</strong> está oficialmente marcada! Mal podemos esperar para celebrar este dia especial juntos. 🎊</p>

            <h3 style='color:#e83e8c;margin-top:25px;'>🎂 Detalhes da Festa</h3>
            <table style='width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #eee;'>
                <tr style='background:#fdf0f5;'>
                    <td style='padding:12px 15px;color:#666;width:40%;'>Aniversariante</td>
                    <td style='padding:12px 15px;'><strong>" . htmlspecialchars($r['nome_aniversariante']) . "</strong> (" . $r['idade_aniversariante'] . " anos)</td>
                </tr>
                <tr>
                    <td style='padding:12px 15px;color:#666;'>Data da Festa</td>
                    <td style='padding:12px 15px;'><strong>$data_f</strong></td>
                </tr>
                <tr style='background:#fdf0f5;'>
                    <td style='padding:12px 15px;color:#666;'>Período</td>
                    <td style='padding:12px 15px;'><strong>$periodo_label</strong></td>
                </tr>
                <tr>
                    <td style='padding:12px 15px;color:#666;'>Nº de Crianças</td>
                    <td style='padding:12px 15px;'><strong>" . $r['num_criancas'] . " crianças</strong></td>
                </tr>
                <tr style='background:#fdf0f5;'>
                    <td style='padding:12px 15px;color:#666;'>Valor Total</td>
                    <td style='padding:12px 15px;'><strong>" . number_format($preco_total, 2, ',', '.') . "€</strong></td>
                </tr>
                <tr>
                    <td style='padding:12px 15px;color:#666;'>Sinal Pago</td>
                    <td style='padding:12px 15px;'><strong style='color:#28a745;'>90,00€ ✅</strong></td>
                </tr>
                <tr style='background:#fdf0f5;'>
                    <td style='padding:12px 15px;color:#666;'>Restante a Pagar</td>
                    <td style='padding:12px 15px;'><strong style='color:#e83e8c;'>" . number_format($restante, 2, ',', '.') . "€</strong> <small style='color:#999;'>(no dia da festa)</small></td>
                </tr>
            </table>

            <div style='background:#e8f5e9;border:1px solid #c8e6c9;border-radius:8px;padding:18px;margin-top:25px;'>
                <strong>📌 Importante</strong><br><br>
                • O valor restante de <strong>" . number_format($restante, 2, ',', '.') . "€</strong> é pago no dia da festa<br>
                • Chega <strong>15 minutos antes</strong> do início para preparar o espaço<br>
                • Em caso de dúvidas, responde a este email ou contacta-nos pelo 964 634 140
            </div>

            " . (!empty($r['mensagem']) ? "
            <h3 style='color:#e83e8c;margin-top:25px;'>💬 A tua mensagem</h3>
            <div style='background:#fff;padding:15px;border-left:4px solid #e83e8c;border-radius:4px;'>
                " . nl2br(htmlspecialchars($r['mensagem'])) . "
            </div>" : "") . "

        </div>
        <div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>
            Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "
        </div>
    </div>";

    $mail->AltBody = "🎉 Festa Confirmada! Reserva #{$id}\n\n"
        . "Olá " . $r['nome_responsavel'] . "!\n\n"
        . "O pagamento do sinal foi confirmado. A festa está marcada!\n\n"
        . "Aniversariante: " . $r['nome_aniversariante'] . " (" . $r['idade_aniversariante'] . " anos)\n"
        . "Data: $data_f\n"
        . "Período: $periodo_label\n"
        . "Nº crianças: " . $r['num_criancas'] . "\n"
        . "Valor total: " . number_format($preco_total, 2, ',', '.') . "€\n"
        . "Sinal pago: 90,00€\n"
        . "Restante no dia: " . number_format($restante, 2, ',', '.') . "€\n\n"
        . "Tribo Verde · " . date('d/m/Y H:i');

    $mail->send();
} catch (Exception $e) {
    error_log("Email confirmação aniversário erro: " . $e->getMessage());
}

header('Location: /admin.php?aniv_confirmado=1');
exit;
?>