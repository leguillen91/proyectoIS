USE projectUnahSistems;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  userId INT NOT NULL,
  enrollmentCode VARCHAR(20) NOT NULL UNIQUE,
  career VARCHAR(120) NOT NULL,
  academicCenter VARCHAR(100) NOT NULL,
  admissionYear YEAR NOT NULL,
  currentPeriod VARCHAR(15) DEFAULT '2025-I',
  status ENUM('Activo','Suspendido','Egresado') DEFAULT 'Activo',
  phoneNumber VARCHAR(20),
  address TEXT,
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fkStudentUser FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
);

-- Vista de apoyo para consultas
CREATE OR REPLACE VIEW view_students AS
SELECT 
  s.id AS studentId,
  u.fullName,
  u.email,
  s.career,
  s.academicCenter,
  s.admissionYear,
  s.currentPeriod,
  s.status,
  s.phoneNumber
FROM students s
JOIN users u ON u.id = s.userId;

INSERT INTO permissions (permissionCode, description) VALUES
('students.view', 'Ver información de estudiantes'),
('students.manage', 'Administrar información de estudiantes'),
('students.create', 'Registrar nuevos estudiantes'),
('students.update', 'Actualizar información de estudiantes'),
('students.delete', 'Eliminar estudiantes')
ON DUPLICATE KEY UPDATE permissionCode = permissionCode;

-- Asignar a roles
-- student (solo ver)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='student' AND p.permissionCode='students.view';

-- coordinator (ver y actualizar)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='coordinator' AND p.permissionCode IN ('students.view','students.update');

-- admin (todos)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='admin';

INSERT INTO permissions (permissionCode, description) VALUES
('students.view', 'Ver información de estudiantes'),
('students.manage', 'Administrar información de estudiantes'),
('students.create', 'Registrar nuevos estudiantes'),
('students.update', 'Actualizar información de estudiantes'),
('students.delete', 'Eliminar estudiantes')
ON DUPLICATE KEY UPDATE permissionCode = permissionCode;

-- Asignar a roles
-- student (solo ver)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='student' AND p.permissionCode='students.view';

-- coordinator (ver y actualizar)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='coordinator' AND p.permissionCode IN ('students.view','students.update');

-- admin (todos)
INSERT IGNORE INTO rolePermissions (roleId, permissionId)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.roleName='admin';


ALTER TABLE students
  ADD COLUMN level ENUM('pregrado','postgrado') DEFAULT 'pregrado' AFTER userId,
  ADD COLUMN accountNumber VARCHAR(20) UNIQUE AFTER level;