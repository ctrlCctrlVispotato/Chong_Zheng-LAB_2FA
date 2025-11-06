<?php
// Configuración de la conexión a MySQL
$DB_HOST = '127.0.0.1';          // Servidor (localhost o 127.0.0.1)
$DB_NAME = 'bd_Sesiones_lab';    // Nombre de tu base de datos
$DB_USER = 'root';               // Usuario de MySQL (en XAMPP es root)
$DB_PASS = '';                   // Contraseña (en XAMPP normalmente está vacía)
$DB_PORT = '3306';               // Puerto de MySQL

// DSN (Data Source Name): contiene la información de conexión
$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

try {
    // Se crea el objeto PDO (conexión)
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errores lanzan excepción
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // resultados como arreglos asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // usar consultas preparadas reales
    ]);
} catch (PDOException $e) {
    // Si falla la conexión, se detiene todo
    exit('Error de conexión a MySQL.');
}
