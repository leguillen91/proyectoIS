DELIMITER $$

/* ---------------------------------------------
   - Asigna automáticamente revisor para recursos del módulo Software.
---------------------------------------------- */
DROP PROCEDURE IF EXISTS resources.sp_sw_assign_reviewer_auto $$
CREATE PROCEDURE resources.sp_sw_assign_reviewer_auto(IN p_resource_id INT UNSIGNED)
BEGIN
  DECLARE v_exists   INT DEFAULT 0;
  DECLARE v_module   VARCHAR(20);
  DECLARE v_assigned INT UNSIGNED;
  DECLARE v_role     VARCHAR(20);

  /* Validar recurso y bloquear fila para update */
  SELECT COUNT(*), r.module
    INTO v_exists, v_module
  FROM resources.resource r
  WHERE r.idResource = p_resource_id
  FOR UPDATE;

  IF v_exists = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resource not found';
  END IF;

  /* Exigiendo que sea del modulo de software */
   IF v_module <> 'Software' THEN
     SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resource is not from Software module';
   END IF;

  /* DepartmentHead activo con MENOR carga de asignaciones activas */
  SELECT dh.idPerson
    INTO v_assigned
  FROM identity.departmentHead dh
  WHERE dh.endDate IS NULL
  ORDER BY (
      SELECT COUNT(*)
      FROM resources.review_assignment ra
      WHERE ra.assignedToPersonId = dh.idPerson
        AND ra.active = 1
    ) ASC,
    dh.startDate DESC
  LIMIT 1;

  IF v_assigned IS NOT NULL THEN
    SET v_role := 'DEPT_HEAD';
  ELSE
    /* Coordinator de área 'Software' activo con MENOR carga */
    SELECT c.idPerson
      INTO v_assigned
    FROM identity.coordinator c
    WHERE c.area = 'Software' AND c.active = 1
    ORDER BY (
        SELECT COUNT(*)
        FROM resources.review_assignment ra
        WHERE ra.assignedToPersonId = c.idPerson
          AND ra.active = 1
      ) ASC,
      c.idPerson ASC
    LIMIT 1;

    SET v_role := 'COORDINATOR';
  END IF;

  IF v_assigned IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No reviewer available';
  END IF;

  /* Registrar asignación y actualizar estado del recurso */
  INSERT INTO resources.review_assignment
    (resourceId, assignedToPersonId, assignedRole, active)
  VALUES
    (p_resource_id, v_assigned, v_role, 1);

  UPDATE resources.resource
     SET status = 'UnderReview'
   WHERE idResource = p_resource_id;

  /* Devolver un pequeño resumen para el front */
  SELECT p_resource_id AS resourceId, v_assigned AS reviewerPersonId, v_role AS reviewerRole, v_module AS module;
END $$

DELIMITER ;
