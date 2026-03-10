
<!DOCTYPE html>
<html lang="es">
<head>
	<?php include('header.php') ?>
	<?php 
	session_start();
	if(isset($_SESSION['login_id'])){
		header('Location:home.php');
	}
	?>
	<title>Iniciar Sesión | Sistema de Cuestionarios</title>
	<style>
		html, body {
			height: 100%;
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		}
		body {
			padding-top: 0;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.signin-container {
			width: 100%;
			max-width: 420px;
			padding: 40px;
			background: white;
			border-radius: 12px;
			box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
		}
		.signin-logo {
			text-align: center;
			margin-bottom: 30px;
		}
		.signin-logo img {
			width: 80px;
			height: 80px;
			border-radius: 8px;
		}
		.signin-title {
			text-align: center;
			margin-bottom: 30px;
			font-size: 28px;
			font-weight: 700;
			color: #2C3E50;
		}
		.form-group {
			margin-bottom: 18px;
		}
		.form-control {
			height: 45px;
			font-size: 14px;
			border: 1px solid #e0e0e0;
			border-radius: 8px;
			padding: 10px 15px;
			transition: all 0.3s ease;
		}
		.form-control:focus {
			border-color: #667eea;
			box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
		}
		.form-check {
			margin-bottom: 20px;
		}
		.form-check-input {
			width: 18px;
			height: 18px;
			margin-top: 3px;
		}
		.form-check-label {
			margin-left: 8px;
			color: #555;
		}
		.btn-signin {
			height: 45px;
			font-size: 16px;
			font-weight: 600;
			letter-spacing: 0.5px;
			text-transform: uppercase;
			border-radius: 8px;
		}
		.signin-footer {
			text-align: center;
			margin-top: 25px;
			padding-top: 20px;
			border-top: 1px solid #e0e0e0;
		}
		.signin-footer a {
			color: #667eea;
			text-decoration: none;
			font-weight: 500;
		}
		.signin-footer a:hover {
			text-decoration: underline;
		}
		.signin-copyright {
			text-align: center;
			margin-top: 20px;
			font-size: 12px;
			color: #999;
		}
		.alert {
			margin-bottom: 20px;
			border-radius: 8px;
		}
	</style>
</head>

<body>
	<div class="signin-container">		
		
		<h1 class="signin-title">Inicia Sesión</h1>
		
		<form id="login-frm">
			<div class="form-group">
				<input type="text" id="inputUsername" name="username" autocomplete="username" class="form-control" placeholder="Nombre de usuario" required autofocus>
			</div>
			<div class="form-group">
				<input type="password" id="inputPassword" name="password" autocomplete="current-password" class="form-control" placeholder="Contraseña" required>
			</div>
			<div class="form-check">
				<input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
				<label class="form-check-label" for="rememberMe">Recuérdame</label>
			</div>
			<button class="btn btn-primary btn-block btn-signin" type="submit">Ingresar</button>
		</form>
		
		<p class="signin-copyright">&copy; <?php echo date('Y') ?> Sistema de Cuestionarios</p>
	</div>

	<script>
		$(document).ready(function(){
			$('#login-frm').submit(function(e){
				e.preventDefault()
				const $btn = $('#login-frm button');
				$btn.prop('disabled', true);
				$btn.html('<i class="fa fa-spinner fa-spin"></i> Por favor espere...');

				$.ajax({
					url:'./login_auth.php',
					method:'POST',
					data:$(this).serialize(),
					error:err=>{
						console.log(err);
						alert('Ocurrió un error en la conexión');
						$btn.prop('disabled', false);
						$btn.html('Ingresar');
					},
					success:function(resp){
						if(resp == 1){
							location.replace('home.php');
						}else{
							alert("Usuario o contraseña incorrectos.");
							$btn.prop('disabled', false);
							$btn.html('Ingresar');
						}
					}
				});
			});
		});
	</script>
</body>
</html>
