<?php
session_start(); // Inicia la sesión para acceder a variables de sesión como 'pending_user'.
require __DIR__ . '/db.php'; // Incluye el archivo de conexión a la base de datos.
require __DIR__ . '/vendor/autoload.php'; // Necesario para cargar la clase GoogleAuthenticator de Sonata.

use Sonata\GoogleAuthenticator\GoogleAuthenticator; // Usa la clase de Google Authenticator.

// 1. Control de acceso: Si no hay un usuario en la sesión temporal 'pending_user',
// significa que el usuario no ha pasado por el primer factor de autenticación (login normal).
// Se le redirige de vuelta a la página de login.
if (!isset($_SESSION['pending_user'])) {
    header('Location: login.php'); // Redirige al login.
    exit; // Detiene la ejecución del script.
}

$username = $_SESSION['pending_user']; // Obtiene el nombre de usuario de la sesión temporal.
$msg = ''; // Variable para almacenar mensajes de error o avisos al usuario.

// 2. Procesa el formulario cuando se envía (método POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? ''); // Obtiene y limpia el código 2FA ingresado por el usuario.

    // 3. Obtiene la clave secreta 2FA del usuario desde la base de datos.
    $stmt = $pdo->prepare("SELECT secret_2fa FROM users WHERE username = ?");
    $stmt->execute([$username]); // Ejecuta la consulta con el nombre de usuario.
    $user = $stmt->fetch(); // Obtiene la fila del usuario.

    // 4. Verifica si se encontró el usuario y si tiene una clave secreta 2FA configurada.
    if ($user && $user['secret_2fa']) {
        $g = new GoogleAuthenticator(); // Instancia del generador de autenticación de Google.

        // 5. Valida el código ingresado por el usuario contra la clave secreta guardada.
        // GoogleAuthenticator::checkCode() comprueba si el código es válido dentro de la ventana de tiempo.
        if ($g->checkCode($user['secret_2fa'], $codigo)) {
            // ✅ Código correcto:
            // 6. Establece la sesión principal del usuario, indicando que ha iniciado sesión con éxito.
            $_SESSION['user'] = $username;
            // 7. Limpia la sesión temporal 'pending_user' ya que la verificación 2FA ha sido completada.
            unset($_SESSION['pending_user']); 
            // 8. Redirige al usuario a la zona segura o dashboard.
            header('Location: bloque_seguridad.php');
            exit; // Detiene la ejecución.
        } else {
            $msg = '❌ Código incorrecto o caducado. Intenta nuevamente.';
        }
    } else {
        // Si no se encuentra el usuario o no tiene 2FA configurado.
        $msg = 'Error: no se encontró el secreto de este usuario.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"> <!-- Define la codificación de caracteres a UTF-8. -->
  <title>Verificación 2FA</title> <!-- Título de la página. -->
  <link rel="stylesheet" href="style.css"> <!-- Enlaza la hoja de estilos CSS. -->
</head>
<body>
<div class="container">
  <h2>Verificación en dos pasos</h2>

  <!-- Muestra mensajes de error o avisos en un div con clase 'error'. -->
  <?php if ($msg): ?>
    <div class="message error"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <p class="note">Abre tu aplicación <b>Google Authenticator</b> y escribe el código que aparece.</p>

  <!-- Formulario para que el usuario introduzca el código 2FA. -->
  <form method="POST" autocomplete="off">
    <label for="codigo">Código de verificación:</label>
    <!-- El campo de entrada está configurado para 6 dígitos numéricos. -->
    <input type="text" name="codigo" id="codigo" required maxlength="6" pattern="\d{6}" placeholder="Ejemplo: 123456">
    <button type="submit">Verificar código</button>
  </form>
</div>
</body>
</html>