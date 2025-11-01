/* =======================================================
   spRegisterUser
   Envío al backend: fullName, email, identityNumber, accountNumber, roleName, passwordHash
   Backend hace:
     - Hashear (argon2id + pepper) -> passwordHash
     - CALL spRegisterUser(..., @oCredId, @oPersonaId, @oCode, @oMsg)
     - Leer outs y responder al cliente (HTTP 200 si oCode=0)
======================================================= */
DROP PROCEDURE IF EXISTS spRegisterUser $$
CREATE PROCEDURE spRegisterUser(
  IN  pFullName       VARCHAR(120),
  IN  pEmail          VARCHAR(120),
  IN  pIdentityNumber VARCHAR(20),
  IN  pAccountNumber  VARCHAR(30),    -- requerido si ESTUDIANTE
  IN  pRoleName       VARCHAR(20),    -- 'ADMIN','ESTUDIANTE','DOCENTE','COORDINADOR','JEFE_DEPTO','ASPIRANTE'
  IN  pPasswordHash   VARCHAR(255),
  OUT pCredencialesId INT,
  OUT pPersonaId      INT,
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
proc: BEGIN
  DECLARE vPerfilId TINYINT UNSIGNED;
  DECLARE vExistsEmail INT DEFAULT 0;
  DECLARE vExistsIdent INT DEFAULT 0;
  DECLARE vNow DATETIME;

  SET pCredencialesId = NULL; SET pPersonaId = NULL;
  SET pCode = 1; SET pMessage = 'Error no especificado';
  SET vNow = NOW();

  IF pFullName IS NULL OR pEmail IS NULL OR pIdentityNumber IS NULL OR pRoleName IS NULL OR pPasswordHash IS NULL THEN
    SET pCode = 1001; SET pMessage = 'Campos requeridos faltantes'; LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO vExistsEmail FROM credenciales WHERE usuario = pEmail;
  IF vExistsEmail > 0 THEN
    SET pCode = 1002; SET pMessage = 'El email ya existe'; LEAVE proc;
  END IF;

  SELECT COUNT(*) INTO vExistsIdent FROM persona WHERE numeroIdentidad = pIdentityNumber;
  IF vExistsIdent > 0 THEN
    SET pCode = 1003; SET pMessage = 'La identidad ya existe'; LEAVE proc;
  END IF;

  SELECT idPerfil INTO vPerfilId FROM perfil WHERE clavePerfil = UPPER(pRoleName) LIMIT 1;
  IF vPerfilId IS NULL THEN
    SET pCode = 1004; SET pMessage = 'Perfil/rol no válido'; LEAVE proc;
  END IF;

  START TRANSACTION;
    /* persona: guardo fullName en nombres (apellidos en blanco para respetar el payload del backend) */
    INSERT INTO persona (numeroIdentidad, nombres, apellidos, correoPersonal, fechaRegistro, estadoCuenta)
    VALUES (pIdentityNumber, pFullName, '', pEmail, vNow, 'Activo');
    SET pPersonaId = LAST_INSERT_ID();

    /* credenciales con enum rol */
    INSERT INTO credenciales (idPersona, usuario, rol, claveHash, fechaCreacion, intentosLogin, bloqueado)
    VALUES (pPersonaId, pEmail, UPPER(pRoleName), pPasswordHash, vNow, 0, 0);
    SET pCredencialesId = LAST_INSERT_ID();

    /* link a perfil */
    INSERT INTO credencialesXperfil (idCredenciales, idPerfil) VALUES (pCredencialesId, vPerfilId);

    /* inserciones específicas por rol */
    IF UPPER(pRoleName) = 'ESTUDIANTE' THEN
      IF pAccountNumber IS NULL OR pAccountNumber = '' THEN
        ROLLBACK;
        SET pCode = 1101; SET pMessage = 'accountNumber es requerido para ESTUDIANTE'; LEAVE proc;
      END IF;
      INSERT INTO alumno (idPersona, numeroCuenta, preparacionAcademica, idCarrera, idCarreraSecundaria, indiceAcademico, fechaIngreso)
      VALUES (pPersonaId, pAccountNumber, 'Pregrado', NULL, NULL, NULL, NULL);
    ELSEIF UPPER(pRoleName) = 'DOCENTE' THEN
      INSERT INTO docente (idPersona, numeroEmpleado, idDepartamentoAcademico, fechaContrato, shift, cubiculo)
      VALUES (pPersonaId, NULL, NULL, NULL, NULL, NULL);
    ELSEIF UPPER(pRoleName) = 'COORDINADOR' THEN
      INSERT INTO coordinador (idPersona, area, activo) VALUES (pPersonaId, 'Software', 1);
    ELSEIF UPPER(pRoleName) = 'JEFE_DEPTO' THEN
      INSERT INTO jefeDepartamento (idPersona, idDepartamentoAcademico, fechaInicioCargo, fechaFinCargo, razonFinalizacion)
      VALUES (pPersonaId, NULL, DATE(vNow), NULL, NULL);
    ELSEIF UPPER(pRoleName) = 'ASPIRANTE' THEN
      /* crear registro de aspirante en esquema admisiones */
      INSERT INTO admisiones.aspirante (idPersona, correoPersonal, telefono, estado)
      VALUES (pPersonaId, pEmail, NULL, 'Activo');
    END IF;
  COMMIT;

  SET pCode = 0; SET pMessage = 'Usuario registrado';
END $$
/* ===================== FIN  ===================== */


/* =======================================================
   spCreateAdmin
   Envío al backend: fullName, email, identityNumber, passwordHash
   Backend hace:
     - Hashear y llamar
     - Interpreta outs; si pCode=0 => OK
======================================================= */
DROP PROCEDURE IF EXISTS spCreateAdmin $$
CREATE PROCEDURE spCreateAdmin(
  IN  pFullName       VARCHAR(120),
  IN  pEmail          VARCHAR(120),
  IN  pIdentityNumber VARCHAR(20),
  IN  pPasswordHash   VARCHAR(255),
  OUT pCredencialesId INT,
  OUT pPersonaId      INT,
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
BEGIN
  CALL spRegisterUser(
    pFullName,
    pEmail,
    pIdentityNumber,
    NULL,            -- accountNumber (no aplica)
    'ADMIN',
    pPasswordHash,
    pCredencialesId,
    pPersonaId,
    pCode,
    pMessage
  );
END $$
/* ===================== FIN  ===================== */


/* =======================================================
   spListUsers
   Envío al backend: search (nullable), roleName (nullable), offset, limit
   Backend hace:
     - CALL spListUsers(...); leer filas, nextResult total
======================================================= */
DROP PROCEDURE IF EXISTS spListUsers $$
CREATE PROCEDURE spListUsers(
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

  /* filas: datos */
  SELECT
    c.idCredenciales, per.idPersona,
    CONCAT(per.nombres,' ',per.apellidos) AS fullName,
    c.usuario AS email,
    c.rol     AS legacyRol,
    pr.clavePerfil AS perfil,
    per.numeroIdentidad,
    per.estadoCuenta,
    c.fechaCreacion AS createdAt
  FROM credenciales c
  JOIN persona per ON per.idPersona = c.idPersona
  LEFT JOIN credencialesXperfil cxp ON cxp.idCredenciales = c.idCredenciales
  LEFT JOIN perfil pr ON pr.idPerfil = cxp.idPerfil
  WHERE (pSearch   IS NULL OR pSearch   = '' OR
         per.nombres LIKE CONCAT('%',pSearch,'%') OR
         per.apellidos LIKE CONCAT('%',pSearch,'%') OR
         c.usuario LIKE CONCAT('%',pSearch,'%') OR
         per.numeroIdentidad LIKE CONCAT('%',pSearch,'%'))
    AND (pRoleName IS NULL OR pRoleName = '' OR pr.clavePerfil = UPPER(pRoleName))
  ORDER BY c.idCredenciales ASC
  LIMIT pLimit OFFSET pOffset;

  /* total */
  SELECT COUNT(*) AS total
  FROM credenciales c
  JOIN persona per ON per.idPersona = c.idPersona
  LEFT JOIN credencialesXperfil cxp ON cxp.idCredenciales = c.idCredenciales
  LEFT JOIN perfil pr ON pr.idPerfil = cxp.idPerfil
  WHERE (pSearch   IS NULL OR pSearch   = '' OR
         per.nombres LIKE CONCAT('%',pSearch,'%') OR
         per.apellidos LIKE CONCAT('%',pSearch,'%') OR
         c.usuario LIKE CONCAT('%',pSearch,'%') OR
         per.numeroIdentidad LIKE CONCAT('%',pSearch,'%'))
    AND (pRoleName IS NULL OR pRoleName = '' OR pr.clavePerfil = UPPER(pRoleName));
END $$
/* ===================== FIN  ===================== */