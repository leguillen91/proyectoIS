-- Esta base de datos y sus tablas están diseñadas para un sistema de autenticación
-- que maneja roles, permisos y revocación de tokens JWT.

--tambien la utilizaremos como parte del proyecto final 
-- ============================================================
--  DATABASE: projectUnahSistems
--  Sistema de autenticación con roles, permisos y revocación de tokens
-- ============================================================

CREATE DATABASE IF NOT EXISTS projectUnahSistems
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE projectUnahSistems;

-- ============================================
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



-- ============================================================
--  Modulo software: proyectoUnahSistems
-- ============================================================

-- =========================================
-- MÓDULO DE SOFTWARE - DDL
-- =========================================

-- 1) Licencias (catálogo)
CREATE TABLE IF NOT EXISTS licenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  licenseKey VARCHAR(40) UNIQUE NOT NULL,    -- ej: MIT, GPL-3.0, CC-BY-4.0
  name VARCHAR(120) NOT NULL,
  url VARCHAR(255) NULL,
  requiresAttribution TINYINT(1) DEFAULT 1, -- si requiere atribución
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO licenses (licenseKey, name, url, requiresAttribution) VALUES
('MIT', 'MIT License', 'https://opensource.org/licenses/MIT', 1),
('GPL-3.0', 'GNU GPL v3', 'https://www.gnu.org/licenses/gpl-3.0.en.html', 1),
('Apache-2.0', 'Apache License 2.0', 'https://www.apache.org/licenses/LICENSE-2.0', 1),
('CC-BY-4.0', 'Creative Commons Attribution 4.0', 'https://creativecommons.org/licenses/by/4.0/', 1),
('CC-BY-NC-4.0', 'CC Attribution-NonCommercial 4.0', 'https://creativecommons.org/licenses/by-nc/4.0/', 1);

-- 2) Proyectos de software
CREATE TABLE IF NOT EXISTS softwareProjects (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  description TEXT NULL,
  licenseId INT NOT NULL,
  readmeText MEDIUMTEXT NULL, -- contenido plano leído del readme.md
  createdBy INT NOT NULL,     -- users.id (autor principal)
  status ENUM('submitted','under_review','changes_requested','approved','vetoed','temporarily_hidden','published','archived') 
         DEFAULT 'submitted',
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (licenseId) REFERENCES licenses(id),
  FOREIGN KEY (createdBy) REFERENCES users(id)
);

-- 3) Autores/Colaboradores del proyecto
CREATE TABLE IF NOT EXISTS softwareProjectContributors (
  projectId BIGINT NOT NULL,
  userId INT NOT NULL,
  roleInProject ENUM('author','coauthor','contributor') DEFAULT 'author',
  PRIMARY KEY (projectId, userId),
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

-- 4) Archivos subidos (múltiples)
CREATE TABLE IF NOT EXISTS softwareFiles (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  projectId BIGINT NOT NULL,
  fileName VARCHAR(200) NOT NULL,
  filePath VARCHAR(300) NOT NULL, -- ej: /storage/software/{projectId}/{uuid}_{file}
  fileSize BIGINT NOT NULL,
  fileType VARCHAR(80) NOT NULL,  -- ej: text/x-php, application/x-zip-compressed
  checksum CHAR(64) NULL,         -- sha256
  uploadedBy INT NOT NULL,
  uploadedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (uploadedBy) REFERENCES users(id)
);

-- 5) Tags (tópicos)
CREATE TABLE IF NOT EXISTS softwareTags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tagName VARCHAR(50) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS softwareProjectTags (
  projectId BIGINT NOT NULL,
  tagId INT NOT NULL,
  PRIMARY KEY (projectId, tagId),
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (tagId) REFERENCES softwareTags(id) ON DELETE CASCADE
);

-- 6) Moderación / Revisiones
CREATE TABLE IF NOT EXISTS softwareReviews (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  projectId BIGINT NOT NULL,
  reviewerId INT NOT NULL,  -- coordinator / deptHead / admin
  action ENUM('request_changes','approve','veto','hide','publish') NOT NULL,
  comment TEXT NULL,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewerId) REFERENCES users(id)
);

-- Historial de estados
CREATE TABLE IF NOT EXISTS softwareStatusHistory (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  projectId BIGINT NOT NULL,
  oldStatus VARCHAR(40) NULL,
  newStatus VARCHAR(40) NOT NULL,
  changedBy INT NOT NULL,
  note TEXT NULL,
  changedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (changedBy) REFERENCES users(id)
);

-- 7) Descargas y consentimiento de licencia
CREATE TABLE IF NOT EXISTS softwareDownloadConsents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  projectId BIGINT NOT NULL,
  userId INT NOT NULL,
  licenseId INT NOT NULL,
  consentedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(64) NULL,
  FOREIGN KEY (projectId) REFERENCES softwareProjects(id) ON DELETE CASCADE,
  FOREIGN KEY (userId) REFERENCES users(id),
  FOREIGN KEY (licenseId) REFERENCES licenses(id)
);

-- 8) (Opcional) Cola de notificaciones por correo
CREATE TABLE IF NOT EXISTS notificationsQueue (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  subject VARCHAR(150) NOT NULL,
  body TEXT NOT NULL,
  sent TINYINT(1) DEFAULT 0,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  sentAt DATETIME NULL,
  FOREIGN KEY (userId) REFERENCES users(id)
);

-- 9) Vista pública de proyectos publicados (para listados rápidos)
CREATE OR REPLACE VIEW view_public_software AS
SELECT 
  p.id AS projectId, p.title, p.description, p.status, p.createdAt, p.updatedAt,
  l.name AS licenseName
FROM softwareProjects p
JOIN licenses l ON l.id = p.licenseId
WHERE p.status = 'published'
ORDER BY p.updatedAt DESC;
