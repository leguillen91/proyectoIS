DELIMITER $$

/* ---------------------------------------------
   - Asigna automáticamente revisor para recursos del módulo Software.
---------------------------------------------- */
DROP PROCEDURE IF EXISTS resources.spSwAssignReviewerAuto $$
CREATE PROCEDURE resources.spSwAssignReviewerAuto(IN pResourceId INT UNSIGNED)
BEGIN
  DECLARE vExists   INT DEFAULT 0;
  DECLARE vModule   VARCHAR(20);
  DECLARE vAssigned INT UNSIGNED;
  DECLARE vRole     VARCHAR(20);

  /* Validar recurso y bloquear fila para update */
  SELECT COUNT(*), r.module
    INTO vExists, vModule
  FROM resources.resource r
  WHERE r.idResource = pResourceId
  FOR UPDATE;

  IF vExists = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resource not found';
  END IF;

  /* Exigiendo que sea del modulo de software */
   IF vModule <> 'Software' THEN
     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resource is not from Software module';
   END IF;

  /* DepartmentHead activo con MENOR carga de asignaciones activas */
  SELECT dh.idPerson
    INTO vAssigned
  FROM identity.departmentHead dh
  WHERE dh.endDate IS NULL
  ORDER BY (
      SELECT COUNT(*)
      FROM resources.reviewAssignment ra
      WHERE ra.assignedToPersonId = dh.idPerson
        AND ra.active = 1
    ) ASC,
    dh.startDate DESC
  LIMIT 1;

  IF vAssigned IS NOT NULL THEN
    SET vRole := 'DEPT_HEAD';
  ELSE
    /* Coordinator de área 'Software' activo con MENOR carga */
    SELECT c.idPerson
      INTO vAssigned
    FROM identity.coordinator c
    WHERE c.area = 'Software' AND c.active = 1
    ORDER BY (
        SELECT COUNT(*)
        FROM resources.reviewAssignment ra
        WHERE ra.assignedToPersonId = c.idPerson
          AND ra.active = 1
      ) ASC,
      c.idPerson ASC
    LIMIT 1;

    SET vRole := 'COORDINATOR';
  END IF;

  IF vAssigned IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No reviewer available';
  END IF;

  /* Registrar asignación y actualizar estado del recurso */
  INSERT INTO resources.reviewAssignment
    (resourceId, assignedToPersonId, assignedRole, active)
  VALUES
    (pResourceId, vAssigned, vRole, 1);

  UPDATE resources.resource
     SET status = 'UnderReview'
   WHERE idResource = pResourceId;

  /* Devolver un pequeño resumen para el front */
  SELECT pResourceId AS resourceId, vAssigned AS reviewerPersonId, vRole AS reviewerRole, vModule AS module;
END $$

DELIMITER ;
