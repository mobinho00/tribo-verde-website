<?php
session_start();
if (!($_SESSION['admin_desbloqueado'] ?? false)) { header('Location: /admin'); exit; }

require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/Exception.php';

define('MEU_EMAIL',  'gonfer.chita2@gmail.com');
define('GMAIL_PASS', 'kdwt pbez fgpm xefl');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin'); exit; }

$stmt = $pdo->prepare("SELECT * FROM pausas_letivas_pedidos WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) { header('Location: /admin'); exit; }

// Descontar vagas nos dias pedidos
$dias = array_filter(array_map('trim', explode(',', $p['dias_pedidos'])));
foreach ($dias as $dia) {
    $pdo->prepare("UPDATE pausas_letivas_dias SET vagas_ocupadas = vagas_ocupadas + 1 WHERE data = ? AND vagas_ocupadas < vagas_total")
        ->execute([$dia]);
}

// Atualizar estado
$pdo->prepare("UPDATE pausas_letivas_pedidos SET estado = 'confirmado' WHERE id = ?")
    ->execute([$id]);

// Formatar dias para email
$meses_pt       = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
$dias_semana_pt = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$dias_fmt = [];
foreach ($dias as $d) {
    $dt = new DateTime($d);
    $dias_fmt[] = $dias_semana_pt[$dt->format('w')] . ', ' . $dt->format('d') . ' de ' . $meses_pt[$dt->format('m')] . ' de ' . $dt->format('Y');
}
$total_dias = count($dias_fmt);
$dias_html  = implode('', array_map(fn($d) => "<li style='padding:4px 0;'>✅ $d</li>", $dias_fmt));
$dias_alt   = implode("\n", array_map(fn($d) => "✅ $d", $dias_fmt));

// Enviar email de confirmação ao cliente
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
    $mail->addAddress($p['email'], $p['nome_responsavel']);
    $mail->addReplyTo(MEU_EMAIL, 'Tribo Verde');
    $mail->isHTML(true);
    $mail->Subject = "✅ Inscrição Confirmada nas Pausas Lectivas — Tribo Verde";
    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
        <div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;'>
            <h2 style='margin:0;'>✅ Inscrição Confirmada!</h2>
        </div>
        <div style='background:#f9f9f9;padding:25px;border:1px solid #e0e0e0;'>
            <p>Olá <strong>" . htmlspecialchars($p['nome_responsavel']) . "</strong>,</p>
            <p>Temos o prazer de confirmar a inscrição de <strong>" . htmlspecialchars($p['nome_crianca']) . "</strong> nas Pausas Lectivas da Tribo Verde! 🌿</p>

            <h3 style='color:#4a8c65;'>📅 Dias Confirmados ($total_dias dia(s))</h3>
            <ul style='background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:15px 15px 15px 35px;margin:0;'>
                $dias_html
            </ul>

            <div style='background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:18px;margin-top:20px;'>
                <strong>🌿 Estamos à vossa espera!</strong><br><br>
                A inscrição está confirmada. Se tiveres alguma dúvida ou precisares de alterar algo, responde a este email ou contacta-nos pelo <strong>964 634 140</strong>.
            </div>
        </div>
        <div style='background:#eee;padding:12px;text-align:center;border-radius:0 0 10px 10px;font-size:12px;color:#999;'>
            Tribo Verde · tribo.verde.2022@gmail.com · " . date('d/m/Y H:i') . "
        </div>
    </div>";
    $mail->AltBody = "Inscrição Confirmada — Pausas Lectivas\n\nOlá " . $p['nome_responsavel'] . ",\n\nA inscrição de " . $p['nome_crianca'] . " nas Pausas Lectivas está confirmada!\n\nDias confirmados:\n$dias_alt\n\nEsperamos por vocês!\nTribo Verde · " . date('d/m/Y H:i');
    $mail->send();
} catch (Exception $e) {
    error_log("Email confirmação pausa erro: " . $e->getMessage());
}

header('Location: /admin?pausa_confirmada=1');
exit;
?>