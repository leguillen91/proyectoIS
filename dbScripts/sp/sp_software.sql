DELIMITER $$

/* ---------------------------------------------
   resources.sp_sw_assign_reviewer_auto
   - Asigna Jefe de Depto de la carrera del recurso,
     si no hay, elige Coordinator(area='Software') con menos carga.
---------------------------------------------- */
DROP PROCEDURE IF EXISTS resources.sp_sw_assign_reviewer_auto $$
CREATE PROCEDURE resources.sp_sw_assign_reviewer_auto(IN p_id_meta INT UNSIGNED)
BEGIN
  DECLARE v_idProgram INT UNSIGNED;
  DECLARE v_assigned  INT UNSIGNED;
  DECLARE v_type      VARCHAR(20);

  /* Program del recurso (desde library_resource asociado a la meta) */
  SELECT lr.idProgram
    INTO v_idProgram
  FROM resources.software_resource_meta m
  JOIN resources.library_resource lr ON lr.idLibraryResource = m.idLibraryResource
  WHERE m.idMeta = p_id_meta;

  /* Intentar con DepartmentHead activo de ese Program */
  SELECT dh.idPerson
    INTO v_assigned
  FROM identity.departmentHead dh
  WHERE dh.idAcademicDepartment = v_idProgram
    AND dh.endDate IS NULL
  ORDER BY dh.startDate DESC
  LIMIT 1;

  IF v_assigned IS NOT NULL THEN
    SET v_type := 'DEPT_HEAD';
  ELSE
    /* Coordinator de área Software con menos revisiones */
    SELECT c.idPerson
      INTO v_assigned
    FROM identity.coordinator c
    WHERE c.area='Software' AND c.active=1
    ORDER BY (
       SELECT COUNT(*)
       FROM resources.software_resource_review r
       WHERE r.idMeta = p_id_meta AND r.assignedTo = c.idPerson
    ) ASC, c.idPerson ASC
    LIMIT 1;

    SET v_type := 'COORDINATOR';
  END IF;

  /* Registrar asignación y marcar meta como Pending */
  INSERT INTO resources.software_resource_review (idMeta, assignedTo, reviewerType, comment, status)
  VALUES (p_id_meta, v_assigned, v_type, 'Asignación automática', 'Pending');

  UPDATE resources.software_resource_meta
     SET status='Pending'
   WHERE idMeta = p_id_meta;
END $$

DELIMITER ;
