<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($title) ? $title . ' | Sistema de Cuestionarios' : 'Sistema de Cuestionarios'; ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc4/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    :root {
      --quiz-primary: #0f4c81;
      --quiz-accent: #ff7a59;
      --quiz-surface: #f6f8fb;
      --quiz-sidebar-a: #0f4c81;
      --quiz-sidebar-b: #1d71b8;
      --quiz-topbar: #ffffff;
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: radial-gradient(circle at top right, #ebf4ff 0%, #f7f9fc 45%, #f5f7fb 100%);
      color: #243447;
    }

    .app-main {
      background: transparent;
    }

    .app-header {
      background: var(--quiz-topbar);
      border-bottom: 1px solid #e7ecf3;
      box-shadow: 0 8px 30px rgba(16, 44, 82, 0.08);
      backdrop-filter: blur(6px);
    }

    .app-sidebar {
      background: linear-gradient(165deg, var(--quiz-sidebar-a) 0%, var(--quiz-sidebar-b) 100%);
      box-shadow: 10px 0 30px rgba(15, 76, 129, 0.25);
      border-right: 0;
    }

    .brand-link {
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff !important;
      font-weight: 700;
      letter-spacing: 0.3px;
      min-height: 64px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .brand-link .brand-image {
      width: 34px;
      height: 34px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .sidebar-menu .nav-link {
      border-radius: 12px;
      margin: 2px 10px;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .sidebar-menu .nav-link:hover,
    .sidebar-menu .nav-link.active {
      color: #fff;
      background: rgba(255, 255, 255, 0.18);
      transform: translateX(2px);
    }

    .content-header {
      padding-bottom: 0.4rem;
    }

    .content-header h1 {
      font-weight: 800;
      color: #102c52;
      font-size: clamp(1.2rem, 1vw + 1rem, 1.7rem);
      letter-spacing: 0.2px;
      margin-bottom: 0;
    }

    .card {
      border: 0;
      border-radius: 16px;
      background: #ffffff;
      box-shadow: 0 12px 30px rgba(18, 39, 70, 0.08);
    }

    .card-header {
      border-bottom: 1px solid #edf1f7;
      background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
      border-top-left-radius: 16px !important;
      border-top-right-radius: 16px !important;
    }

    .card-title {
      font-weight: 700;
      color: #102c52;
      margin-bottom: 0;
    }

    .card-tools {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-left: auto;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--quiz-primary) 0%, #1d71b8 100%);
      border-color: transparent;
    }

    .btn-primary:hover,
    .btn-primary:focus {
      background: linear-gradient(135deg, #0c3d67 0%, #165f98 100%);
      border-color: transparent;
    }

    .main-footer {
      font-size: 0.9rem;
      color: #6b7785;
      border-top: 1px solid #e7ecf3;
      background: rgba(255, 255, 255, 0.85);
    }

    .select2-container {
      width: 100% !important;
    }

    .dropdown-menu {
      border: 1px solid #e9eef5;
      border-radius: 12px;
      box-shadow: 0 12px 30px rgba(16, 44, 82, 0.12);
    }
  </style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
  <nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Alternar barra lateral">
            <i class="fa-solid fa-bars"></i>
          </a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto">
        <li class="nav-item">          
          <a class="nav-link" href="logout.php" title="Cerrar sesión"><small><?php echo isset($_SESSION['login_user_type']) ? ($_SESSION['login_user_type'] == 1 ? 'Administrador' : ($_SESSION['login_user_type'] == 2 ? 'Profesor' : (isset($_SESSION['login_name']) ? $_SESSION['login_name'] : 'Estudiante'))) : ''; ?></small> <i class="fa-solid fa-right-from-bracket"></i></a>
        </li>
      </ul>
    </div>
  </nav>

  <aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
      <a href="home.php" class="brand-link">
        <img src="image/logo.png" alt="Quiz System" class="brand-image">
        <span class="brand-text fw-semibold">Cuestionarios</span>        
      </a>
    </div>
    <div class="sidebar-wrapper">
      <nav class="mt-2 sidebar-menu">
        <ul class="nav sidebar-menu flex-column" role="menu" data-lte-toggle="treeview">
          <?php if($_SESSION['login_user_type'] == 1): ?>
          <li class="nav-item">
            <a href="home.php" class="nav-link">
              <i class="nav-icon fa-solid fa-house"></i>
              <p>Inicio</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="faculty.php" class="nav-link">
              <i class="nav-icon fa-solid fa-chalkboard-user"></i>
              <p>Profesores</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="student.php" class="nav-link">
              <i class="nav-icon fa-solid fa-user-graduate"></i>
              <p>Estudiantes</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="levels.php" class="nav-link">
              <i class="nav-icon fa-solid fa-layer-group"></i>
              <p>Niveles</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fa-solid fa-list-check"></i>
              <p>
                Cuestionarios
                <i class="nav-arrow fa-solid fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="cuestionarios.php" class="nav-link">
                  <i class="nav-icon fa-regular fa-circle"></i>
                  <p>Listado</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="quiz_category.php" class="nav-link">
                  <i class="nav-icon fa-regular fa-circle"></i>
                  <p>Categorías</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="evaluacion.php" class="nav-link">
              <i class="nav-icon fa-solid fa-clipboard-question"></i>
              <p>Evaluación</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="history.php" class="nav-link">
              <i class="nav-icon fa-solid fa-clock-rotate-left"></i>
              <p>Historial</p>
            </a>
          </li>
          <?php elseif($_SESSION['login_user_type'] == 2): ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fa-solid fa-list-check"></i>
              <p>
                Cuestionarios
                <i class="nav-arrow fa-solid fa-angle-right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="cuestionarios.php" class="nav-link">
                  <i class="nav-icon fa-regular fa-circle"></i>
                  <p>Listado</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="quiz_category.php" class="nav-link">
                  <i class="nav-icon fa-regular fa-circle"></i>
                  <p>Categorías</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="evaluacion.php" class="nav-link">
              <i class="nav-icon fa-solid fa-clipboard-question"></i>
              <p>Evaluación</p>
            </a>
          </li>
          <?php else: ?>
          <li class="nav-item">
            <a href="home.php" class="nav-link">
              <i class="nav-icon fa-solid fa-house"></i>
              <p>Inicio</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="student_quiz_list.php" class="nav-link">
              <i class="nav-icon fa-solid fa-list-check"></i>
              <p>Mis Cuestionarios</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="student_evaluacion_list.php" class="nav-link">
              <i class="nav-icon fa-solid fa-clipboard-list"></i>
              <p>Mis Evaluaciones</p>
            </a>
          </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </aside>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col-sm-12">
            <h1><?php echo isset($title) ? $title : ''; ?></h1>
          </div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">

