<?php
session_start(); // Inicia la sesión para acceder a las variables de sesión.

// Verificación de seguridad: Si no hay un usuario en la sesión,
// Significa que el usuario no ha iniciado sesión.
// En este caso, se le redirige a la página de login.
if (!isset($_SESSION['user'])) {
    header('Location: login.php'); // Redirige al usuario a login.php.
    exit; // Termina la ejecución del script para evitar que se muestre contenido no autorizado.
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"> <!-- Define la codificación de caracteres a UTF-8 para evitar problemas con acentos y caracteres especiales. -->
  <title>Zona Protegida</title> <!-- Título que aparece en la pestaña del navegador. -->
  <link rel="stylesheet" href="style.css"> <!-- Enlaza la hoja de estilos CSS para dar formato a la página. -->
</head>
<body>
<div class="container"> <!-- Contenedor principal para centrar y estilizar el contenido. -->
  <h2>Hola, <?php echo htmlspecialchars($_SESSION['user']); ?> 👋</h2> <!-- Saluda al usuario, mostrando su nombre de usuario de la sesión. htmlspecialchars() previene ataques XSS. -->
  <p class="note">Has iniciado sesión correctamente.</p> <!-- Mensaje de confirmación de inicio de sesión. -->
  
  <?php 
  // Comprueba si la URL contiene el parámetro '2fa_enabled' y si su valor es '1'.
  // Esto se usa para mostrar un mensaje de éxito después de que el usuario habilita 2FA.
  if (isset($_GET['2fa_enabled']) && $_GET['2fa_enabled'] == 1): 
  ?>
    <div class="message success">¡Autenticación de Dos Factores habilitada con éxito!</div> <!-- Mensaje de éxito para 2FA. -->
  <?php endif; ?>

  <p class="note">
    <!-- Enlace a la página de configuración de 2FA.
         Permite al usuario activar o gestionar su autenticación de dos factores. -->
    <a href="configurar_2fa.php">Gestionar Autenticación de Dos Factores (2FA)</a>
  </p>
  
  <!-- Botón de cerrar sesión. Cuando se hace clic, redirige a 'logout.php' para destruir la sesión. -->
  <button onclick="location.href='logout.php'">Cerrar sesión</button>
</div>
</body>
</html>