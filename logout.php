<?php
session_start();     // Se reanuda la sesión para poder destruirla

// Limpiar específicamente variables de 2FA si existieran
if (isset($_SESSION['pending_user'])) { unset($_SESSION['pending_user']); }
if (isset($_SESSION['2fa_temp_secret'])) { unset($_SESSION['2fa_temp_secret']); }

session_destroy();   // Destruye todos los datos de sesión

header('Location: login.php'); // Redirige al login
exit;