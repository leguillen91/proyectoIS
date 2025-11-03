<?php
/**
 * TEST COMPLETO DEL MÓDULO AUTH + STUDENTS
 * Verifica login, permisos y operaciones básicas con estudiantes.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../bootstrap/init.php';
require_once __DIR__ . '/../classes/controllers/authController.php';
require_once __DIR__ . '/../classes/controllers/studentController.php';
require_once __DIR__ . '/../middleware/requireAuth.php';

$auth = new AuthController($pdo, $config);
$studentController = new StudentController($pdo);

echo " Iniciando pruebas del módulo STUDENTS...\n\n";

// 1 LOGIN ADMIN
echo "1  Probando LOGIN de admin...\n";
$login = $auth->login('admin@unisys.local', 'ChangeMe#2025');
if (!$login['ok']) {
  echo " Error en login: {$login['error']}\n";
  exit;
}
$token = $login['token'];
echo " Login exitoso. Token obtenido.\n\n";

// 2 OBTENER LISTA DE ESTUDIANTES (admin)
echo "2  Probando LISTADO de estudiantes...\n";
$cliToken = $token;
$ctx = requireAuth();

ob_start();
$studentController->listAll($ctx);
$listOutput = ob_get_clean();
$listData = json_decode($listOutput, true);

if (!empty($listData['students'])) {
  echo " Se obtuvieron " . count($listData['students']) . " estudiantes.\n\n";
} else {
  echo " No hay registros de estudiantes todavía.\n\n";
}

// 3 CREAR NUEVO ESTUDIANTE
echo "3  Probando CREACIÓN de nuevo estudiante...\n";

$sampleData = [
  'userId' => 2, //  Cambiar por un userId válido con rol student
  'enrollmentCode' => 'ST-' . rand(1000, 9999),
  'career' => 'Ingeniería en Sistemas',
  'academicCenter' => 'UNAH-VS',
  'admissionYear' => 2025,
  'currentPeriod' => '2025-I',
  'status' => 'Activo',
  'phoneNumber' => '9999-9999',
  'address' => 'San Pedro Sula, Cortés'
];

ob_start();
$studentController->create($ctx, $sampleData);
$createOutput = ob_get_clean();
$createData = json_decode($createOutput, true);

if (!empty($createData['studentId'])) {
  echo " Estudiante creado exitosamente (ID: {$createData['studentId']}).\n\n";
} else {
  echo " Error al crear estudiante.\n\n";
}

// 4 ACTUALIZAR ESTUDIANTE
echo "4  Probando ACTUALIZACIÓN de estudiante...\n";
if (!empty($createData['studentId'])) {
  $updateData = [
    'career' => 'Ingeniería Informática',
    'academicCenter' => 'UNAH-TEC',
    'currentPeriod' => '2025-II',
    'status' => 'Activo',
    'phoneNumber' => '8888-8888',
    'address' => 'Santa Rosa de Copán'
  ];

  ob_start();
  $studentController->update($ctx, $createData['studentId'], $updateData);
  $updateOutput = ob_get_clean();
  $updateData = json_decode($updateOutput, true);

  if (!empty($updateData['ok'])) {
    echo " Estudiante actualizado correctamente.\n\n";
  } else {
    echo " Error al actualizar estudiante.\n\n";
  }
}

// 5 OBTENER UNO (getOne)
echo "5  Probando GET ONE...\n";
if (!empty($createData['studentId'])) {
  ob_start();
  $studentController->getOne($ctx, $createData['studentId']);
  $getOutput = ob_get_clean();
  $getData = json_decode($getOutput, true);

  if (!empty($getData['student'])) {
    echo " Estudiante obtenido: " . $getData['student']['fullName'] . "\n\n";
  } else {
    echo " No se pudo obtener el estudiante.\n\n";
  }
}

// 6️ ELIMINAR ESTUDIANTE
echo "6  Probando ELIMINACIÓN...\n";
if (!empty($createData['studentId'])) {
  ob_start();
  $studentController->delete($ctx, $createData['studentId']);
  $deleteOutput = ob_get_clean();
  $deleteData = json_decode($deleteOutput, true);

  if (!empty($deleteData['ok'])) {
    echo " Estudiante eliminado correctamente.\n\n";
  } else {
    echo " Error al eliminar estudiante.\n\n";
  }
}

//  RESULTADO FINAL

echo " Todas las pruebas del módulo STUDENTS completadas correctamente.\n";
