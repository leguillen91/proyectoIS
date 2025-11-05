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
  UNIQUE KEY uq_users_nationalId (nationalId)
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

  UNIQUE KEY uq_credentials_username (username),
  UNIQUE KEY uq_credentials_user (userId),
  KEY fk_credentials_user (userId),

  CONSTRAINT fk_credentials_user
    FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
  id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  roleName   VARCHAR(45)  NOT NULL,
  description VARCHAR(255) NULL,
  UNIQUE KEY uq_roles_roleName (roleName)
) ENGINE=InnoDB;

CREATE TABLE credentialRoles (
  credentialId INT UNSIGNED NOT NULL,
  roleId       TINYINT UNSIGNED NOT NULL,
  assignedAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (credentialId,roleId),
  KEY fk_cr_role (roleId),
  CONSTRAINT fk_cr_credential FOREIGN KEY (credentialId) REFERENCES credentials(id),
  CONSTRAINT fk_cr_role       FOREIGN KEY (roleId)       REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE students (
  userId            INT UNSIGNED PRIMARY KEY,
  studentNumber     VARCHAR(11)  NOT NULL,
  programLevel      ENUM('Undergraduate','Graduate') NOT NULL,
  programId         INT UNSIGNED NULL,        /* FK academic.program */
  secondaryProgramId INT UNSIGNED NULL,       /* FK academic.program */
  gpa               DECIMAL(4,2)  NULL,
  entryDate         DATE          NULL,
  KEY fk_student_program1 (programId),
  KEY fk_student_program2 (secondaryProgramId),
  CONSTRAINT fk_student_user FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  userId                  INT UNSIGNED PRIMARY KEY,
  employeeNumber          VARCHAR(20) NULL,
  academicDepartmentId    INT UNSIGNED NULL,   /* FK academic.program (o depto si se crea después) */
  hireDate                DATE NULL,
  shift                   ENUM('FullTime','PartTime') NULL,
  office                  VARCHAR(45) NULL,
  CONSTRAINT fk_teacher_user FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE departmentHead (
  userId               INT UNSIGNED PRIMARY KEY,
  academicDepartmentId INT UNSIGNED NULL,   /* FK academic.program (o depto) */
  termStartDate        DATE NOT NULL,
  termEndDate          DATE NULL,
  endReason            VARCHAR(255) NULL,
  CONSTRAINT fk_head_user FOREIGN KEY (userId) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE coordinators (
  userId INT UNSIGNED PRIMARY KEY,
  area   ENUM('Software','Music','VirtualLibrary','Admissions','Enrollment') NOT NULL,
  isActive TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_coord_user FOREIGN KEY (userId) REFERENCES users(id)
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
  UNIQUE KEY uq_faculty_code (facultyCode)
) ENGINE=InnoDB;

CREATE TABLE campus (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campusCode  VARCHAR(10) NOT NULL,
  name        VARCHAR(80) NOT NULL,
  address     VARCHAR(255) NULL,
  phone       VARCHAR(20)  NULL,
  status      VARCHAR(45)  NOT NULL DEFAULT 'Active',
  UNIQUE KEY uq_campus_code (campusCode)
) ENGINE=InnoDB;

CREATE TABLE building (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campusId  INT UNSIGNED NOT NULL,
  name      VARCHAR(80) NOT NULL,
  status    VARCHAR(45) NOT NULL DEFAULT 'Active',
  KEY fk_building_campus (campusId),
  CONSTRAINT fk_building_campus FOREIGN KEY (campusId) REFERENCES campus(id)
) ENGINE=InnoDB;

CREATE TABLE classroom (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buildingId  INT UNSIGNED NOT NULL,
  roomNumber  VARCHAR(10)  NOT NULL,
  capacity    INT UNSIGNED NOT NULL,
  roomType    ENUM('Classroom','Laboratory') NOT NULL,
  status      VARCHAR(45) NOT NULL DEFAULT 'Active',
  KEY fk_classroom_building (buildingId),
  CONSTRAINT fk_classroom_building FOREIGN KEY (buildingId) REFERENCES building(id)
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
  UNIQUE KEY uq_program_code (programCode),
  KEY fk_program_faculty (facultyId),
  KEY fk_program_campus (campusId),
  CONSTRAINT fk_program_faculty FOREIGN KEY (facultyId) REFERENCES faculty(id),
  CONSTRAINT fk_program_campus  FOREIGN KEY (campusId)  REFERENCES campus(id)
) ENGINE=InnoDB;

CREATE TABLE course (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courseCode     VARCHAR(15) NOT NULL,
  name           VARCHAR(100) NOT NULL,
  credits        TINYINT UNSIGNED NOT NULL,
  category       ENUM('Required','Elective','Selective') NOT NULL,
  status         VARCHAR(45) NOT NULL DEFAULT 'Active',
  UNIQUE KEY uq_course_code (courseCode)
) ENGINE=InnoDB;

CREATE TABLE prerequisite (
  courseId        INT UNSIGNED NOT NULL,
  prerequisiteId  INT UNSIGNED NOT NULL,
  PRIMARY KEY (courseId,prerequisiteId),
  KEY fk_pre_req (prerequisiteId),
  CONSTRAINT fk_pre_course   FOREIGN KEY (courseId)       REFERENCES course(id),
  CONSTRAINT fk_pre_course_2 FOREIGN KEY (prerequisiteId) REFERENCES course(id)
) ENGINE=InnoDB;

CREATE TABLE curriculumPlan (
  programId   INT UNSIGNED NOT NULL,
  courseId    INT UNSIGNED NOT NULL,
  semester    TINYINT UNSIGNED NOT NULL,
  planType    ENUM('Required','Elective') NOT NULL,
  PRIMARY KEY (programId,courseId),
  KEY fk_plan_course (courseId),
  CONSTRAINT fk_plan_program FOREIGN KEY (programId) REFERENCES program(id),
  CONSTRAINT fk_plan_course  FOREIGN KEY (courseId)  REFERENCES course(id)
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
  ADD CONSTRAINT fk_student_program1 FOREIGN KEY (programId)           REFERENCES academic.program(id),
  ADD CONSTRAINT fk_student_program2 FOREIGN KEY (secondaryProgramId)  REFERENCES academic.program(id);

ALTER TABLE teachers
  ADD CONSTRAINT fk_teacher_dept FOREIGN KEY (academicDepartmentId) REFERENCES academic.program(id);

ALTER TABLE departmentHead
  ADD CONSTRAINT fk_head_dept FOREIGN KEY (academicDepartmentId) REFERENCES academic.program(id);


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
  KEY fk_sec_course   (courseId),
  KEY fk_sec_term     (termId),
  KEY fk_sec_teacher  (teacherId),
  KEY fk_sec_classroom(classroomId),
  CONSTRAINT fk_sec_course    FOREIGN KEY (courseId)    REFERENCES academic.course(id),
  CONSTRAINT fk_sec_term      FOREIGN KEY (termId)      REFERENCES academic.academicTerm(id),
  CONSTRAINT fk_sec_teacher   FOREIGN KEY (teacherId)   REFERENCES identity.teachers(userId),
  CONSTRAINT fk_sec_classroom FOREIGN KEY (classroomId) REFERENCES academic.classroom(id)
) ENGINE=InnoDB;

CREATE TABLE sectionSchedule (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sectionId  INT UNSIGNED NOT NULL,
  days       VARCHAR(45)  NOT NULL,     
  startTime  TIME NOT NULL,
  endTime    TIME NOT NULL,
  classroomId INT UNSIGNED NOT NULL,
  KEY fk_ss_sect  (sectionId),
  KEY fk_ss_class (classroomId),
  CONSTRAINT fk_ss_sect  FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fk_ss_class FOREIGN KEY (classroomId) REFERENCES academic.classroom(id)
) ENGINE=InnoDB;

CREATE TABLE waitlist (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId    INT UNSIGNED NOT NULL,  /* FK identity.students(userId) */
  sectionId    INT UNSIGNED NOT NULL,
  requestedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  position     INT UNSIGNED NOT NULL,
  priority     TINYINT UNSIGNED NULL,
  served       TINYINT(1) NOT NULL DEFAULT 0,
  KEY fk_wl_student (studentId),
  KEY fk_wl_section (sectionId),
  CONSTRAINT fk_wl_student FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fk_wl_section FOREIGN KEY (sectionId)  REFERENCES section(id)
) ENGINE=InnoDB;

CREATE TABLE payment (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId   INT UNSIGNED NOT NULL,  /* FK identity.students */
  termId      INT UNSIGNED NOT NULL,  /* FK academic.academicTerm */
  concept     ENUM('EnrollmentFee','Makeup','Other') NOT NULL,
  amount      DECIMAL(10,2) NOT NULL,
  method      ENUM('Cash','Card') NOT NULL,
  status      ENUM('Recorded','Confirmed','Rejected','Voided') NOT NULL,
  paidAt      DATETIME NOT NULL,
  KEY fk_pay_student (studentId),
  KEY fk_pay_term    (termId),
  CONSTRAINT fk_pay_student FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fk_pay_term    FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE studentEnrollment (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studentId     INT UNSIGNED NOT NULL, /* FK identity.students */
  sectionId     INT UNSIGNED NOT NULL, /* FK enrollment.section */
  enrolledAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Active','Withdrawn','Cancelled') NOT NULL,
  finalGrade    DECIMAL(5,2) NULL,
  paymentId     INT UNSIGNED NOT NULL,
  KEY fk_enr_student (studentId),
  KEY fk_enr_section (sectionId),
  KEY fk_enr_payment (paymentId),
  CONSTRAINT fk_enr_student FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fk_enr_section FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fk_enr_payment FOREIGN KEY (paymentId)  REFERENCES payment(id)
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
  KEY fk_sr_student (studentId),
  KEY fk_sr_term    (termId),
  CONSTRAINT fk_sr_student FOREIGN KEY (studentId) REFERENCES identity.students(userId),
  CONSTRAINT fk_sr_term    FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id),
  CONSTRAINT fk_sr_prog1   FOREIGN KEY (currentProgramId)   REFERENCES academic.program(id),
  CONSTRAINT fk_sr_prog2   FOREIGN KEY (requestedProgramId) REFERENCES academic.program(id),
  CONSTRAINT fk_sr_camp1   FOREIGN KEY (currentCampusId)    REFERENCES academic.campus(id),
  CONSTRAINT fk_sr_camp2   FOREIGN KEY (requestedCampusId)  REFERENCES academic.campus(id),
  CONSTRAINT fk_sr_coord   FOREIGN KEY (assignedCoordinatorId) REFERENCES identity.coordinators(userId),
  CONSTRAINT fk_sr_approved FOREIGN KEY (approvedById)         REFERENCES identity.departmentHead(userId)
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
  KEY fk_sreq_req (requestId),
  KEY fk_sreq_sec (sectionId),
  CONSTRAINT fk_sreq_req FOREIGN KEY (requestId) REFERENCES studentRequest(id),
  CONSTRAINT fk_sreq_sec FOREIGN KEY (sectionId)  REFERENCES section(id),
  CONSTRAINT fk_sreq_apr FOREIGN KEY (approvedById) REFERENCES identity.departmentHead(userId)
) ENGINE=InnoDB;


/* =======================================================
   RESOURCES
======================================================= */
USE resources;

CREATE TABLE tags (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  UNIQUE KEY uq_tag_name (name)
) ENGINE=InnoDB;

/* Biblioteca */
CREATE TABLE libraryResource (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courseId        INT UNSIGNED NULL, /* academic.course */
  departmentId    INT UNSIGNED NULL, /* academic.program */
  title           VARCHAR(100) NOT NULL,
  author          VARCHAR(100) NOT NULL,
  publicationYear YEAR NULL,
  description     VARCHAR(255) NULL,
  resourceType    VARCHAR(45) NOT NULL,  /* Libro, Artículo, etc. */
  pdfFile         LONGBLOB NULL,
  sizeBytes       INT UNSIGNED NULL,
  uploadedAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  permissions     VARCHAR(45) NULL,
  KEY fk_lr_course (courseId),
  KEY fk_lr_dept   (departmentId),
  CONSTRAINT fk_lr_course FOREIGN KEY (courseId)     REFERENCES academic.course(id),
  CONSTRAINT fk_lr_dept   FOREIGN KEY (departmentId) REFERENCES academic.program(id)
) ENGINE=InnoDB;

CREATE TABLE libraryResourceTag (
  resourceId INT UNSIGNED NOT NULL,
  tagId      INT UNSIGNED NOT NULL,
  PRIMARY KEY (resourceId,tagId),
  KEY fk_lrt_tag (tagId),
  CONSTRAINT fk_lrt_res FOREIGN KEY (resourceId) REFERENCES libraryResource(id),
  CONSTRAINT fk_lrt_tag FOREIGN KEY (tagId)      REFERENCES tags(id)
) ENGINE=InnoDB;

/* Música */
CREATE TABLE musicResource (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  courseId        INT UNSIGNED NULL, /* academic.course */
  departmentId    INT UNSIGNED NULL, /* academic.program */
  title           VARCHAR(100) NOT NULL,
  composer        VARCHAR(100) NULL,
  author          VARCHAR(100) NOT NULL,
  publicationYear YEAR NULL,
  description     VARCHAR(255) NULL,
  resourceType    VARCHAR(45) NOT NULL,   /* Partitura, Audio, etc. */
  resourceFormat  VARCHAR(45) NOT NULL,   /* PDF, MP3, etc. */
  sizeBytes       INT UNSIGNED NULL,
  uploadedAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  durationSeconds INT UNSIGNED NULL,
  pdfFile         LONGBLOB NULL,
  audioFile       LONGBLOB NULL,
  scoreFile       VARCHAR(100) NULL,
  KEY fk_mr_course (courseId),
  KEY fk_mr_dept   (departmentId),
  CONSTRAINT fk_mr_course FOREIGN KEY (courseId)     REFERENCES academic.course(id),
  CONSTRAINT fk_mr_dept   FOREIGN KEY (departmentId) REFERENCES academic.program(id)
) ENGINE=InnoDB;

CREATE TABLE musicResourceTag (
  resourceId INT UNSIGNED NOT NULL,
  tagId      INT UNSIGNED NOT NULL,
  PRIMARY KEY (resourceId,tagId),
  KEY fk_mrt_tag (tagId),
  CONSTRAINT fk_mrt_res FOREIGN KEY (resourceId) REFERENCES musicResource(id),
  CONSTRAINT fk_mrt_tag FOREIGN KEY (tagId)      REFERENCES tags(id)
) ENGINE=InnoDB;


/* =======================================================
   ADMISSIONS
======================================================= */
USE admissions;

CREATE TABLE applicant (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userId       INT UNSIGNED NOT NULL, /* identity.users (antes de ser alumno) */
  personalEmail VARCHAR(120) NULL,
  phone         VARCHAR(45)  NULL,
  createdAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status        ENUM('Active','Inactive','Blocked') NOT NULL DEFAULT 'Active',
  KEY fk_app_user (userId),
  CONSTRAINT fk_app_user FOREIGN KEY (userId) REFERENCES identity.users(id)
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
  KEY fk_aa_app (applicantId),
  KEY fk_aa_term (termId),
  KEY fk_aa_prog1 (primaryProgramId),
  KEY fk_aa_prog2 (secondaryProgramId),
  KEY fk_aa_campus (campusId),
  CONSTRAINT fk_aa_app    FOREIGN KEY (applicantId)        REFERENCES applicant(id),
  CONSTRAINT fk_aa_term   FOREIGN KEY (termId)             REFERENCES academic.academicTerm(id),
  CONSTRAINT fk_aa_prog1  FOREIGN KEY (primaryProgramId)   REFERENCES academic.program(id),
  CONSTRAINT fk_aa_prog2  FOREIGN KEY (secondaryProgramId) REFERENCES academic.program(id),
  CONSTRAINT fk_aa_campus FOREIGN KEY (campusId)           REFERENCES academic.campus(id)
) ENGINE=InnoDB;

CREATE TABLE applicationDocument (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  applicationId INT UNSIGNED NOT NULL,
  documentType ENUM('HighSchoolCertificate') NOT NULL,
  pdfFile     LONGBLOB NOT NULL,
  sizeBytes   INT UNSIGNED NOT NULL,
  uploadedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fk_doc_app (applicationId),
  CONSTRAINT fk_doc_app FOREIGN KEY (applicationId) REFERENCES admissionApplication(id)
) ENGINE=InnoDB;

CREATE TABLE examType (
  id         TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       ENUM('PAA','PAM','PCCNS') NOT NULL,
  maxScore   INT UNSIGNED NOT NULL,
  description VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE programExamRequirement (
  programId     INT UNSIGNED NOT NULL, /* academic.program */
  examTypeId    TINYINT UNSIGNED NOT NULL, /* admissions.examType */
  minimumScore  INT UNSIGNED NOT NULL,
  required      TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (programId,examTypeId),
  KEY fk_per_type (examTypeId),
  CONSTRAINT fk_per_prog FOREIGN KEY (programId)  REFERENCES academic.program(id),
  CONSTRAINT fk_per_type FOREIGN KEY (examTypeId) REFERENCES examType(id)
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
  KEY fk_ex_app   (applicationId),
  KEY fk_ex_type  (examTypeId),
  KEY fk_ex_term  (termId),
  KEY fk_ex_campus(campusId),
  KEY fk_ex_class (classroomId),
  KEY fk_ex_grad  (gradedById),
  CONSTRAINT fk_ex_app   FOREIGN KEY (applicationId) REFERENCES admissionApplication(id),
  CONSTRAINT fk_ex_type  FOREIGN KEY (examTypeId)    REFERENCES examType(id),
  CONSTRAINT fk_ex_term  FOREIGN KEY (termId)        REFERENCES academic.academicTerm(id),
  CONSTRAINT fk_ex_campus FOREIGN KEY (campusId)     REFERENCES academic.campus(id),
  CONSTRAINT fk_ex_class  FOREIGN KEY (classroomId)  REFERENCES academic.classroom(id),
  CONSTRAINT fk_ex_grad   FOREIGN KEY (gradedById)   REFERENCES identity.departmentHead(userId)
) ENGINE=InnoDB;

CREATE TABLE programCampusTermCapacity (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  programId       INT UNSIGNED NOT NULL, /* academic.program */
  campusId        INT UNSIGNED NOT NULL, /* academic.campus */
  termId          INT UNSIGNED NOT NULL, /* academic.academicTerm */
  availableSeats  INT UNSIGNED NOT NULL,
  assignedSeats   INT UNSIGNED NOT NULL DEFAULT 0,
  openingDate     DATE NOT NULL,
  closingDate     DATE NOT NULL,
  KEY fk_pct_prog (programId),
  KEY fk_pct_camp (campusId),
  KEY fk_pct_term (termId),
  CONSTRAINT fk_pct_prog FOREIGN KEY (programId) REFERENCES academic.program(id),
  CONSTRAINT fk_pct_camp FOREIGN KEY (campusId)  REFERENCES academic.campus(id),
  CONSTRAINT fk_pct_term FOREIGN KEY (termId)    REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE admissionWaitlist (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  programId     INT UNSIGNED NOT NULL, /* academic.program */
  campusId      INT UNSIGNED NOT NULL, /* academic.campus */
  termId        INT UNSIGNED NOT NULL, /* academic.academicTerm */
  applicationId INT UNSIGNED NOT NULL, /* admissions.admissionApplication */
  position      INT UNSIGNED NOT NULL,
  status        ENUM('Waiting','Promoted','Removed') NOT NULL DEFAULT 'Waiting',
  KEY fk_aw_prog (programId),
  KEY fk_aw_camp (campusId),
  KEY fk_aw_term (termId),
  KEY fk_aw_app  (applicationId),
  CONSTRAINT fk_aw_prog FOREIGN KEY (programId)  REFERENCES academic.program(id),
  CONSTRAINT fk_aw_camp FOREIGN KEY (campusId)   REFERENCES academic.campus(id),
  CONSTRAINT fk_aw_term FOREIGN KEY (termId)     REFERENCES academic.academicTerm(id),
  CONSTRAINT fk_aw_app  FOREIGN KEY (applicationId) REFERENCES admissionApplication(id)
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
  KEY fk_ar_app (applicationId),
  KEY fk_ar_prog (assignedProgramId),
  KEY fk_ar_camp (assignedCampusId),
  KEY fk_ar_term (termId),
  CONSTRAINT fk_ar_app  FOREIGN KEY (applicationId)     REFERENCES admissionApplication(id),
  CONSTRAINT fk_ar_prog FOREIGN KEY (assignedProgramId) REFERENCES academic.program(id),
  CONSTRAINT fk_ar_camp FOREIGN KEY (assignedCampusId)  REFERENCES academic.campus(id),
  CONSTRAINT fk_ar_term FOREIGN KEY (termId)            REFERENCES academic.academicTerm(id)
) ENGINE=InnoDB;

CREATE TABLE seatAcceptance (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  resultId          INT UNSIGNED NOT NULL, /* admissions.admissionResult */
  confirmationToken CHAR(64) NOT NULL,
  tokenIssuedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tokenExpiresAt    DATETIME NOT NULL,
  status            ENUM('Pending','Accepted','Rejected','Expired') NOT NULL DEFAULT 'Pending',
  respondedAt       DATETIME NULL,
  KEY fk_sa_res (resultId),
  CONSTRAINT fk_sa_res FOREIGN KEY (resultId) REFERENCES admissionResult(id)
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

CREATE OR REPLACE VIEW view_user_profiles AS
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