DELIMITER $$

/* =======================================================
   identity.sp_revoke_token 
======================================================= */
DROP PROCEDURE IF EXISTS identity.sp_revoke_token $$
CREATE PROCEDURE identity.sp_revoke_token(
  IN  pJti            VARCHAR(64),
  IN  pCredentialsId  INT,
  OUT pCode           INT,
  OUT pMessage        VARCHAR(200)
)
BEGIN
  IF pJti IS NULL OR pJti='' THEN
    SET pCode=1001; SET pMessage='JTI required'; LEAVE proc;
  END IF;

  INSERT INTO identity.revokedTokens (jti, idCredentials)
  VALUES (pJti, pCredentialsId)
  ON DUPLICATE KEY UPDATE revokedAt=revokedAt, idCredentials=COALESCE(idCredentials,pCredentialsId);

  SET pCode=0; SET pMessage='Token revoked / already revoked';
END $$


/* =======================================================
   identity.sp_is_token_revoked
======================================================= */
DROP PROCEDURE IF EXISTS identity.sp_is_token_revoked $$
CREATE PROCEDURE identity.sp_is_token_revoked(IN pJti VARCHAR(64))
BEGIN
  SELECT EXISTS(SELECT 1 FROM identity.revokedTokens WHERE jti=pJti) AS isRevoked;
END $$

DELIMITER ;
