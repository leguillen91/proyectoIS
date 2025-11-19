CREATE TABLE careers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO careers (name) VALUES
('Ingeniería en Sistemas'),
('Licenciatura en Informática'),
('Música');


CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO departments (name) VALUES
('Departamento de Ingeniería'),
('Departamento de Informática'),
('Departamento de Música');


CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO buildings (code, name) VALUES
('A1','Edificio A1'),
('A2','Edificio A2'),
('B3','Edificio B3'),
('CBL','Centro de Bienestar Universitario');

CREATE TABLE classrooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    buildingId INT NOT NULL,
    roomCode VARCHAR(20) NOT NULL,
    capacity INT NOT NULL DEFAULT 40,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_classrooms_building FOREIGN KEY (buildingId)
        REFERENCES buildings(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

INSERT INTO classrooms (buildingId, roomCode, capacity) VALUES
(1, 'A1-101', 40),
(1, 'A1-102', 40),
(2, 'A2-201', 45),
(3, 'B3-210', 35),
(4, 'CBL-05', 25);


CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    uv INT NOT NULL,
    departmentId INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_subjects_department FOREIGN KEY (departmentId)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

INSERT INTO subjects (code, name, uv, departmentId) VALUES
('IS-110', 'Introducción a la Programación', 4, 1),
('IS-210', 'Estructuras de Datos', 4, 1),
('INF-101', 'Introducción a Informática', 3, 2),
('MU-100', 'Solfeo Básico', 2, 3);

CREATE TABLE subjectPrerequisites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subjectId INT NOT NULL,
    prereqId INT NOT NULL,

    CONSTRAINT fk_subjectPrerequisites_subject FOREIGN KEY (subjectId)
        REFERENCES subjects(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_subjectPrerequisites_prereq FOREIGN KEY (prereqId)
        REFERENCES subjects(id)
        ON DELETE CASCADE
);

INSERT INTO subjectPrerequisites (subjectId, prereqId) VALUES
(2, 1); -- IS-210 requiere IS-110


CREATE TABLE periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,
    status ENUM('creado','abierto','cerrado','finalizado') NOT NULL DEFAULT 'creado',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO periods (code, startDate, endDate, status) VALUES
('2025-I','2025-01-15','2025-05-30','abierto');

CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodId INT NOT NULL,
    subjectId INT NOT NULL,
    sectionCode VARCHAR(10) NOT NULL,
    teacherId INT NOT NULL,
    classroomId INT NOT NULL,
    cupo INT NOT NULL DEFAULT 30,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sections_period FOREIGN KEY (periodId)
        REFERENCES periods(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_sections_subject FOREIGN KEY (subjectId)
        REFERENCES subjects(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_sections_teacher FOREIGN KEY (teacherId)
        REFERENCES teachers(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_sections_classroom FOREIGN KEY (classroomId)
        REFERENCES classrooms(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


CREATE TABLE sectionSchedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sectionId INT NOT NULL,
    day ENUM('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado') NOT NULL,
    startTime TIME NOT NULL,
    endTime TIME NOT NULL,

    CONSTRAINT fk_sectionSchedule_section FOREIGN KEY (sectionId)
        REFERENCES sections(id)
        ON DELETE CASCADE
);


CREATE TABLE studentEnrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId INT NOT NULL,
    sectionId INT NOT NULL,
    periodId INT NOT NULL,
    uv INT NOT NULL,
    status ENUM('Inscrito','Retirado') DEFAULT 'Inscrito',
    enrolledAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_studentEnrollments_student FOREIGN KEY (studentId)
        REFERENCES students(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_studentEnrollments_section FOREIGN KEY (sectionId)
        REFERENCES sections(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_studentEnrollments_period FOREIGN KEY (periodId)
        REFERENCES periods(id)
        ON DELETE RESTRICT
);

CREATE TABLE studentGrades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId INT NOT NULL,
    sectionId INT NOT NULL,
    grade DECIMAL(4,2) NULL,
    status ENUM('Pendiente','Aprobado','Reprobado') DEFAULT 'Pendiente',
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_studentGrades_student FOREIGN KEY (studentId)
        REFERENCES students(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_studentGrades_section FOREIGN KEY (sectionId)
        REFERENCES sections(id)
        ON DELETE RESTRICT
);

CREATE TABLE studentAcademicRecord (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId INT NOT NULL,
    subjectId INT NOT NULL,
    grade DECIMAL(4,2) NOT NULL,
    uv INT NOT NULL,
    periodId INT NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_studentAcademicRecord_student FOREIGN KEY (studentId)
        REFERENCES students(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_studentAcademicRecord_subject FOREIGN KEY (subjectId)
        REFERENCES subjects(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_studentAcademicRecord_period FOREIGN KEY (periodId)
        REFERENCES periods(id)
        ON DELETE RESTRICT
);

CREATE TABLE enrollmentCalendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periodId INT NOT NULL,
    minIndex DECIMAL(4,2) NOT NULL,
    maxIndex DECIMAL(4,2) NOT NULL,
    startDate DATE NOT NULL,
    endDate DATE NOT NULL,

    CONSTRAINT fk_enrollmentCalendar_period FOREIGN KEY (periodId)
        REFERENCES periods(id)
        ON DELETE CASCADE
);


    CREATE TABLE enrollmentLogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId INT NOT NULL,
    action VARCHAR(120) NOT NULL,
    details TEXT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_enrollmentLogs_student FOREIGN KEY (studentId)
        REFERENCES students(id)
        ON DELETE CASCADE
);

