<?php
session_start(); // Inicia la sesión para mantener el estado del usuario.

// Incluye los archivos necesarios:
require __DIR__ . '/db.php'; // Conexión a la base de datos.
require __DIR__ . '/vendor/autoload.php'; // Autocarga de las bibliotecas de terceros (Composer).

// Usa las clases de las bibliotecas incluidas.
use Sonata\GoogleAuthenticator\GoogleAuthenticator; // Clase para manejar la autenticación de Google Authenticator.
use chillerlan\QRCode\{QRCode, QROptions}; // Clases para generar códigos QR.
use chillerlan\QRCode\Common\EccLevel; // Nivel de corrección de errores para el QR.

// Si no hay usuario en la sesión, redirige a la página de inicio de sesión.
// Esto asegura que solo usuarios autenticados puedan acceder a esta página.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit; // Detiene la ejecución del script.
}

$username = $_SESSION['user']; // Obtiene el nombre de usuario de la sesión.
$error = ''; // Variable para almacenar mensajes de error.
$qrCodeDataUri = ''; // Variable para almacenar la imagen del código QR en formato Data URI (Base64).
$secret = ''; // Variable para almacenar la clave secreta 2FA.
$statusMessage = ''; // Variable para almacenar mensajes de estado o éxito.

$g = new GoogleAuthenticator(); // Instancia del generador de autenticación de Google.

// 1. Cargar el usuario de la base de datos para ver si ya tiene 2FA configurada.
$stmt = $pdo->prepare("SELECT secret_2fa FROM users WHERE username = ?");
$stmt->execute([$username]); // Ejecuta la consulta, pasando el nombre de usuario.
$user = $stmt->fetch(); // Obtiene la fila del usuario.

if (!$user) {
    // Si por alguna razón no encuentra al usuario, redirige a cerrar sesión.
    // Esto podría ocurrir si el usuario fue eliminado de la BD mientras tenía sesión.
    header('Location: logout.php');
    exit;
}

// Lógica para generar o mostrar el QR si 2FA no está activado para este usuario.
if (empty($user['secret_2fa'])) {
    // Si la clave secreta 2FA no está en la BD, significa que 2FA no está habilitado.

    // Generar una nueva clave secreta si no existe una temporal en la sesión.
    // Guardamos la clave en la sesión temporalmente hasta que el usuario la confirme.
    if (!isset($_SESSION['2fa_temp_secret'])) {
        $secret = $g->generateSecret(); // Genera una nueva clave secreta aleatoria.
        $_SESSION['2fa_temp_secret'] = $secret; // Almacena la clave en la sesión.
    } else {
        // Si ya hay una clave temporal, la usa para no generar una nueva en cada recarga.
        $secret = $_SESSION['2fa_temp_secret'];
    }
    
    // 1. Obtener el URI otpauth:// con Sonata. Este URI es el que se codifica en el QR.
    // Contiene la información necesaria para que la app de autenticación (ej. Google Authenticator)
    // configure una nueva entrada:
    $issuer = 'MiAppSegura'; // Nombre del emisor que aparecerá en Google Authenticator.
    $label = $username;      // Nombre del usuario que aparecerá en Google Authenticator.
    // Construye el URI con el protocolo totp, emisor, usuario, clave secreta y el emisor de nuevo.
    $otpauthUri = 'otpauth://totp/' . urlencode($issuer) . ':' . urlencode($label) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);

    // 2. Usar chillerlan/php-qrcode para generar la imagen del QR a partir del URI.
    // Configura las opciones para el QR:
    $qrCodeOptions = new QROptions([
        'outputType'  => QRCode::OUTPUT_IMAGE_PNG, // Queremos una imagen PNG.
        'eccLevel'    => EccLEVEL::L, // Nivel de corrección de errores bajo (L).
        'scale'       => 4, // Escala de los módulos del QR para un tamaño adecuado.
        'imageBase64' => true, // La salida será una imagen codificada en Base64.
    ]);
    $qrCode = new QRCode($qrCodeOptions); // Crea una instancia del generador de QR con las opciones.
    $qrCodeDataUri = $qrCode->render($otpauthUri); // Renderiza el URI otpauth:// en una imagen Base64.
    // Esta cadena Base64 se puede incrustar directamente en la etiqueta <img>.

} else {
    // Si secret_2fa ya tiene un valor en la BD, significa que 2FA ya está habilitado.
    $statusMessage = 'La autenticación de dos factores ya está habilitada.';
}

// Lógica para procesar la confirmación del código enviado por el usuario.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_confirmacion'])) {
    $codigo_confirmacion = trim($_POST['codigo_confirmacion']); // Obtiene y limpia el código de confirmación.
    $tempSecret = $_SESSION['2fa_temp_secret'] ?? ''; // Obtiene la clave secreta temporal de la sesión.

    if (empty($tempSecret)) {
        $error = 'No se ha generado una clave secreta temporal. Por favor, recarga la página.';
    } elseif (empty($codigo_confirmacion)) {
        $error = 'Por favor, introduce el código de tu autenticador para confirmar.';
    } else {
        // Usa la clave secreta TEMPORAL de la sesión para verificar el código.
        // El usuario debe introducir el código que su app de autenticación genera con esta clave.
        if ($g->checkCode($tempSecret, $codigo_confirmacion)) {
            // Si el código es correcto, guarda la clave secreta definitiva en la base de datos.
            $stmt = $pdo->prepare("UPDATE users SET secret_2fa = ? WHERE username = ?");
            $stmt->execute([$tempSecret, $username]);

            unset($_SESSION['2fa_temp_secret']); // Limpia la clave temporal de la sesión.
            // Redirige al usuario a la zona segura con un mensaje de éxito.
            header('Location: bloque_seguridad.php?2fa_enabled=1'); 
            exit;
        } else {
            // Si el código no es correcto, muestra un mensaje de error.
            $error = 'Código de confirmación incorrecto. Intenta de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configurar 2FA</title>
  <link rel="stylesheet" href="style.css"> <!-- Enlaza tu hoja de estilos CSS. -->
</head>
<body>
<div class="container">
  <h2>Configuración de Autenticación de Dos Factores (2FA)</h2>

  <?php if ($statusMessage): ?>
    <!-- Muestra mensajes de estado exitosos. -->
    <div class="message success"><?php echo htmlspecialchars($statusMessage); ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <!-- Muestra mensajes de error. -->
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <?php if (empty($user['secret_2fa'])): // Mostrar QR si 2FA no está habilitado ?>
    <p>Escanea este código QR con tu aplicación de autenticación (ej. Google Authenticator) y luego introduce el código que te muestra para confirmar.</p>
    <?php if ($qrCodeDataUri):?>
      <!-- Muestra la imagen del QR incrustada directamente en el HTML. -->
      <img src="<?php echo $qrCodeDataUri; ?>" alt="Código QR para 2FA" style="width: 200px; height: 200px; margin: 20px auto; display: block;">
      <!-- También muestra la clave secreta para entrada manual, por si el QR no funciona. -->
      <p>O introduce manualmente la clave: <strong><?php echo htmlspecialchars($secret); ?></strong></p>
    <?php endif; ?>

    <form method="POST" action="configurar_2fa.php">
      <label for="codigo_confirmacion">Código de tu autenticador para confirmar:</label>
      <!-- Campo para que el usuario introduzca el código de su app de autenticación. -->
      <input type="text" name="codigo_confirmacion" id="codigo_confirmacion" required pattern="\d{6}" maxlength="6">
      <button type="submit">Confirmar 2FA</button>
    </form>
  <?php else: ?>
    <!-- Mensaje si 2FA ya está habilitado. -->
    <p>La autenticación de dos factores ya está habilitada para tu cuenta.</p>
    <p>Si necesitas desactivarla o restablecerla, contacta con soporte técnico.</p>
  <?php endif; ?>

  <!-- Enlaces de navegación. -->
  <p class="note"><a href="bloque_seguridad.php">Volver a la zona segura</a></p>
  <p class="note"><a href="logout.php">Cerrar sesión</a></p>
</div>
</body>
</html>