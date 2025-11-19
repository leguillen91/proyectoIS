<?php
// scripts/createTeachers.php

require_once __DIR__ . '/../config/connection.php';
$config = require __DIR__ . '/../config/env.php';

require_once __DIR__ . '/../classes/services/authService.php';
require_once __DIR__ . '/../classes/models/userModel.php';

$authService = new AuthService($config['security']['pepper']);
$userModel = new UserModel($pdo);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Seed iniciado...<br>";

// OBTENER rol teacher
$stmt = $pdo->prepare("SELECT id FROM roles WHERE roleName = 'teacher' LIMIT 1");
$stmt->execute();
$role = $stmt->fetch();

if (!$role) {
  die("❌ No existe el rol 'teacher'.\n");
}

$roleId = (int)$role['id'];

$teachers = [
  ["Carlos Alberto Medina", "carlos.medina@unah.edu", "EMP1001", "Ingeniería en Sistemas", "CU Tegucigalpa"],
  ["María Fernanda Reyes", "maria.reyes@unah.edu", "EMP1002", "Licenciatura en Informática", "CU Tegucigalpa"],
  ["José Alejandro López", "jose.lopez@unah.edu", "EMP1003", "Ingeniería en Sistemas", "CU SPS"],
  ["Karla Patricia Rivera", "karla.rivera@unah.edu", "EMP1004", "Ingeniería en Sistemas", "CU Tegucigalpa"],
  ["Daniel Arturo Molina", "daniel.molina@unah.edu", "EMP1005", "Música", "CU Tegucigalpa"],
  ["Sofía Hernández", "sofia.hernandez@unah.edu", "EMP1006", "Música", "CU Tegucigalpa"],
  ["Luis Enrique Castillo", "luis.castillo@unah.edu", "EMP1007", "Ingeniería en Sistemas", "CU Choluteca"],
  ["Ana Gabriela Fúnez", "ana.funez@unah.edu", "EMP1008", "Lic. Informática", "CU Comayagua"],
  ["Ricardo Bardales", "ricardo.bardales@unah.edu", "EMP1009", "Ing. Sistemas", "CU Juticalpa"],
  ["Paola Lagos", "paola.lagos@unah.edu", "EMP1010", "Lic. Informática", "CU Tegucigalpa"],
  ["Javier Pineda", "javier.pineda@unah.edu", "EMP1011", "Ing. Sistemas", "CU SPS"],
  ["Michelle Caballero", "michelle.caballero@unah.edu", "EMP1012", "Música", "CU Tegucigalpa"],
  ["Carlos Barahona", "carlos.barahona@unah.edu", "EMP1013", "Ing. Sistemas", "CU Danlí"],
  ["Diana Mejía", "diana.mejia@unah.edu", "EMP1014", "Lic. Informática", "CU La Ceiba"],
  ["Fernando Álvarez", "fernando.alvarez@unah.edu", "EMP1015", "Ing. Sistemas", "CU Tegucigalpa"],
  ["Lorena Orellana", "lorena.orellana@unah.edu", "EMP1016", "Música", "CU Tegucigalpa"],
  ["Marco Romero", "marco.romero@unah.edu", "EMP1017", "Ing. Sistemas", "CU SPS"],
  ["Gabriela Cáceres", "gabriela.caceres@unah.edu", "EMP1018", "Lic. Informática", "CU Tegucigalpa"],
  ["Roberto Sevilla", "roberto.sevilla@unah.edu", "EMP1019", "Ing. Sistemas", "CU Choluteca"],
  ["Valeria Quintanilla", "valeria.quintanilla@unah.edu", "EMP1020", "Música", "CU Tegucigalpa"],
];

// PASSWORD temporal para todos
$passwordPlain = "Docente#2025";

foreach ($teachers as $t) {

    [$fullName, $email, $employeeNumber, $career, $center] = $t;

    // 1) salt único
    $salt = $authService->generateSalt();

    // 2) hash Argon2id
    $hash = $authService->hashPassword($passwordPlain, $salt);

    try {

        // 3) crear usuario EXACTAMENTE igual que createAdmin
        $userId = $userModel->create(
            $fullName,
            $email,
            "",          // identityNumber (admin también lo deja vacío)
            null,        // accountNumber
            $roleId,
            $hash,
            $salt
        );

        // 4) insertar en teachers
        $userModel->createTeacher(
            $userId,
            $fullName,
            $employeeNumber,
            $career,
            $center
        );

        echo "✔ Docente creado: $fullName — userId=$userId\n";

    } catch (Exception $e) {
        echo "❌ Error creando $fullName: " . $e->getMessage() . "\n";
    }
}

echo "\n==== ✔ FINALIZADO: 20 DOCENTES CREADOS CORRECTAMENTE ====\n";
