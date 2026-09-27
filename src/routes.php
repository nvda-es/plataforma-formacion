<?php
declare(strict_types=1);

use App\Controllers\Admin\CourseController as AdminCourseController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\ItemController as AdminItemController;
use App\Controllers\Admin\ModuleController as AdminModuleController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\AuthController;
use App\Controllers\CourseController;
use App\Controllers\HomeController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Router;

$router = new Router();

// Catálogo público
$router->get('/', [HomeController::class, 'index']);

// Registro, acceso y recuperación de cuenta
$router->get('/registro', [AuthController::class, 'showRegister']);
$router->post('/registro', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/verificar-email', [AuthController::class, 'verify']);
$router->get('/reenviar-verificacion', [AuthController::class, 'showResend']);
$router->post('/reenviar-verificacion', [AuthController::class, 'resend']);
$router->get('/recuperar-contrasena', [AuthController::class, 'showForgot']);
$router->post('/recuperar-contrasena', [AuthController::class, 'forgot']);
$router->get('/restablecer-contrasena', [AuthController::class, 'showReset']);
$router->post('/restablecer-contrasena', [AuthController::class, 'reset']);

// Cursos y contenidos (alumnos)
$router->get('/cursos/{slug}', [CourseController::class, 'show']);
$router->post('/cursos/{slug}/matricula', [CourseController::class, 'enroll']);
$router->get('/cursos/{slug}/contenido/{id}', [CourseController::class, 'item']);
$router->post('/cursos/{slug}/contenido/{id}/respuesta', [CourseController::class, 'submit']);
$router->get('/mis-cursos', [StudentController::class, 'dashboard']);

// Corrección (profesores y administradores)
$router->get('/profesor', [TeacherController::class, 'index']);
$router->get('/profesor/entregas/{id}', [TeacherController::class, 'show']);
$router->post('/profesor/entregas/{id}', [TeacherController::class, 'grade']);

// Administración
$router->get('/admin', [AdminDashboardController::class, 'index']);

$router->get('/admin/usuarios', [AdminUserController::class, 'index']);
$router->get('/admin/usuarios/nuevo', [AdminUserController::class, 'create']);
$router->post('/admin/usuarios', [AdminUserController::class, 'store']);
$router->get('/admin/usuarios/{id}/editar', [AdminUserController::class, 'edit']);
$router->post('/admin/usuarios/{id}', [AdminUserController::class, 'update']);
$router->post('/admin/usuarios/{id}/enviar-acceso', [AdminUserController::class, 'sendAccess']);
$router->post('/admin/usuarios/{id}/matriculas', [AdminUserController::class, 'enroll']);
$router->post('/admin/usuarios/{id}/matriculas/{courseId}/eliminar', [AdminUserController::class, 'unenroll']);

$router->get('/admin/cursos', [AdminCourseController::class, 'index']);
$router->get('/admin/cursos/nuevo', [AdminCourseController::class, 'create']);
$router->post('/admin/cursos', [AdminCourseController::class, 'store']);
$router->get('/admin/cursos/importar', [AdminCourseController::class, 'importForm']);
$router->post('/admin/cursos/importar', [AdminCourseController::class, 'import']);
$router->get('/admin/cursos/{id}', [AdminCourseController::class, 'show']);
$router->get('/admin/cursos/{id}/editar', [AdminCourseController::class, 'edit']);
$router->post('/admin/cursos/{id}', [AdminCourseController::class, 'update']);
$router->post('/admin/cursos/{id}/eliminar', [AdminCourseController::class, 'destroy']);

$router->get('/admin/cursos/{id}/modulos/nuevo', [AdminModuleController::class, 'create']);
$router->post('/admin/cursos/{id}/modulos', [AdminModuleController::class, 'store']);
$router->get('/admin/modulos/{id}/editar', [AdminModuleController::class, 'edit']);
$router->post('/admin/modulos/{id}', [AdminModuleController::class, 'update']);
$router->post('/admin/modulos/{id}/eliminar', [AdminModuleController::class, 'destroy']);
$router->post('/admin/modulos/{id}/mover', [AdminModuleController::class, 'move']);

$router->get('/admin/modulos/{id}/contenidos/nuevo', [AdminItemController::class, 'create']);
$router->post('/admin/modulos/{id}/contenidos', [AdminItemController::class, 'store']);
$router->get('/admin/contenidos/{id}/editar', [AdminItemController::class, 'edit']);
$router->post('/admin/contenidos/{id}', [AdminItemController::class, 'update']);
$router->post('/admin/contenidos/{id}/eliminar', [AdminItemController::class, 'destroy']);
$router->post('/admin/contenidos/{id}/mover', [AdminItemController::class, 'move']);

return $router;
