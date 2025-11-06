<?php
session_start(); // Inicia la sesión para almacenar información del usuario entre peticiones.
require __DIR__ . '/db.php'; // Incluye el archivo de conexión a la base de datos.

$notice = ''; // Variable para mensajes de éxito o información al usuario.
$error  = ''; // Variable para mensajes de error.

// Verifica si se ha recibido un parámetro 'registered' en la URL.
// Esto indica que el usuario acaba de registrarse y muestra un mensaje de éxito.
if (isset($_GET['registered'])) {
    $notice = 'Registro exitoso. Ya puedes iniciar sesión.';
}

// Mensaje de éxito si 2FA fue habilitado y el usuario es redirigido aquí.
if (isset($_GET['2fa_enabled']) && $_GET['2fa_enabled'] == 1) {
    $notice = 'Autenticación de dos factores habilitada con éxito. Por favor, inicia sesión.';
}


// Comprueba si el formulario de inicio de sesión ha sido enviado (método POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtiene y limpia el nombre de usuario y la contraseña del formulario.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $status = 'fail'; // Por defecto, el intento de inicio de sesión se considera fallido.

    // Procede solo si el nombre de usuario y la contraseña no están vacíos.
    if ($username !== '' && $password !== '') {
        // 1. Buscamos el usuario en la base de datos, incluyendo su clave secreta 2FA.
        $stmt = $pdo->prepare("SELECT username, password, secret_2fa FROM users WHERE username = ?");
        $stmt->execute([$username]); // Ejecuta la consulta con el nombre de usuario.
        $user = $stmt->fetch(); // Obtiene la fila del usuario.

        // 2. Verificamos la contraseña hasheada almacenada en la BD con la proporcionada.
        if ($user && password_verify($password, $user['password'])) {
            // Si la contraseña es correcta:

            // 3. Comprobamos si el usuario tiene la autenticación de dos factores (2FA) configurada.
            if (!empty($user['secret_2fa'])) {
                // Si el 2FA está configurado, no iniciamos sesión directamente.
                // Guardamos el nombre de usuario en una sesión temporal ('pending_user').
                $_SESSION['pending_user'] = $user['username'];
                // Redirigimos al usuario a la página de verificación 2FA.
                header('Location: verificar_2fa.php');
                exit; // Detiene la ejecución.
            } else {
                // Si el 2FA NO está habilitado para este usuario, inicia sesión normalmente.
                $_SESSION['user'] = $user['username']; // Guarda el nombre de usuario en la sesión.
                $status = 'success'; // Marca el intento como exitoso.

                // Registra el intento de inicio de sesión exitoso en la tabla de auditoría.
                $pdo->prepare("INSERT INTO login_audit (username, status) VALUES (?, ?)")
                    ->execute([$username, $status]);

                header('Location: bloque_seguridad.php'); // Redirige a la zona segura.
                exit; // Detiene la ejecución.
            }
        }
    }

    // Si el código llega a este punto, significa que el intento fue fallido
    // Registra el intento fallido en la tabla de auditoría.
    // Usamos $username ?: null para guardar NULL si el username estaba vacío.
    $pdo->prepare("INSERT INTO login_audit (username, status) VALUES (?, ?)")
        ->execute([$username ?: null, $status]);

    $error = 'Usuario o contraseña incorrectos.'; // Mensaje de error genérico.
}
?>
<!-- Vista HTML para la página de inicio de sesión -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"> <!-- Define la codificación de caracteres. -->
  <title>Login</title> <!-- Título de la página. -->
  <link rel="stylesheet" href="style.css"> <!-- Enlaza la hoja de estilos CSS. -->
</head>
<body>
<div class="container">
  <h2>Iniciar sesión</h2>

  <!-- Muestra el mensaje de éxito o información -->
  <?php if ($notice): ?>
    <div class="message success"><?php echo htmlspecialchars($notice); ?></div>
  <?php endif; ?>

  <!-- Muestra el mensaje de error -->
  <?php if ($error): ?>
    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <!-- Formulario de inicio de sesión -->
  <form method="POST" action="login.php" autocomplete="off">
    <label for="username">Usuario:</label>
    <input type="text" name="username" id="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">

    <label for="password">Contraseña:</label>
    <input type="password" name="password" id="password" required>

    <button type="submit">Entrar</button>
  </form>

  <p class="note"><a href="register.php">¿No tienes cuenta? Regístrate</a></p> <!-- Enlace a la página de registro. -->
</div>
</body>
</html>