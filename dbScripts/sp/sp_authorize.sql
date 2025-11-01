/* =======================================================
   spGetUserContext
   Envío al backend: email (usuario)
   Backend hace:
     - CALL spGetUserContext(:email)
     - Parte1: usuario+perfil; Parte2: permisos (permissionCode)
======================================================= */
DROP PROCEDURE IF EXISTS spGetUserContext $$
CREATE PROCEDURE spGetUserContext(
  IN pEmail VARCHAR(120)
)
BEGIN
  /* Parte 1 */
  SELECT
    c.idCredenciales, per.idPersona,
    CONCAT(per.nombres,' ',per.apellidos) AS fullName,
    c.usuario AS email,
    c.rol     AS legacyRol,
    pr.clavePerfil AS perfil,
    per.estadoCuenta,
    c.fechaCreacion AS createdAt
  FROM credenciales c
  JOIN persona per ON per.idPersona=c.idPersona
  LEFT JOIN credencialesXperfil cxp ON cxp.idCredenciales=c.idCredenciales
  LEFT JOIN perfil pr ON pr.idPerfil=cxp.idPerfil
  WHERE c.usuario = pEmail
  LIMIT 1;

  /* Parte 2 */
  SELECT DISTINCT pm.permissionCode
  FROM credenciales c
  JOIN credencialesXperfil cxp ON cxp.idCredenciales=c.idCredenciales
  JOIN rolePermissions rp ON rp.idPerfil=cxp.idPerfil
  JOIN permissions pm ON pm.idPermission=rp.idPermission
  WHERE c.usuario = pEmail
  ORDER BY pm.permissionCode;
END $$
/* ===================== FIN  ===================== */




/* =======================================================
   spEnsureRolePermission
   Envío al backend: roleName, permissionCode
   Backend hace:
     - CALL spEnsureRolePermission(:roleName,:permissionCode,@code,@msg)
======================================================= */
DROP PROCEDURE IF EXISTS spEnsureRolePermission $$
CREATE PROCEDURE spEnsureRolePermission(
  IN  pRoleName       VARCHAR(20),
  IN  pPermissionCode VARCHAR(100),
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
proc: BEGIN
  DECLARE vPerfilId TINYINT UNSIGNED;
  DECLARE vPermId   INT UNSIGNED;

  SET pCode=1; SET pMessage='Error no especificado';

  SELECT idPerfil INTO vPerfilId FROM perfil WHERE clavePerfil=UPPER(pRoleName) LIMIT 1;
  IF vPerfilId IS NULL THEN
    SET pCode=1001; SET pMessage='Perfil no existe'; LEAVE proc;
  END IF;

  SELECT idPermission INTO vPermId FROM permissions WHERE permissionCode=pPermissionCode LIMIT 1;
  IF vPermId IS NULL THEN
    INSERT INTO permissions(permissionCode,description) VALUES (pPermissionCode,NULL);
    SET vPermId=LAST_INSERT_ID();
  END IF;

  INSERT IGNORE INTO rolePermissions(idPerfil,idPermission) VALUES (vPerfilId,vPermId);

  SET pCode=0; SET pMessage='Asignado';
END $$
/* ===================== FIN ===================== */

DELIMITER ;