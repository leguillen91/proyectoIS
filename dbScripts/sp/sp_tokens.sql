/* =======================================================
   D.4 spRevokeToken
   Envío (backend): jti, credencialesId (opcional)
   Backend hace:
     - CALL spRevokeToken(:jti, :credencialesId, @code, @msg)
     - Interpretar outs (idempotente)
======================================================= */
DROP PROCEDURE IF EXISTS spRevokeToken $$
CREATE PROCEDURE spRevokeToken(
  IN  pJti             VARCHAR(64),
  IN  pCredencialesId  INT,
  OUT pCode            INT,
  OUT pMessage         VARCHAR(200)
)
BEGIN
  IF pJti IS NULL OR pJti='' THEN
    SET pCode=1001; SET pMessage='JTI requerido'; LEAVE BEGIN;
  END IF;

  INSERT INTO revokedTokens (jti, idCredenciales)
  VALUES (pJti, pCredencialesId)
  ON DUPLICATE KEY UPDATE revokedAt=revokedAt, idCredenciales=COALESCE(idCredenciales,pCredencialesId);

  SET pCode=0; SET pMessage='Token revocado / ya revocado';
END $$
/* ===================== FIN spRevokeToken ===================== */


/* =======================================================
   D.5 spIsTokenRevoked
   Envío (backend): jti
   Backend hace:
     - CALL spIsTokenRevoked(:jti); leer campo isRevoked (0/1)
======================================================= */
DROP PROCEDURE IF EXISTS spIsTokenRevoked $$
CREATE PROCEDURE spIsTokenRevoked(
  IN pJti VARCHAR(64)
)
BEGIN
  SELECT EXISTS(SELECT 1 FROM revokedTokens WHERE jti=pJti) AS isRevoked;
END $$
/* ===================== FIN spIsTokenRevoked ===================== */