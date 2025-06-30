<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'env.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

// Habilita errores para desarrollo (desactiva en producción)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Honeypot antispam
if (!empty($_POST['website'])) exit();

function limpiar($str) {
    return htmlspecialchars(trim($str));
}

// Validación de archivo
function archivoValido($archivo) {
    $permitidos = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxSize = 10 * 1024 * 1024;
    return isset($archivo['tmp_name']) && in_array($archivo['type'], $permitidos) && $archivo['size'] <= $maxSize && $archivo['error'] === UPLOAD_ERR_OK;
}

// Recoge los datos
$nombre        = limpiar($_POST['nombre'] ?? '');
$apellido      = limpiar($_POST['apellido'] ?? '');
$rfc_curp      = limpiar($_POST['rfc_curp'] ?? '');
$email         = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$telefono      = limpiar($_POST['telefono'] ?? '');
$direccion     = limpiar($_POST['direccion'] ?? '');
$ciudad        = limpiar($_POST['ciudad'] ?? '');
$estado        = limpiar($_POST['estado'] ?? '');
$cp            = limpiar($_POST['cp'] ?? '');
$fecha_compra  = limpiar($_POST['fecha_compra'] ?? '');
$modelo        = limpiar($_POST['modelo'] ?? '');
$tipo_persona  = limpiar($_POST['tipo_persona'] ?? '');
$forma_pago    = limpiar($_POST['forma_pago'] ?? '');
$plazo_credito = limpiar($_POST['plazo_credito'] ?? '');

// Archivos
$ine_frente = $_FILES['ine_frente'] ?? null;
$ine_reverso = $_FILES['ine_reverso'] ?? null;
$domicilio = $_FILES['domicilio'] ?? null;
$rfc_archivo = $_FILES['rfc'] ?? null;

// IP y geolocalización
function getClientIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}
$ip = getClientIP();
$geo = json_decode(@file_get_contents("https://ipapi.co/{$ip}/json/"), true);
$ubicacion = $geo['city'] . ', ' . $geo['region'] . ', ' . $geo['country_name'] ?? 'No disponible';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.zoho.com';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('ZOHO_USER');
    $mail->Password = getenv('ZOHO_PASS');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(getenv('ZOHO_USER'), 'Formulario Web');
    $mail->addAddress(getenv('ZOHO_USER'));

    $mail->isHTML(true);
    $mail->Subject = 'Solicitud de compra recibida';

    $mail->Body = "
        <h2>Solicitud de compra</h2>
        <p><strong>Nombre:</strong> {$nombre} {$apellido}</p>
        <p><strong>RFC/CURP:</strong> {$rfc_curp}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Teléfono:</strong> {$telefono}</p>
        <p><strong>Dirección:</strong> {$direccion}</p>
        <p><strong>Ciudad:</strong> {$ciudad}</p>
        <p><strong>Estado:</strong> {$estado}</p>
        <p><strong>Código Postal:</strong> {$cp}</p>
        <p><strong>Fecha de compra:</strong> {$fecha_compra}</p>
        <p><strong>Auto de interés:</strong> {$modelo}</p>
        <p><strong>Tipo de persona:</strong> {$tipo_persona}</p>
        <p><strong>Forma de pago:</strong> {$forma_pago}</p>
        <p><strong>Plazo crédito:</strong> {$plazo_credito}</p>
        <hr>
        <p><strong>IP del visitante:</strong> {$ip}</p>
        <p><strong>Ubicación aproximada:</strong> {$ubicacion}</p>
    ";

    // Adjuntos
    if (archivoValido($ine_frente)) {
        $mail->addAttachment($ine_frente['tmp_name'], 'INE_Frente.' . pathinfo($ine_frente['name'], PATHINFO_EXTENSION));
    }
    if (archivoValido($ine_reverso)) {
        $mail->addAttachment($ine_reverso['tmp_name'], 'INE_Reverso.' . pathinfo($ine_reverso['name'], PATHINFO_EXTENSION));
    }
    if (archivoValido($domicilio)) {
        $mail->addAttachment($domicilio['tmp_name'], 'Comprobante_Domicilio.' . pathinfo($domicilio['name'], PATHINFO_EXTENSION));
    }
    if (archivoValido($rfc_archivo)) {
        $mail->addAttachment($rfc_archivo['tmp_name'], 'RFC.' . pathinfo($rfc_archivo['name'], PATHINFO_EXTENSION));
    }

    $mail->send();
    header('Location: formulario.html?success=1');
    exit;
} catch (Exception $e) {
    header('Location: formulario.html?error=' . urlencode($mail->ErrorInfo));
    exit;
}
?>
