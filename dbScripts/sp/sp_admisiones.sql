/* Cambiar delimitador para crear SP */
DELIMITER $$

/* ---------------------------------------------------------------------
   1) Listas para el formulario (carreras, campus, períodos activos)
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admissions.sp_form_enrollment_lists $$
CREATE PROCEDURE admissions.sp_form_enrollment_lists()
BEGIN
  /* Programs activos */
  SELECT
      p.idProgram,
      p.programCode,
      p.name              AS program,
      f.name              AS faculty,
      c.name              AS campus,
      p.modality,
      p.status
  FROM academic.program p
  JOIN academic.faculty f ON f.idFaculty = p.idFaculty
  JOIN academic.campus  c ON c.idCampus  = p.idCampus
  WHERE p.status = 'Active'
  ORDER BY f.name, p.name;

  /* Campus activos */
  SELECT
      c.idCampus,
      c.campusCode,
      c.name,
      c.address,
      c.phone,
      c.status
  FROM academic.campus c
  WHERE c.status = 'Active'
  ORDER BY c.name;

  /* Términos con estado Enrollment o InProgress */
  SELECT
      t.idTerm,
      CONCAT(t.year, '-', LPAD(t.number,2,'0')) AS termLabel,
      t.name,
      t.enrollmentStartDate,
      t.enrollmentEndDate,
      t.status
  FROM academic.academic_term t
  WHERE t.status IN ('Enrollment','InProgress')
  ORDER BY t.year DESC, t.number DESC;
END $$


/* ---------------------------------------------------------------------
   2) Resultado de admisión por número de identidad
   - Entradas: p_identityNumber (con o sin guiones)
   - Salidas (result sets):
     1) Resumen persona/aspirante/última solicitud
     2) Programas aplicados (principal/secundario)
     3) Mejor nota por tipo de examen
     4) Programas elegibles SEGÚN NOTAS (todos los activos)
     5) Programas elegibles DENTRO de los aplicados
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admissions.sp_admission_result_by_identity $$
CREATE PROCEDURE admissions.sp_admission_result_by_identity(IN p_identityNumber VARCHAR(20))
proc_end: BEGIN
  /* Normalizar identidad: quitar guiones y espacios */
  SET @id_norm := REPLACE(REPLACE(p_identityNumber, '-', ''), ' ', '');

  /* Variables locales */
  DECLARE v_idPerson   INT UNSIGNED;
  DECLARE v_idApplicant INT UNSIGNED;
  DECLARE v_idApplication INT UNSIGNED;

  /* Persona por identidad */
  SELECT per.idPerson
    INTO v_idPerson
  FROM identity.person per
  WHERE REPLACE(REPLACE(per.identityNumber,'-',''),' ','') = @id_norm
  LIMIT 1;

  /* Si no existe persona, regresar mensaje y terminar */
  IF v_idPerson IS NULL THEN
    SELECT 'NOT_FOUND' AS status, 'Identidad no registrada' AS message, @id_norm AS identityNumber;
    LEAVE proc_end;
  END IF;

  /* Applicant por persona */
  SELECT a.idApplicant
    INTO v_idApplicant
  FROM admissions.applicant a
  WHERE a.idPerson = v_idPerson
  LIMIT 1;

  /* Última solicitud por applicant (más reciente) */
  SELECT ap.idApplication
    INTO v_idApplication
  FROM admissions.admissionApplication ap
  WHERE ap.idApplicant = v_idApplicant
  ORDER BY ap.createdAt DESC, ap.idApplication DESC
  LIMIT 1;

  /* Resumen */
  SELECT
      per.idPerson,
      a.idApplicant,
      ap.idApplication,
      per.identityNumber,
      per.firstName,
      per.lastName,
      per.personalEmail,
      cam.name  AS campusApplication,
      CONCAT(te.year, '-', LPAD(te.number,2,'0')) AS termLabel,
      ap.status AS applicationStatus,
      ap.createdAt
  FROM identity.person per
  LEFT JOIN admissions.applicant a          ON a.idPerson = per.idPerson
  LEFT JOIN admissions.admissionApplication ap ON ap.idApplication = v_idApplication
  LEFT JOIN academic.campus cam             ON cam.idCampus = ap.idCampus
  LEFT JOIN academic.academic_term te       ON te.idTerm   = ap.idTerm
  WHERE per.idPerson = v_idPerson;

  /* Si no hay solicitud, devolver conjuntos vacíos y terminar */
  IF v_idApplication IS NULL THEN
    SELECT NULL WHERE FALSE;  /* Programas aplicados */
    SELECT NULL WHERE FALSE;  /* Notas por tipo */
    SELECT NULL WHERE FALSE;  /* Elegibles (todos) */
    SELECT NULL WHERE FALSE;  /* Elegibles (aplicados) */
    LEAVE proc_end;
  END IF;

  /* Programas aplicados */
  SELECT 'Primary' AS kind,
         p1.idProgram, p1.programCode, p1.name AS program, f1.name AS faculty
  FROM admissions.admissionApplication ap
  JOIN academic.program  p1 ON p1.idProgram  = ap.idPrimaryProgram
  JOIN academic.faculty  f1 ON f1.idFaculty  = p1.idFaculty
  WHERE ap.idApplication = v_idApplication
  UNION ALL
  SELECT 'Secondary' AS kind,
         p2.idProgram, p2.programCode, p2.name AS program, f2.name AS faculty
  FROM admissions.admissionApplication ap
  JOIN academic.program  p2 ON p2.idProgram  = ap.idSecondaryProgram
  JOIN academic.faculty  f2 ON f2.idFaculty  = p2.idFaculty
  WHERE ap.idApplication = v_idApplication
    AND ap.idSecondaryProgram IS NOT NULL;

  /* Mejor nota por tipo de examen (para la solicitud) */
  SELECT
      et.idExamType,
      et.name       AS examType,
      MAX(ex.score) AS bestScore,
      MAX(ex.status) AS lastStatus,
      MAX(ex.date)   AS lastDate
  FROM admissions.exam ex
  JOIN admissions.examType et ON et.idExamType = ex.idExamType
  WHERE ex.idApplication = v_idApplication
  GROUP BY et.idExamType, et.name
  ORDER BY et.idExamType;

  /* Elegibles según notas (todos los programas activos) */
  WITH best_scores AS (
    SELECT ex.idExamType, MAX(ex.score) AS score
    FROM admissions.exam ex
    WHERE ex.idApplication = v_idApplication
    GROUP BY ex.idExamType
  )
  SELECT
      p.idProgram, p.programCode, p.name AS program,
      f.name AS faculty, c.name AS campus
  FROM academic.program p
  JOIN academic.faculty f ON f.idFaculty = p.idFaculty
  JOIN academic.campus  c ON c.idCampus  = p.idCampus
  WHERE p.status = 'Active'
    AND NOT EXISTS (
          SELECT 1
          FROM admissions.programs_exams x
          LEFT JOIN best_scores bs ON bs.idExamType = x.idExamType
          WHERE x.idProgram = p.idProgram
            AND x.required = 1
            AND (bs.score IS NULL OR bs.score < x.minScore)
        )
  ORDER BY f.name, p.name;

  /* Elegibles dentro de los aplicados */
  WITH best_scores AS (
    SELECT ex.idExamType, MAX(ex.score) AS score
    FROM admissions.exam ex
    WHERE ex.idApplication = v_idApplication
    GROUP BY ex.idExamType
  ),
  applied AS (
    SELECT ap.idPrimaryProgram   AS idProgram FROM admissions.admissionApplication ap WHERE ap.idApplication=v_idApplication
    UNION
    SELECT ap.idSecondaryProgram AS idProgram FROM admissions.admissionApplication ap WHERE ap.idApplication=v_idApplication AND ap.idSecondaryProgram IS NOT NULL
  )
  SELECT
      p.idProgram, p.programCode, p.name AS program,
      f.name AS faculty, c.name AS campus
  FROM applied a
  JOIN academic.program p ON p.idProgram = a.idProgram
  JOIN academic.faculty f ON f.idFaculty = p.idFaculty
  JOIN academic.campus  c ON c.idCampus  = p.idCampus
  WHERE p.status = 'Active'
    AND NOT EXISTS (
          SELECT 1
          FROM admissions.programs_exams x
          LEFT JOIN best_scores bs ON bs.idExamType = x.idExamType
          WHERE x.idProgram = p.idProgram
            AND x.required = 1
            AND (bs.score IS NULL OR bs.score < x.minScore)
        )
  ORDER BY f.name, p.name;

END $$

/* Restaurar delimitador */
DELIMITER ;
