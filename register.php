<?php
require __DIR__ . '/db.php'; // Incluye el archivo de conexión a la base de datos.

$msg = ''; // Variable para almacenar mensajes de error o aviso al usuario.

// Comprueba si el formulario de registro ha sido enviado (método POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Recopila los valores enviados desde el formulario.
    // trim() elimina espacios en blanco al principio y al final.
    // ?? '' proporciona un valor predeterminado (cadena vacía) si la variable no existe.
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    // 2. Realiza validaciones básicas de los datos de entrada.

    // Comprueba si algún campo obligatorio está vacío.
    if ($username === '' || $password === '' || $confirm === '') {
        $msg = 'Todos los campos son obligatorios.';
    } 
    // Comprueba si las contraseñas no coinciden.
    elseif ($password !== $confirm) {
        $msg = 'Las contraseñas no coinciden.';
    } 
    // Valida la longitud del nombre de usuario.
    elseif (strlen($username) < 3 || strlen($username) > 50) {
        $msg = 'El usuario debe tener entre 3 y 50 caracteres.';
    } 
    // Valida la longitud mínima de la contraseña.
    elseif (strlen($password) < 4) {
        $msg = 'La contraseña debe tener al menos 4 caracteres.';
    } 
    else {
        // Si todas las validaciones básicas pasan, procede a verificar la disponibilidad del usuario.

        // 3. Comprueba si el nombre de usuario ya existe en la base de datos.
        $chk = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
        $chk->execute([$username]); // Ejecuta la consulta con el nombre de usuario propuesto.

        if ($chk->fetch()) {
            // Si $chk->fetch() devuelve una fila, significa que el usuario ya existe.
            $msg = "El usuario '{$username}' ya existe.";
        } else {
            // Si el nombre de usuario no existe, procede a registrar al nuevo usuario.

            // 4. Hashea la contraseña antes de almacenarla en la base de datos.
            // password_hash() es una función segura para hashear contraseñas.
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 5. Prepara e inserta los datos del nuevo usuario en la tabla 'users'.
            $ins  = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->execute([$username, $hash]); // Ejecuta la inserción.

            // 6. Redirige al usuario a la página de login con un mensaje de éxito.
            // El parámetro 'registered=1' se usará en login.php para mostrar el aviso.
            header('Location: login.php?registered=1');
            exit; // Termina la ejecución del script después de la redirección.
        }
    }
}
?>
<!-- Vista (HTML) para la página de registro -->
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"> <!-- Define la codificación de caracteres. -->
  <title>Registro</title> <!-- Título de la página. -->
  <link rel="stylesheet" href="style.css"> <!-- Enlaza la hoja de estilos CSS. -->
</head>
<body>
<div class="container">
  <h2>Crear cuenta</h2>

  <!-- Muestra mensajes de error o aviso en un div con clase 'error'. -->
  <?php if ($msg): ?>
    <div class="message error"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <!-- Formulario de registro -->
  <form method="POST" action="register.php" autocomplete="off">
    <label for="username">Usuario:</label>
    <input type="text" name="username" id="username" required>

    <label for="password">Contraseña:</label>
    <input type="password" name="password" id="password" required minlength="4">

    <label for="confirm">Confirmar contraseña:</label>
    <input type="password" name="confirm" id="confirm" required minlength="4">

    <button type="submit">Registrarme</button>
  </form>

  <p class="note"><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p> <!-- Enlace a la página de inicio de sesión. -->
</div>
</body>
</html>