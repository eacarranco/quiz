			<nav class="navbar-header">
			<div class="container-fluid d-flex justify-content-between align-items-center">
				<div class="navbar-brand">
					<p class="navbar-text text-white m-0" style="font-size: 18px;"><strong>Cuestionarios</strong></p>
				</div>
				<div class="nav navbar-nav ml-auto">
					<a href="logout.php" class="text-white" style="text-decoration: none; display: flex; align-items: center;"><span style="margin-right: 8px;"><?php echo $name ?></span><i class="fa fa-power-off"></i></a>
				</div>
			</div>
		</nav>
		<div id="sidebar" class="bg-dark">
			<style>
				.sidebar-submenu-title {
					display: flex;
					align-items: center;
					padding: 10px 12px;
					color: #fff;
					font-weight: 600;
				}

				.sidebar-submenu-title.active {
					background: rgba(255, 255, 255, 0.12);
				}

				.sidebar-submenu-links a {
					display: block;
					padding: 8px 12px 8px 40px;
					color: #d8e6f3;
					text-decoration: none;
					font-size: 14px;
				}

				.sidebar-submenu-links a:hover,
				.sidebar-submenu-links a.active {
					color: #fff;
					background: rgba(255, 255, 255, 0.12);
				}
			</style>
			<div id="sidebar-field">
				<a href="home.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-home"></i></div>  Inicio
				</a>
			</div>
			<?php if($_SESSION['login_user_type'] != 3): ?>
			<?php if($_SESSION['login_user_type'] == 1): ?>
			<div id="sidebar-field">
				<a href="faculty.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-users"></i></div>  Profesores
				</a>
			</div>
			<div id="sidebar-field">
				<a href="student.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-users"></i></div>  Estudiantes
				</a>
			</div>
			<?php endif; ?>
			<div id="sidebar-field">
				<div class="sidebar-submenu-title">
					<div class="sidebar-icon"><i class="fa fa-list"></i></div>&nbsp;Cuestionarios
				</div>
				<div class="sidebar-submenu-links">
					<a href="cuestionarios.php" class="sidebar-sub-item">Listado</a>
					<a href="quiz_category.php" class="sidebar-sub-item">Categorías</a>
				</div>
			</div>
			<div id="sidebar-field">
				<a href="evaluacion.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-clipboard"></i></div>  Evaluaciones
				</a>
			</div>
			<?php if($_SESSION['login_user_type'] == 1): ?>
			<div id="sidebar-field">
				<a href="history.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-history"></i></div>  Historial
				</a>
			</div>
			<?php endif; ?>
			<?php else: ?>
			<div id="sidebar-field">
				<a href="student_quiz_list.php" class="sidebar-item text-white">
						<div class="sidebar-icon"><i class="fa fa-list"></i></div>  Mis Cuestionarios
				</a>
			</div>
		<?php endif; ?>

		</div>
		<script>
			$(document).ready(function(){
				var loc = window.location.href;
				var page = loc.substr(loc.lastIndexOf("/") + 1);
				loc.split('{/}')
				$('#sidebar a').each(function(){
				// console.log(loc.substr(loc.lastIndexOf("/") + 1),$(this).attr('href'))
					if($(this).attr('href') == page){
						$(this).addClass('active')
					}
				})

				if(page === 'cuestionarios.php' || page === 'quiz_category.php'){
					$('.sidebar-submenu-title').addClass('active');
				}
			})
			
		</script>