<?php
// config/env.php

// estes archivo me carga la configuración global (JWT, cookies, seguridad, etc.)
return [
  //  Configuración de la base de datos
  'db' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'projectUnahSistems',
    'user' => 'milton',
    'pass' => '12345',   // recuerden cambiarlo segun su entorno
    'charset' => 'utf8mb4'
  ],
  

  //  Configuración JWT
  'jwt' => [
    'secret' => 'change_this_super_secret_key_64chars_minimum',
    'issuer' => 'projectUnahSistems.local',
    'audience' => 'projectUnahSistems.client',
    'expiresIn' => 60 * 60 * 2 // 2 horas
  ],

  // Seguridad (sal + pimienta)
  'security' => [
    'pepper' => 'change_this_long_random_pepper_value_2025'
  ],

  // Configuración de cookies
  'cookies' => [
    'accessToken' => [
      'name' => 'accessToken',
      'path' => '/',
      'secure' => false,   // true en producción (HTTPS) una vez subido a un servidor
      'httpOnly' => true,
      'sameSite' => 'Lax'
    ]
  ]
];
