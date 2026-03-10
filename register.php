<?php
	include("db_connect.php");
	session_start();
	
	if(isset($_POST['submit']))
	{	
		$name = $conn->real_escape_string($_POST['name']);
		$email = $conn->real_escape_string($_POST['email']);
		$password = $conn->real_escape_string($_POST['password']);
		$college = $conn->real_escape_string($_POST['college']);
		
		$str="SELECT id from users WHERE email='$email'";
		$result=$conn->query($str);
		
		if($result->num_rows > 0)	
		{
            echo "<script>alert('Lo siento.. Este correo ya está registrado!'); window.location='login.php';</script>";
        }
		else
		{
            $str="INSERT INTO users (name,email,password,address,user_type) VALUES ('$name','$email','$password','$college', 3)";
			if($conn->query($str))	
			{
				echo "<script>alert('¡Felicidades! Te has registrado exitosamente!'); window.location='login.php';</script>";
			} else {
				echo "<script>alert('Error al registrar: " . $conn->error . "');</script>";
			}
		}
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Registro | Sistema de Cuestionarios</title>
	<?php include('header.php') ?>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
        }
        .form-group label {
            font-weight: 600;
            color: #2C3E50;
        }
    </style>
</head>

<body>
	<div class="register-container">
        <h1 class="h3 font-weight-normal text-center mb-4">Registrarse</h1>
        <p class="text-center text-muted mb-4">Crea una nueva cuenta</p>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="nombre">Nombre Completo:</label>
                <input type="text" id="nombre" name="name" autocomplete="name" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico:</label>
                <input type="email" id="email" name="email" autocomplete="email" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" autocomplete="new-password" class="form-control" required />
            </div>
            <div class="form-group">
                <label for="college">Institución/Centro:</label>
                <input type="text" id="college" name="college" class="form-control" required />
            </div>
            
            <button class="btn btn-primary btn-block btn-lg mt-4" name="submit" type="submit">Registrarse</button>
            
            <div class="text-center mt-3">
                <p class="text-muted">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión Aquí</a></p>
            </div>
        </form>
    </div>
</body>
</html>
