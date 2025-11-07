DELIMITER $$

/* =======================================================
   identity.sp_register_user
   Recibe: fullName, email, identityNumber, accountNumber, roleName, passwordHash
======================================================= */
DROP PROCEDURE IF EXISTS identity.spRegisterUser $$
CREATE PROCEDURE identity.spRegisterUser(
  IN  pFullName       VARCHAR(120),
  IN  pEmail          VARCHAR(120),
  IN  pIdentityNumber VARCHAR(20),
  IN  pAccountNumber  VARCHAR(30),   -- requerido si STUDENT
  IN  pRoleName       VARCHAR(20),
  IN  pPasswordHash   VARCHAR(255),
  OUT pCredentialsId  INT,
  OUT pPersonId       INT,
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
proc: BEGIN
  DECLARE vProfileId   TINYINT UNSIGNED;
  DECLARE vExistsEmail INT DEFAULT 0;
  DECLARE vExistsIdent INT DEFAULT 0;
  DECLARE vNow         DATETIME;

  SET pCredentialsId = NULL; SET pPersonId = NULL;
  SET pCode = 1; SET pMessage = 'Unspecified error';
  SET vNow = NOW();

  IF pFullName IS NULL OR pEmail IS NULL OR pIdentityNumber IS NULL OR pRoleName IS NULL OR pPasswordHash IS NULL THEN
    SET pCode = 1001; SET pMessage = 'Missing required fields'; LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO vExistsEmail FROM identity.credentials WHERE username = pEmail;
  IF vExistsEmail > 0 THEN
    SET pCode = 1002; SET pMessage = 'Email already exists'; LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO vExistsIdent FROM identity.person WHERE identityNumber = pIdentityNumber;
  IF vExistsIdent > 0 THEN
    SET pCode = 1003; SET pMessage = 'Identity already exists'; LEAVE proc;
  END IF;

  SELECT idProfile INTO vProfileId FROM identity.profile WHERE profileKey = UPPER(pRoleName) LIMIT 1;
  IF vProfileId IS NULL THEN
    SET pCode = 1004; SET pMessage = 'Invalid role/profile'; LEAVE proc;
  END IF;

  START TRANSACTION;
    /* Guardar persona (fullName en firstName y lastName en blanco) */
    INSERT INTO identity.person (identityNumber, firstName, lastName, personalEmail, createdAt, accountStatus)
    VALUES (pIdentityNumber, pFullName, '', pEmail, vNow, 'Active');
    SET pPersonId = LAST_INSERT_ID();

    /* Credenciales */
    INSERT INTO identity.credentials (idPerson, username, role, passwordHash, createdAt, loginAttempts, locked)
    VALUES (pPersonId, pEmail, UPPER(pRoleName), pPasswordHash, vNow, 0, 0);
    SET pCredentialsId = LAST_INSERT_ID();

    /* Vincular a profile */
    INSERT INTO identity.credentialsProfile (idCredentials, idProfile, assignedAt)
    VALUES (pCredentialsId, vProfileId, vNow);

    /* Inserciones por rol */
    IF UPPER(pRoleName) = 'STUDENT' THEN
      IF pAccountNumber IS NULL OR pAccountNumber = '' THEN
        ROLLBACK;
        SET pCode = 1101; SET pMessage = 'accountNumber is required for STUDENT'; LEAVE proc;
      END IF;
      INSERT INTO identity.student (idPerson, accountNumber, academicLevel, idProgram, idProgramSecondary, gpa, joinDate)
      VALUES (pPersonId, pAccountNumber, 'Undergraduate', NULL, NULL, NULL, NULL);

    ELSEIF UPPER(pRoleName) = 'INSTRUCTOR' THEN
      INSERT INTO identity.instructor (idPerson, employeeNumber, idAcademicDepartment, contractDate, shift, office)
      VALUES (pPersonId, NULL, NULL, NULL, NULL, NULL);

    ELSEIF UPPER(pRoleName) = 'COORDINATOR' THEN
      INSERT INTO identity.coordinator (idPerson, area, active) VALUES (pPersonId, 'Software', 1);

    ELSEIF UPPER(pRoleName) = 'DEPT_HEAD' THEN
      INSERT INTO identity.departmentHead (idPerson, idAcademicDepartment, startDate, endDate, endReason)
      VALUES (pPersonId, NULL, DATE(vNow), NULL, NULL);

    ELSEIF UPPER(pRoleName) = 'APPLICANT' THEN
      INSERT INTO admissions.applicant (idPerson, personalEmail, phone, status)
      VALUES (pPersonId, pEmail, NULL, 'Active');
    END IF;
  COMMIT;

  SET pCode = 0; SET pMessage = 'User registered';
END $$
/* ===================== FIN ===================== */


/* =======================================================
   identity.sp_create_admin (wrapper)
======================================================= */
DROP PROCEDURE IF EXISTS identity.spCreateAdmin $$
CREATE PROCEDURE identity.spCreateAdmin(
  IN  pFullName       VARCHAR(120),
  IN  pEmail          VARCHAR(120),
  IN  pIdentityNumber VARCHAR(20),
  IN  pPasswordHash   VARCHAR(255),
  OUT pCredentialsId  INT,
  OUT pPersonId       INT,
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
BEGIN
  CALL identity.spRegisterUser(
    pFullName,
    pEmail,
    pIdentityNumber,
    NULL,            -- accountNumber
    'ADMIN',
    pPasswordHash,
    pCredentialsId,
    pPersonId,
    pCode,
    pMessage
  );
END $$


/* =======================================================
   identity.sp_list_users
   Filtros: pSearch (nombre/email/identidad), pRoleName
======================================================= */
DROP PROCEDURE IF EXISTS identity.spListUsers $$
CREATE PROCEDURE identity.spListUsers(
  IN pSearch   VARCHAR(100),
  IN pRoleName VARCHAR(20),
  IN pOffset   INT,
  IN pLimit    INT
)
BEGIN
  SET pOffset = IFNULL(pOffset, 0);
  SET pLimit  = IFNULL(pLimit, 50);
  IF pOffset < 0 THEN SET pOffset = 0; END IF;
  IF pLimit  < 1 THEN SET pLimit  = 50; END IF;

  /* filas */
  SELECT
    cr.idCredentials, per.idPerson,
    CONCAT(per.firstName,' ',per.lastName) AS fullName,
    cr.username AS email,
    cr.role     AS legacyRole,
    pr.profileKey AS profile,
    per.identityNumber,
    per.accountStatus,
    cr.createdAt
  FROM identity.credentials cr
  JOIN identity.person per ON per.idPerson = cr.idPerson
  LEFT JOIN identity.credentialsProfile cp ON cp.idCredentials = cr.idCredentials
  LEFT JOIN identity.profile pr ON pr.idProfile = cp.idProfile
  WHERE (pSearch IS NULL OR pSearch='' OR
         per.firstName LIKE CONCAT('%',pSearch,'%') OR
         per.lastName  LIKE CONCAT('%',pSearch,'%') OR
         cr.username   LIKE CONCAT('%',pSearch,'%') OR
         per.identityNumber LIKE CONCAT('%',pSearch,'%'))
    AND (pRoleName IS NULL OR pRoleName='' OR pr.profileKey = UPPER(pRoleName))
  ORDER BY cr.idCredentials ASC
  LIMIT pLimit OFFSET pOffset;

  /* total */
  SELECT COUNT(*) AS total
  FROM identity.credentials cr
  JOIN identity.person per ON per.idPerson = cr.idPerson
  LEFT JOIN identity.credentialsProfile cp ON cp.idCredentials = cr.idCredentials
  LEFT JOIN identity.profile pr ON pr.idProfile = cp.idProfile
  WHERE (pSearch IS NULL OR pSearch='' OR
         per.firstName LIKE CONCAT('%',pSearch,'%') OR
         per.lastName  LIKE CONCAT('%',pSearch,'%') OR
         cr.username   LIKE CONCAT('%',pSearch,'%') OR
         per.identityNumber LIKE CONCAT('%',pSearch,'%'))
    AND (pRoleName IS NULL OR pRoleName='' OR pr.profileKey = UPPER(pRoleName));
END $$

DELIMITER ;
