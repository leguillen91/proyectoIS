/* =======================================================
  AJUSTES TEMPORALES
======================================================= */
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


/* =======================================================
   ESQUEMAS
======================================================= */
CREATE SCHEMA IF NOT EXISTS identity   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS academic   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS enrollment DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS resources  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS admissions DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


/* =======================================================
   IDENTITY
======================================================= */
USE identity;

CREATE TABLE users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nationalId      VARCHAR(20)  NOT NULL,
  firstName       VARCHAR(60)  NOT NULL,
  lastName        VARCHAR(60)  NOT NULL,
  institutionalEmail VARCHAR(100) NULL,
  personalEmail      VARCHAR(120) NULL,
  phone              VARCHAR(20)  NULL,
  address            VARCHAR(255) NULL,
  profilePhoto       LONGBLOB     NULL,
  createdAt          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  accountStatus      ENUM('Active','Inactive','Suspended','Deleted') NOT NULL DEFAULT 'Active',
  UNIQUE KEY uqUsersNationalId (nationalId)
) ENGINE=InnoDB;

CREATE TABLE credentials (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userId          INT UNSIGNED NOT NULL,
  username        VARCHAR(45)  NOT NULL,
  role            ENUM('ADMIN','STUDENT','TEACHER','COORDINATOR','DEPT_HEAD','APPLICANT') NOT NULL DEFAULT 'APPLICANT',
  passwordHash    VARCHAR(255) NOT NULL,
  lastLogin       DATETIME NULL,
  loginAttempts   INT UNSIGNED NOT NULL DEFAULT 0,
  isBlocked       TINYINT(1) NOT NULL DEFAULT 0,
  recoveryToken   VARCHAR(100) NULL,
  createdAt       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uqCredentialsUsername (username),
  UNIQUE KEY uqCredentialsUser (userId),
  KEY fkCredentialsUser (userId),

  CONSTRAINT fkCredentialsUser
    FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
  id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  roleName   VARCHAR(45)  NOT NULL,
  description VARCHAR(255) NULL,
  UNIQUE KEY uqRolesRoleName (roleName)
) ENGINE=InnoDB;

CREATE TABLE credentialRoles (
  credentialId INT UNSIGNED NOT NULL,
  roleId       TINYINT UNSIGNED NOT NULL,
  assignedAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (credentialId,roleId),
  KEY fkCrRole (roleId),
  CONSTRAINT fkCrCredential FOREIGN KEY (credentialId) REFERENCES credentials(id),
  CONSTRAINT fkCrRole       FOREIGN KEY (roleId)       REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE students (
  userId            INT UNSIGNED PRIMARY KEY,
  studentNumber     VARCHAR(11)  NOT NULL,
  programLevel      ENUM('Undergraduate','Graduate') NOT NULL,
  programId         INT UNSIGNED NULL,        /* FK academic.program */
  secondaryProgramId INT UNSIGNED NULL,       /* FK academic.program */
  gpa               DECIMAL(4,2)  NULL,
  entryDate         DATE          NULL,
  KEY fkStudentProgram1 (programId),
  KEY fkStudentProgram2 (secondaryProgramId),
  CONSTRAINT fkStudentUser FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  userId                  INT UNSIGNED PRIMARY KEY,
  employeeNumber          VARCHAR(20) NULL,
  academicDepartmentId    INT UNSIGNED NULL,   /* FK academic.program (o depto si se crea después) */
  hireDate                DATE NULL,
  shift                   ENUM('FullTime','PartTime') NULL,
  office                  VARCHAR(45) NULL,
  CONSTRAINT fkTeacherUser FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE departmentHead (
  userId               INT UNSIGNED PRIMARY KEY,
  academicDepartmentId INT UNSIGNED NULL,   /* FK academic.program (o depto) */
  termStartDate        DATE NOT NULL,
  termEndDate          DATE NULL,
  endReason            VARCHAR(255) NULL,
  CONSTRAINT fkHeadUser FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE coordinators (
  userId INT UNSIGNED PRIMARY KEY,
  area   ENUM('Software','Music','VirtualLibrary','Admissions','Enrollment') NOT NULL,
  isActive TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fkCoordUser FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  permissionCode VARCHAR(100) NOT NULL UNIQUE,
  description    VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rolePermissions (
  roleId       TINYINT UNSIGNED NOT NULL,
  permissionId INT UNSIGNED NOT NULL,
  PRIMARY KEY (roleId, permissionId),
  CONSTRAINT fkRolePermRole
    FOREIGN KEY (roleId) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fkRolePermPerm
    FOREIGN KEY (permissionId) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS revokedTokens (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  jti          VARCHAR(64) NOT NULL UNIQUE,
  credentialId INT UNSIGNED NULL,
  revokedAt    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fkRevokedTokenCred
    FOREIGN KEY (credentialId) REFERENCES credentials(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/* =======================================================
   ACADEMIC 
======================================================= */
USE academic;

CREATE TABLE faculty (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  facultyCode   VARCHAR(10) NOT NULL,
  name          VARCHAR(80) NOT NULL,
  status        VARCHAR(45) NOT NULL DEFAULT 'Active',
  UNIQUE KEY uqFacultyCode (facultyCode)
) ENGINE=InnoDB;

CREATE TABLE campus (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campusCode  VARCHAR(10) NOT NULL,
  name        VARCHAR(80) NOT NULL,
  address     VARCHAR(255) NULL,
  phone       VARCHAR(20)  NULL,
  status      VARCHAR(45)  NOT NULL DEFAULT 'Active',
  UNIQUE KEY uqCampusCode (campusCode)
) ENGINE=InnoDB;

CREATE TABLE building (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campusId  INT UNSIGNED NOT NULL,
  name      VARCHAR(80) NOT NULL,
  status    VARCHAR(45) NOT NULL DEFAULT 'Active',
  KEY fkBuildingCampus (campusId),
  CONSTRAINT fkBuildingCampus FOREIGN KEY (campusId) REFERENCES campus(id)
) ENGINE=InnoDB;

CREATE TABLE classroom (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buildingId  INT UNSIGNED NOT NULL,
  roomNumber  VARCHAR(10)  NOT NULL,
  capacity    INT UNSIGNED NOT NULL,
  roomType    ENUM('Classroom','Laboratory') NOT NULL,
  status      VARCHAR(45) NOT NULL DEFAULT 'Active',
  KEY fkClassroomBuilding (buildingId),
  CONSTRAINT fkClassroomBuilding FOREIGN KEY (buildingId) REFERENCES building(id)
) ENGINE=InnoDB;

CREATE TABLE program (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  programCode     VARCHAR(10) NOT NULL,
  name            VARCHAR(100) NOT NULL,
  facultyId       INT UNSIGNED NOT NULL,
  totalCredits    INT UNSIGNED NULL,
  totalSemesters  INT UNSIGNED NULL,
  status          VARCHAR(45) NOT NULL DEFAULT 'Active',
  campusId        INT UNSIGNED NOT NULL,
  modality        ENUM('Onsite','Online') NOT NULL,
  UNIQUE KEY uqProgramCode (programCode),
  KEY fkProgramFaculty (facultyId),
  KEY fkProgramCampus (campusId),
  CONSTRAINT fkProgramFaculty FOREIGN KEY (facultyId) REFERENCES faculty(id),
  CONSTRAINT fkProgramCampus  FOREIGN KEY (campusId)  REFERENCES campus(id)
) ENGINE=InnoDB;

CREATE TABLE course (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courseCode     VARCHAR(15) NOT NULL,
  name           VARCHAR(100) NOT NULL,
  credits        TINYINT UNSIGNED NOT NULL,
  category       ENUM('Required','Elective','Selective') NOT NULL,
  status         VARCHAR(45) NOT NULL DEFAULT 'Active',
  UNIQUE KEY uqCourseCode (courseCode)
) ENGINE=InnoDB;

CREATE TABLE prerequisite (
  courseId        INT UNSIGNED NOT NULL,
  prerequisiteId  INT UNSIGNED NOT NULL,
  PRIMARY KEY (courseId,prerequisiteId),
  KEY fkPreReq (prerequisiteId),
  CONSTRAINT fkPreCourse   FOREIGN KEY (courseId)       REFERENCES course(id),
  CONSTRAINT fkPreCourse2  FOREIGN KEY (prerequisiteId) REFERENCES course(id)
) ENGINE=InnoDB;

CREATE TABLE curriculumPlan (
  programId   INT UNSIGNED NOT NULL,
  courseId    INT UNSIGNED NOT NULL,
  semester    TINYINT UNSIGNED NOT NULL,
  planType    ENUM('Required','Elective') NOT NULL,
  PRIMARY KEY (programId,courseId),
  KEY fkPlanCourse (courseId),
  CONSTRAINT fkPlanProgram FOREIGN KEY (programId) REFERENCES program(id),
  CONSTRAINT fkPlanCourse  FOREIGN KEY (courseId)  REFERENCES course(id)
) ENGINE=InnoDB;

CREATE TABLE academicTerm (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year                 SMALLINT UNSIGNED NOT NULL,
  termNumber           TINYINT  UNSIGNED NOT NULL,
  name                 VARCHAR(45) NULL,
  startDate            DATE NOT NULL,
  endDate              DATE NOT NULL,
  enrollmentStartDate  DATE NOT NULL,
  enrollmentEndDate    DATE NOT NULL,
  classesStartDate     DATE NOT NULL,
  classesEndDate       DATE NOT NULL,
  status               ENUM('Enrollment','InProgress','Finished') NOT NULL
) ENGINE=InnoDB;


USE identity;
ALTER TABLE students
  ADD CONSTRAINT fkStudentProgram1 FOREIGN KEY (programId)           REFERENCES academic.program(id),
  ADD CONSTRAINT fkStudentProgram2 FOREIGN KEY (secondaryProgramId)  REFERENCES academic.program(id);

ALTER TABLE teachers
  ADD CONSTRAINT fkTeacherDept FOREIGN KEY (academicDepartmentId) REFERENCES academic.program(id);

ALTER TABLE departmentHead
  ADD CONSTRAINT fkHeadDept FOREIGN KEY (academicDepartmentId) REFERENCES academic.program(id);


/* =======================================================
   ENROLLMENT
======================================================= */
USE enrollment;

CREATE TABLE section (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courseId      INT UNSIGNED NOT NULL,  /* FK academic.course */
  termId        INT UNSIGNED NOT NULL,  /* FK academic.academicTerm */
  teacherId     INT UNSIGNED NOT NULL,  /* FK identity.teachers(userId) */
  classroomId   INT UNSIGNED NOT NULL,  /* FK academic.classroom */
  sectionCode   VARCHAR(20) NOT NULL,
  maxCapacity   SMALLINT UNSIGNED NOT NULL,
  modality      ENUM('Onsite','Online') NOT NULL,
  status        ENUM('Active','Closed','Cancelled') NOT NULL,
  createdAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fkSecCourse    (courseId),
  KEY fkSecTerm      (termId),
  KEY fkSecTeacher   (teacherId),
  KEY fkSecClassroom (classroomId),
  CONSTRAINT fkSecCourse    FOREIGN KEY (courseId)    REFERENCES academic.course(id),
  CONSTRAINT fkSecTerm      FOREIGN KEY (termId)      REFERENCES academic.academicTerm(id),
  CONSTRAINT fkSecTeacher   FOREIGN KEY (teacherId)   REFERENCES identity.teachers(userId),
  CONSTRAINT fkSecClassroom FOREIGN KEY (classroomId) REFERENCES academic.classroom(id)
) ENGINE=InnoDB;

CREATE TABLE sectionSchedule (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sectionId   INT UNSIGNED NOT NULL,
  days        VARCHAR(45)  NOT NULL,     
  startTime   TIME NOT NULL,
  endTime     TIME NOT NULL,
  classroomId INT UNSIGNED NOT NULL,
  KEY fkSsSect  (sectionId),
  KEY fkSsClass (classroomId),
  CONSTRAINT fkSsSect  FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fkSsClass FOREIGN KEY (classroomId) REFERENCES academic.classroom(id)
) ENGINE=InnoDB;

CREATE TABLE waitlist (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId    INT UNSIGNED NOT NULL,  /* FK identity.students(userId) */
  sectionId    INT UNSIGNED NOT NULL,
  requestedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  position     INT UNSIGNED NOT NULL,
  priority     TINYINT UNSIGNED NULL,
  served       TINYINT(1) NOT NULL DEFAULT 0,
  KEY fkWlStudent (studentId),
  KEY fkWlSection (sectionId),
  CONSTRAINT fkWlStudent FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fkWlSection FOREIGN KEY (sectionId)  REFERENCES section(id)
) ENGINE=InnoDB;

CREATE TABLE payment (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId INT UNSIGNED NOT NULL,  /* FK identity.students */
  termId    INT UNSIGNED NOT NULL,  /* FK academic.academicTerm */
  concept   ENUM('EnrollmentFee','Makeup','Other') NOT NULL,
  amount    DECIMAL(10,2) NOT NULL,
  method    ENUM('Cash','Card') NOT NULL,
  status    ENUM('Recorded','Confirmed','Rejected','Voided') NOT NULL,
  paidAt    DATETIME NOT NULL,
  KEY fkPayStudent (studentId),
  KEY fkPayTerm    (termId),
  CONSTRAINT fkPayStudent FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fkPayTerm    FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE studentEnrollment (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId     INT UNSIGNED NOT NULL, /* FK identity.students */
  sectionId     INT UNSIGNED NOT NULL, /* FK enrollment.section */
  enrolledAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Active','Withdrawn','Cancelled') NOT NULL,
  finalGrade    DECIMAL(5,2) NULL,
  paymentId     INT UNSIGNED NOT NULL,
  KEY fkEnrStudent (studentId),
  KEY fkEnrSection (sectionId),
  KEY fkEnrPayment (paymentId),
  CONSTRAINT fkEnrStudent FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fkEnrSection FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fkEnrPayment FOREIGN KEY (paymentId)  REFERENCES payment(id)
) ENGINE=InnoDB;

CREATE TABLE studentRequest (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId          INT UNSIGNED NOT NULL, /* identity.students */
  currentProgramId   INT UNSIGNED NULL,     /* academic.program */
  requestedProgramId INT UNSIGNED NULL,     /* academic.program */
  currentCampusId    INT UNSIGNED NULL,     /* academic.campus */
  requestedCampusId  INT UNSIGNED NULL,     /* academic.campus */
  termId             INT UNSIGNED NOT NULL, /* academic.academicTerm */
  requestType        ENUM('ProgramChange','ExceptionalCancellation','Makeup','CampusChange') NOT NULL,
  justification      VARCHAR(255) NULL,
  justificationPdf   LONGBLOB NULL,
  status             ENUM('Review','Approved','Rejected','Voided') NOT NULL,
  assignedCoordinatorId INT UNSIGNED NULL,   /* identity.coordinators(userId) */
  approvedById       INT UNSIGNED NULL,      /* identity.departmentHead(userId) */
  requestedAt        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approvedAt         DATETIME NULL,
  KEY fkSrStudent (studentId),
  KEY fkSrTerm    (termId),
  CONSTRAINT fkSrStudent FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fkSrTerm    FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id),
  CONSTRAINT fkSrProg1   FOREIGN KEY (currentProgramId)   REFERENCES academic.program(id),
  CONSTRAINT fkSrProg2   FOREIGN KEY (requestedProgramId) REFERENCES academic.program(id),
  CONSTRAINT fkSrCamp1   FOREIGN KEY (currentCampusId)    REFERENCES academic.campus(id),
  CONSTRAINT fkSrCamp2   FOREIGN KEY (requestedCampusId)  REFERENCES academic.campus(id),
  CONSTRAINT fkSrCoord   FOREIGN KEY (assignedCoordinatorId) REFERENCES identity.coordinators(userId),
  CONSTRAINT fkSrApproved FOREIGN KEY (approvedById)         REFERENCES identity.departmentHead(userId)
) ENGINE=InnoDB;

CREATE TABLE sectionRequest (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requestId    INT UNSIGNED NOT NULL, /* FK enrollment.studentRequest */
  sectionId    INT UNSIGNED NOT NULL, /* FK enrollment.section */
  reason       VARCHAR(255) NULL,
  status       ENUM('Pending','Approved','Rejected') NOT NULL,
  comment      VARCHAR(255) NULL,
  approvedById INT UNSIGNED NULL,     /* identity.departmentHead */
  reviewedAt   DATETIME NULL,
  KEY fkSreqReq (requestId),
  KEY fkSreqSec (sectionId),
  CONSTRAINT fkSreqReq FOREIGN KEY (requestId) REFERENCES studentRequest(id),
  CONSTRAINT fkSreqSec FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fkSreqApr FOREIGN KEY (approvedById) REFERENCES identity.departmentHead(userId)
) ENGINE=InnoDB;


/* =======================================================
   RESOURCES
======================================================= */

USE resources;

/* ---------------------------------------------
   Catálogos
--------------------------------------------- */
CREATE TABLE resourceType (
  idResourceType   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(20)  NOT NULL UNIQUE,      -- Como ejemplo tenemos de PDF, AUDIO, SCORE, CODE, etc...
  description      VARCHAR(120) NULL
) ENGINE=InnoDB;

CREATE TABLE license (
  idLicense   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code        VARCHAR(30)  NOT NULL UNIQUE,
  name        VARCHAR(120) NOT NULL,
  url         VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE tag (
  idTag        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(60)  NOT NULL UNIQUE,
  description  VARCHAR(255) NULL
) ENGINE=InnoDB;

/* ---------------------------------------------
   Recurso tabla principal
--------------------------------------------- */
CREATE TABLE resource (
  idResource        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title             VARCHAR(150) NOT NULL,
  description       VARCHAR(255) NULL,
  module            ENUM('Software','Music','Library') NOT NULL,  -- plataforma a la que pertenece
  resourceTypeId    INT UNSIGNED NOT NULL,                        -- FK a tipo (PDF, AUDIO, etc.)
  createdByPersonId INT UNSIGNED NULL,                            -- FK opcional a identity.person
  careerId          INT UNSIGNED NULL,                            -- FK opcional a academic.career
  courseId          INT UNSIGNED NULL,                            -- FK opcional a academic.course (asignatura)
  sectionId         INT UNSIGNED NULL,                            -- FK opcional a enrollment.section
  licenseId         INT UNSIGNED NULL,                            -- FK opcional
  visibility        ENUM('UniversityOnly','Public') NOT NULL DEFAULT 'UniversityOnly',
  downloadPolicy    ENUM('ViewOnly','DownloadAllowed') NOT NULL DEFAULT 'ViewOnly',
  status            ENUM('Draft','Submitted','UnderReview','Approved','NeedsCorrection','Rejected','Archived')
                    NOT NULL DEFAULT 'Submitted',
  createdAt         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updatedAt         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  KEY idxResModStatus (module, status),
  KEY idxResCourse (courseId),
  KEY idxResCareer (careerId),

  CONSTRAINT fkResType
    FOREIGN KEY (resourceTypeId) REFERENCES resourceType(idResourceType),

  CONSTRAINT fkResLicense
    FOREIGN KEY (licenseId) REFERENCES license(idLicense),

  CONSTRAINT fkResPerson    FOREIGN KEY (createdByPersonId) REFERENCES identity.person(idPerson),
  CONSTRAINT fkResCareer    FOREIGN KEY (careerId)          REFERENCES academic.career(idCareer),
  CONSTRAINT fkResCourse    FOREIGN KEY (courseId)          REFERENCES academic.course(idCourse),
  CONSTRAINT fkResSection   FOREIGN KEY (sectionId)         REFERENCES enrollment.section(idSection)
) ENGINE=InnoDB;

/* ---------------------------------------------
   Archivos del recurso (BLOBs)
--------------------------------------------- */
CREATE TABLE resourceFile (
  idResourceFile   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resourceId       INT UNSIGNED NOT NULL,
  fileKind         ENUM('Primary','Readme','Preview') NOT NULL DEFAULT 'Primary',
  fileBlob         LONGBLOB NULL,                    -- contenido binario
  originalFilename VARCHAR(150) NULL,
  mimeType         VARCHAR(80)  NULL,
  sizeBytes        INT UNSIGNED NULL,
  pages            INT UNSIGNED NULL,                -- PDF
  durationSeconds  INT UNSIGNED NULL,                -- audio/video
  checksum         VARCHAR(64) NULL,                 -- opcional
  createdAt        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  KEY idxRfRes (resourceId),
  CONSTRAINT fkRfRes FOREIGN KEY (resourceId) REFERENCES resource(idResource) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------
   Autores/Editorial 
--------------------------------------------- */
CREATE TABLE resourceAuthor (
  idResourceAuthor INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resourceId       INT UNSIGNED NOT NULL,
  personId         INT UNSIGNED NULL,  -- si el autor existe en identity.person
  authorName       VARCHAR(120) NULL,  -- si es autor externo (texto)
  role             ENUM('Author','CoAuthor','Composer','Editor') NOT NULL DEFAULT 'Author',

  KEY idxRaRes (resourceId),
  CONSTRAINT fkRaRes    FOREIGN KEY (resourceId) REFERENCES resource(idResource) ON DELETE CASCADE,
  CONSTRAINT fkRaPerson FOREIGN KEY (personId)   REFERENCES identity.person(idPerson)
) ENGINE=InnoDB;

/* ---------------------------------------------
   Etiquetas (N:M)
--------------------------------------------- */
CREATE TABLE resourceTag (
  resourceId INT UNSIGNED NOT NULL,
  tagId      INT UNSIGNED NOT NULL,
  PRIMARY KEY (resourceId, tagId),
  KEY idxRtTag (tagId),
  CONSTRAINT fkRtRes FOREIGN KEY (resourceId) REFERENCES resource(idResource) ON DELETE CASCADE,
  CONSTRAINT fkRtTag FOREIGN KEY (tagId)      REFERENCES tag(idTag)
) ENGINE=InnoDB;

/* ---------------------------------------------
   Alcance adicional (si un recurso aplica a varios ámbitos)
--------------------------------------------- */
CREATE TABLE resourceScope (
  idResourceScope INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resourceId      INT UNSIGNED NOT NULL,
  scopeType       ENUM('Career','Course','Section','Campus') NOT NULL,
  scopeId         INT UNSIGNED NOT NULL,
  KEY idxRsRes (resourceId),
  CONSTRAINT fkRsRes FOREIGN KEY (resourceId) REFERENCES resource(idResource) ON DELETE CASCADE
) ENGINE=InnoDB;

/* ---------------------------------------------
   Revisión 
--------------------------------------------- */
CREATE TABLE reviewAssignment (
  idAssignment       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resourceId         INT UNSIGNED NOT NULL,
  assignedToPersonId INT UNSIGNED NOT NULL,   -- Jefe o Coordinador
  assignedRole       ENUM('DEPT_HEAD','COORDINATOR') NOT NULL,
  assignedAt         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  active             TINYINT(1) NOT NULL DEFAULT 1,
  KEY idxRa2Res (resourceId),
  CONSTRAINT fkRasRes    FOREIGN KEY (resourceId)         REFERENCES resource(idResource) ON DELETE CASCADE,
  CONSTRAINT fkRasPerson FOREIGN KEY (assignedToPersonId) REFERENCES identity.person(idPerson)
) ENGINE=InnoDB;

CREATE TABLE review (
  idReview          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resourceId        INT UNSIGNED NOT NULL,
  reviewerPersonId  INT UNSIGNED NOT NULL,
  decision          ENUM('Approved','NeedsCorrection','Rejected') NOT NULL,
  comments          VARCHAR(255) NULL,
  reviewedAt        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idxRevRes (resourceId),
  CONSTRAINT fkRevRes    FOREIGN KEY (resourceId)       REFERENCES resource(idResource) ON DELETE CASCADE,
  CONSTRAINT fkRevPerson FOREIGN KEY (reviewerPersonId) REFERENCES identity.person(idPerson)
) ENGINE=InnoDB;


/* =======================================================
   ADMISSIONS
======================================================= */
USE admissions;

CREATE TABLE applicant (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userId        INT UNSIGNED NOT NULL, /* identity.users (antes de ser alumno) */
  personalEmail VARCHAR(120) NULL,
  phone         VARCHAR(45)  NULL,
  createdAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Active','Inactive','Blocked') NOT NULL DEFAULT 'Active',
  KEY fkAppUser (userId),
  CONSTRAINT fkAppUser FOREIGN KEY (userId) REFERENCES identity.users(id)
) ENGINE=InnoDB;

CREATE TABLE admissionApplication (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicantId        INT UNSIGNED NOT NULL, /* admissions.applicant */
  termId             INT UNSIGNED NOT NULL, /* academic.academicTerm */
  primaryProgramId   INT UNSIGNED NOT NULL, /* academic.program */
  campusId           INT UNSIGNED NOT NULL, /* academic.campus */
  secondaryProgramId INT UNSIGNED NULL,     /* academic.program */
  status             ENUM('Pending','UnderReview','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  rejectionReason    VARCHAR(255) NULL,
  createdAt          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decidedAt          DATETIME NULL,
  KEY fkAaApp    (applicantId),
  KEY fkAaTerm   (termId),
  KEY fkAaProg1  (primaryProgramId),
  KEY fkAaProg2  (secondaryProgramId),
  KEY fkAaCampus (campusId),
  CONSTRAINT fkAaApp    FOREIGN KEY (applicantId)        REFERENCES applicant(id),
  CONSTRAINT fkAaTerm   FOREIGN KEY (termId)             REFERENCES academic.academicTerm(id),
  CONSTRAINT fkAaProg1  FOREIGN KEY (primaryProgramId)   REFERENCES academic.program(id),
  CONSTRAINT fkAaProg2  FOREIGN KEY (secondaryProgramId) REFERENCES academic.program(id),
  CONSTRAINT fkAaCampus FOREIGN KEY (campusId)           REFERENCES academic.campus(id)
) ENGINE=InnoDB;

CREATE TABLE applicationDocument (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicationId INT UNSIGNED NOT NULL,
  documentType  ENUM('HighSchoolCertificate') NOT NULL,
  pdfFile       LONGBLOB NOT NULL,
  sizeBytes     INT UNSIGNED NOT NULL,
  uploadedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fkDocApp (applicationId),
  CONSTRAINT fkDocApp FOREIGN KEY (applicationId) REFERENCES admissionApplication(id)
) ENGINE=InnoDB;

CREATE TABLE examType (
  id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        ENUM('PAA','PAM','PCCNS') NOT NULL,
  maxScore    INT UNSIGNED NOT NULL,
  description VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE programExamRequirement (
  programId    INT UNSIGNED NOT NULL,      /* academic.program */
  examTypeId   TINYINT UNSIGNED NOT NULL,  /* admissions.examType */
  minimumScore INT UNSIGNED NOT NULL,
  required     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (programId,examTypeId),
  KEY fkPerType (examTypeId),
  CONSTRAINT fkPerProg FOREIGN KEY (programId)  REFERENCES academic.program(id),
  CONSTRAINT fkPerType FOREIGN KEY (examTypeId) REFERENCES examType(id)
) ENGINE=InnoDB;

CREATE TABLE exam (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicationId INT UNSIGNED NOT NULL,     /* admissions.admissionApplication */
  examTypeId    TINYINT UNSIGNED NOT NULL, /* admissions.examType */
  termId        INT UNSIGNED NOT NULL,     /* academic.academicTerm */
  campusId      INT UNSIGNED NOT NULL,     /* academic.campus */
  classroomId   INT UNSIGNED NOT NULL,     /* academic.classroom */
  date          DATE NOT NULL,
  time          TIME NOT NULL,
  attended      TINYINT(1) NOT NULL DEFAULT 0,
  score         INT UNSIGNED NULL,
  gradedById    INT UNSIGNED NULL,         /* identity.departmentHead (revisor) */
  status        ENUM('Scheduled','Taken','Graded','Voided') NOT NULL DEFAULT 'Scheduled',
  KEY fkExApp    (applicationId),
  KEY fkExType   (examTypeId),
  KEY fkExTerm   (termId),
  KEY fkExCampus (campusId),
  KEY fkExClass  (classroomId),
  KEY fkExGrad   (gradedById),
  CONSTRAINT fkExApp    FOREIGN KEY (applicationId) REFERENCES admissionApplication(id),
  CONSTRAINT fkExType   FOREIGN KEY (examTypeId)    REFERENCES examType(id),
  CONSTRAINT fkExTerm   FOREIGN KEY (termId)        REFERENCES academic.academicTerm(id),
  CONSTRAINT fkExCampus FOREIGN KEY (campusId)      REFERENCES academic.campus(id),
  CONSTRAINT fkExClass  FOREIGN KEY (classroomId)   REFERENCES academic.classroom(id),
  CONSTRAINT fkExGrad   FOREIGN KEY (gradedById)    REFERENCES identity.departmentHead(userId)
) ENGINE=InnoDB;

CREATE TABLE programCampusTermCapacity (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  programId      INT UNSIGNED NOT NULL, /* academic.program */
  campusId       INT UNSIGNED NOT NULL, /* academic.campus */
  termId         INT UNSIGNED NOT NULL, /* academic.academicTerm */
  availableSeats INT UNSIGNED NOT NULL,
  assignedSeats  INT UNSIGNED NOT NULL DEFAULT 0,
  openingDate    DATE NOT NULL,
  closingDate    DATE NOT NULL,
  KEY fkPctProg (programId),
  KEY fkPctCamp (campusId),
  KEY fkPctTerm (termId),
  CONSTRAINT fkPctProg FOREIGN KEY (programId) REFERENCES academic.program(id),
  CONSTRAINT fkPctCamp FOREIGN KEY (campusId)  REFERENCES academic.campus(id),
  CONSTRAINT fkPctTerm FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE admissionWaitlist (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  programId     INT UNSIGNED NOT NULL, /* academic.program */
  campusId      INT UNSIGNED NOT NULL, /* academic.campus */
  termId        INT UNSIGNED NOT NULL, /* academic.academicTerm */
  applicationId INT UNSIGNED NOT NULL, /* admissions.admissionApplication */
  position      INT UNSIGNED NOT NULL,
  status        ENUM('Waiting','Promoted','Removed') NOT NULL DEFAULT 'Waiting',
  KEY fkAwProg (programId),
  KEY fkAwCamp (campusId),
  KEY fkAwTerm (termId),
  KEY fkAwApp  (applicationId),
  CONSTRAINT fkAwProg FOREIGN KEY (programId)  REFERENCES academic.program(id),
  CONSTRAINT fkAwCamp FOREIGN KEY (campusId)   REFERENCES academic.campus(id),
  CONSTRAINT fkAwTerm FOREIGN KEY (termId)     REFERENCES academic.academicTerm(id),
  CONSTRAINT fkAwApp  FOREIGN KEY (applicationId) REFERENCES admissionApplication(id)
) ENGINE=InnoDB;

CREATE TABLE admissionResult (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicationId       INT UNSIGNED NOT NULL, /* admissions.admissionApplication */
  decision            ENUM('ApprovedPrimary','ApprovedSecondary','Rejected') NOT NULL,
  scorePAA            INT UNSIGNED NULL,
  scorePAM            INT UNSIGNED NULL,
  scorePCCNS          INT UNSIGNED NULL,
  assignedProgramId   INT UNSIGNED NULL,     /* academic.program */
  assignedCampusId    INT UNSIGNED NULL,     /* academic.campus */
  termId              INT UNSIGNED NOT NULL, /* academic.academicTerm */
  resultDate          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  remarks             VARCHAR(255) NULL,
  KEY fkArApp  (applicationId),
  KEY fkArProg (assignedProgramId),
  KEY fkArCamp (assignedCampusId),
  KEY fkArTerm (termId),
  CONSTRAINT fkArApp  FOREIGN KEY (applicationId)     REFERENCES admissionApplication(id),
  CONSTRAINT fkArProg FOREIGN KEY (assignedProgramId) REFERENCES academic.program(id),
  CONSTRAINT fkArCamp FOREIGN KEY (assignedCampusId)  REFERENCES academic.campus(id),
  CONSTRAINT fkArTerm FOREIGN KEY (termId)            REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE seatAcceptance (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resultId          INT UNSIGNED NOT NULL, /* admissions.admissionResult */
  confirmationToken CHAR(64) NOT NULL,
  tokenIssuedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tokenExpiresAt    DATETIME NOT NULL,
  status            ENUM('Pending','Accepted','Rejected','Expired') NOT NULL DEFAULT 'Pending',
  respondedAt       DATETIME NULL,
  KEY fkSaRes (resultId),
  CONSTRAINT fkSaRes FOREIGN KEY (resultId) REFERENCES admissionResult(id)
) ENGINE=InnoDB;


/* =======================================================
   AJUSTES FINALES
======================================================= */
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;


/* =======================================================
   VISTA: usuarios con su rol principal
   (une users + credentials + credentialRoles + roles)
======================================================= */
USE identity;

CREATE OR REPLACE VIEW viewUserProfiles AS
SELECT
  c.id            AS credentialId,
  u.id            AS userId,
  CONCAT(u.firstName,' ',u.lastName) AS fullName,
  c.username      AS email,
  c.role          AS legacyRole,      -- enum histórico en credentials
  r.roleName      AS role,
  u.nationalId,
  u.accountStatus,
  c.createdAt     AS createdAt
FROM credentials c
JOIN users u              ON u.id = c.userId
LEFT JOIN credentialRoles cr ON cr.credentialId = c.id
LEFT JOIN roles r             ON r.id = cr.role;








/* =======================================================
   Asignar programa a estudiantes de ejemplo
   (Milton, Luis, Javiary, Jhonny, Carlos)
======================================================= */

UPDATE identity.students st
JOIN identity.users u ON u.id = st.userId
LEFT JOIN academic.program pIS   ON pIS.programCode   = 'IS-01'
LEFT JOIN academic.program pDER  ON pDER.programCode  = 'DERE-01'
LEFT JOIN academic.program pPSI  ON pPSI.programCode  = 'PSI-01'
LEFT JOIN academic.program pMED  ON pMED.programCode  = 'MED-01'
SET st.programId = CASE u.nationalId
  WHEN '0801-2000-02942' THEN pIS.id    -- Milton  → Ingeniería en Sistemas
  WHEN '0801-2000-02947' THEN pDER.id   -- Luis    → Derecho
  WHEN '0801-2000-02948' THEN pPSI.id   -- Javiary → Psicología
  WHEN '0801-2000-02949' THEN pMED.id   -- Jhonny  → Medicina
  WHEN '0801-2000-02950' THEN pIS.id    -- Carlos  → Ingeniería en Sistemas (repetido)
END
WHERE u.nationalId IN (
  '0801-2000-02942',
  '0801-2000-02947',
  '0801-2000-02948',
  '0801-2000-02949',
  '0801-2000-02950'
);
