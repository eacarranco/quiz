<?php
$conn= new mysqli('localhost','root','','db_quiz')or die("Could not connect to mysql".mysqli_error($con));
// Agregar esta línea
$conn->set_charset("utf8mb4");
