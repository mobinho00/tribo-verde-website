<?php
require $_SERVER['DOCUMENT_ROOT'] . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/PHPMailer.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/SMTP.php';
require $_SERVER['DOCUMENT_ROOT'] . '/includes/phpmailer/Exception.php';
define('MEU_EMAIL',  'gonfer.chita2@gmail.com');
define('GMAIL_PASS', 'kdwt pbez fgpm xefl');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
    exit;
}
$email_destino = trim($_POST['email'] ?? '');
$atividade_id  = (int)($_POST['atividade_id'] ?? 0);
if (!filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Email inválido']);
    exit;
}
if (!$atividade_id) {
    echo json_encode(['sucesso' => false, 'erro' => 'Atividade inválida']);
    exit;
}
$stmt = $pdo->prepare("SELECT nome, descricao, info_email, cta_email FROM atividades WHERE id = ?");
$stmt->execute([$atividade_id]);
$ativ = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ativ) {
    echo json_encode(['sucesso' => false, 'erro' => 'Atividade não encontrada']);
    exit;
}
$nome_ativ  = htmlspecialchars($ativ['nome']);
$info_texto = !empty($ativ['info_email'])
    ? nl2br(htmlspecialchars($ativ['info_email']))
    : nl2br(htmlspecialchars($ativ['descricao'] ?? 'Em breve disponível.'));
// Frase personalizada por atividade (com fallback genérico)
$cta_frase = !empty($ativ['cta_email'])
    ? htmlspecialchars($ativ['cta_email'])
    : 'Pronto/a para reservar?';
// Link do botão já com a atividade pré-selecionada
$link_reserva = "https://tribo-verde.kesug.com/contacto?atividade_id={$atividade_id}";
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
    $mail->addAddress($email_destino);
    $mail->isHTML(true);
    $mail->Subject = "🌿 Mais informações sobre: $nome_ativ";
    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
        <div style='background:linear-gradient(135deg,#4a8c65,#3a7055);color:white;padding:25px;border-radius:10px 10px 0 0;text-align:center;'>
            <h2 style='margin:0;'>🌿 Tribo Verde</h2>
            <p style='margin:8px 0 0;opacity:.85;font-size:1.1em;'>Informações sobre <strong>$nome_ativ</strong></p>
        </div>
        <div style='background:#f9f9f9;padding:30px;border:1px solid #e0e0e0;'>
            <p style='color:#555;font-size:15px;line-height:1.7;'>$info_texto</p>
        </div>
        <div style='background:#4a8c65;padding:20px;border-radius:0 0 10px 10px;text-align:center;'>
            <p style='color:white;margin:0 0 12px;font-size:14px;'>$cta_frase</p>
            <a href='$link_reserva'
               style='background:white;color:#4a8c65;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:bold;font-size:15px;'>
               Reservar Data
            </a>
        </div>
        <div style='padding:12px;text-align:center;font-size:12px;color:#999;'>
            Tribo Verde · " . date('d/m/Y H:i') . "
        </div>
    </div>";
    $mail->AltBody = "Mais informações sobre $nome_ativ\n\n"
        . strip_tags($info_texto) . "\n\n"
        . "$cta_frase\n"
        . "Para reservares: $link_reserva";
    $mail->send();
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    error_log("PHPMailer info_atividade erro: " . $mail->ErrorInfo);
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao enviar email']);
}
?>