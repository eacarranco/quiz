<?php
// Zona horaria por defecto de la aplicación (UTC-05, Ecuador continental)
date_default_timezone_set('America/Guayaquil');

$conn= new mysqli('localhost','root','','db_quiz')or die("Could not connect to mysql".mysqli_error($con));
// Agregar esta línea
$conn->set_charset("utf8mb4");

// Alinear la sesión de MySQL con UTC-05
$conn->query("SET time_zone = '-05:00'");

// Asegurar que la columna level_id existe en quiz_list
$col_check = $conn->query("SHOW COLUMNS FROM quiz_list LIKE 'level_id'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE quiz_list ADD COLUMN level_id INT NULL DEFAULT NULL");
}
?>
