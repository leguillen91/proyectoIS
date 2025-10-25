<?php
/* Objetivo del archivo
Encargarse de:

Generar un salt único por usuario.
Combinar salt + pepper + password.
Aplicar el hash Argon2id.
Verificar contraseñas en login. */

class AuthService {
  private string $pepper;

  public function __construct(string $pepper) {
    $this->pepper = $pepper;
  }

  /**
   * Genera un salt único para cada usuario.
   * Retorna un string hexadecimal (64 caracteres = 32 bytes).
   */
  public function generateSalt(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
  }

  /**
   * Aplica hash Argon2id combinando salt + pepper + password.
   */
  public function hashPassword(string $password, string $salt): string {
    $toHash = $salt . $this->pepper . $password;
    return password_hash($toHash, PASSWORD_ARGON2ID);
  }

  /**
   * Verifica que la contraseña ingresada coincida con el hash almacenado.
   */
  public function verifyPassword(string $password, string $salt, string $storedHash): bool {
    $toVerify = $salt . $this->pepper . $password;
    return password_verify($toVerify, $storedHash);
  }

  /**
   * Permite verificar si el hash requiere ser actualizado (opcional).
   */
  public function needsRehash(string $storedHash): bool {
    return password_needs_rehash($storedHash, PASSWORD_ARGON2ID);
  }
}
