# Quiz - Agentes de Código

## Visión General

Sistema de quiz/exámenes en PHP puro con AdminLTE. Gestiona cuestionarios, evaluaciones, estudiantes, niveles y facultades.

## Estructura del Proyecto

```
├── basedatos/                          # Archivos de base de datos
├── assets/                             # Recursos estáticos
├── fonts/                              # Fuentes
├── image/                              # Imágenes
│
├── Principales
│   ├── cuestionarios.php               # Gestión de cuestionarios
│   ├── evaluacion.php                   # Gestión de evaluaciones
│   ├── quiz.php                         # Quiz principal
│   ├── create_quiz.php                  # Crear quiz
│   └── quiz_category.php               # Categorías de quiz
│
├── Estudiantes
│   ├── home.php                        # Página principal estudiante
│   ├── student.php                      # CRUD estudiantes
│   ├── student_quiz_list.php           # Lista de quizzes estudiante
│   ├── student_evaluacion_list.php    # Lista de evaluaciones
│   ├── take_evaluacion.php             # Tomar evaluación
│   └── submit_evaluacion.php           # Enviar evaluación
│
├── Admin
│   ├── admin.php                        # Panel admin
│   ├── dashboard.php                    # Dashboard admin
│   ├── levels.php                       # CRUD niveles
│   ├── faculty.php                      # CRUD facultades
│   ├── get_*.php                        # Endpoints AJAX
│   └── save_*.php                       # Guardar entidades
│
├── Exámenes
│   ├── get_evaluacion.php              # Obtener evaluación
│   ├── save_evaluacion.php             # Guardar evaluación
│   ├── submit_evaluacion.php          # Enviar respuestas
│   └── delete_evaluacion.php          # Eliminar evaluación
│
└── db_connect.php                       # Conexión a BD
```

## Rutas Principales

```php
index.php              # Redirección
login.php             # Login
home.php              # Home estudiante
quiz.php             # Quiz principal
take_evaluacion.php  # Tomar evaluación
admin.php            # Panel admin
dashboard.php        # Dashboard
```

## Entidades Principales

- **Quiz** - Cuestionarios con preguntas
- **Evaluación** - Exámenes para estudiantes
- **Student** - Estudiantes
- **Level** - Niveles
- **Faculty** - Facultades
- **Quiz_Category** - Categorías

## Reglas de Código

- Usar `db_connect.php` para conexión a BD
- Endpoints AJAX en `get_*.php`, `save_*.php`, `delete_*.php`
- AdminLTE para interfaces admin
- Sesiones PHP para autenticación
