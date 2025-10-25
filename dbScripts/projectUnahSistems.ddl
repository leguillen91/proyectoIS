-- ============================================================
--  DATABASE: projectUnahSistems
--  Sistema de autenticación con roles, permisos y revocación de tokens
-- ============================================================

CREATE DATABASE IF NOT EXISTS projectUnahSistems
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE projectUnahSistems;

-- ============================================================
--  TABLA: roles
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  roleName VARCHAR(50) NOT NULL UNIQUE,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLA: permissions
-- ============================================================
CREATE TABLE IF NOT EXISTS permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  permissionCode VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255)
);

-- ============================================================
--  TABLA: rolePermissions (N:M)
-- ============================================================
CREATE TABLE IF NOT EXISTS rolePermissions (
  roleId INT NOT NULL,
  permissionId INT NOT NULL,
  PRIMARY KEY (roleId, permissionId),
  CONSTRAINT fkRolePermRole FOREIGN KEY (roleId) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fkRolePermPerm FOREIGN KEY (permissionId) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================================
--  TABLA: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullName VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  identityNumber VARCHAR(30),
  accountNumber VARCHAR(30),
  roleId INT NOT NULL,
  passwordHash VARCHAR(255) NOT NULL,
  passwordSalt VARCHAR(64) NOT NULL,
  status TINYINT(1) DEFAULT 1,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fkUserRole FOREIGN KEY (roleId) REFERENCES roles(id)
);

-- ============================================================
--  TABLA: revokedTokens
-- ============================================================
CREATE TABLE IF NOT EXISTS revokedTokens (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  jti VARCHAR(64) NOT NULL UNIQUE,
  revokedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  DATOS INICIALES (ROLES Y PERMISOS)
-- ============================================================

-- Roles base
INSERT INTO roles (roleName) VALUES 
('student'), 
('coordinator'), 
('admin')
ON DUPLICATE KEY UPDATE roleName = roleName;

-- Permisos base
INSERT INTO permissions (permissionCode, description) VALUES
('library.view', 'Ver recursos'),
('library.download', 'Descargar recursos'),
('library.upload', 'Subir recursos'),
('library.edit', 'Editar recursos'),
('library.approve', 'Aprobar o validar recursos'),
('users.manage', 'Gestión de usuarios')
ON DUPLICATE KEY UPDATE permissionCode = permissionCode;

-- Asignación de permisos
-- student
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='student' AND p.permissionCode IN ('library.view','library.download');

-- coordinator
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='coordinator' AND p.permissionCode IN ('library.view','library.download','library.upload','library.edit','library.approve');

-- admin
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='admin';

-- ============================================================
--  ADMIN INICIAL (para pruebas)
-- ============================================================
-- Nota: este usuario se crea con el script createAdmin.php (PHP)
-- porque necesita salt + pepper + hash Argon2id
-- ------------------------------------------------------------
-- Email:    admin@unisys.local
-- Password: ChangeMe#2025
-- Rol:      admin
-- ------------------------------------------------------------

-- (El hash se genera dinámicamente en PHP con Argon2id)

-- ============================================================
--  VISTAS O CONSULTAS DE APOYO
-- ============================================================

-- Ver usuarios con su rol
CREATE OR REPLACE VIEW view_users_roles AS
SELECT u.id, u.fullName, u.email, r.roleName AS role, u.status, u.createdAt
FROM users u
JOIN roles r ON r.id = u.roleId
ORDER BY u.id ASC;

-- ============================================================
--  COMPROBACIÓN RÁPIDA
-- ============================================================
-- Para verificar usuarios:
-- SELECT * FROM view_users_roles;
--
-- Para ver roles y permisos:
-- SELECT r.roleName, p.permissionCode
-- FROM rolePermissions rp
-- JOIN roles r ON rp.roleId = r.id
-- JOIN permissions p ON rp.permissionId = p.id;
--
-- Para ver tokens revocados:
-- SELECT * FROM revokedTokens;
