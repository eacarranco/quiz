
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
	<title>Acceso de Administrador | Sistema de Cuestionarios</title>
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
		.signin-subtitle {
			text-align: center;
			margin-bottom: 25px;
			font-size: 14px;
			color: #999;
			text-transform: uppercase;
			letter-spacing: 1px;
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
		.btn-signin {
			height: 45px;
			font-size: 16px;
			font-weight: 600;
			letter-spacing: 0.5px;
			text-transform: uppercase;
			border-radius: 8px;
		}
		.signin-copyright {
			text-align: center;
			margin-top: 30px;
			font-size: 12px;
			color: #999;
		}
	</style>
</head>

<body>
	<div class="signin-container">		
		
		<h1 class="signin-title">Administrador</h1>
		<p class="signin-subtitle">Acceso Restringido</p>
		
		<form id="login-frm">
			<div class="form-group">
				<input type="text" id="inputUsername" name="username" autocomplete="username" class="form-control" placeholder="Nombre de usuario" required autofocus>
			</div>
			<div class="form-group">
				<input type="password" id="inputPassword" name="password" autocomplete="current-password" class="form-control" placeholder="Contraseña" required>
			</div>
			<button class="btn btn-primary btn-block btn-signin" type="submit">Acceder</button>
		</form>
		
		<p class="signin-copyright">&copy; <?php echo date('Y') ?> Sistema de Cuestionarios</p>
	</div>

	<script>
		$(document).ready(function(){
			$('#login-frm').submit(function(e){
				e.preventDefault();
				const $btn = $('#login-frm button');
				$btn.prop('disabled', true);
				$btn.html('<i class="fa fa-spinner fa-spin"></i> Por favor espere...');

				$.ajax({
					url:'./login_auth.php?type=1',
					method:'POST',
					data:$(this).serialize(),
					error:err=>{
						console.log(err);
						alert('Ocurrió un error en la conexión');
						$btn.prop('disabled', false);
						$btn.html('Acceder');
					},
					success:function(resp){
						if(resp == 1){
							location.replace('home.php');
						}else{
							alert("Usuario o contraseña incorrectos.");
							$btn.prop('disabled', false);
							$btn.html('Acceder');
						}
					}
				});
			});
		});
	</script>
</body>
</html>
