/* Cambiar delimitador para crear SP */
DELIMITER $$

/* ---------------------------------------------------------------------
   1) Listas para el formulario (carreras, campus, períodos activos)
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admissions.spFormEnrollmentLists $$
CREATE PROCEDURE admissions.spFormEnrollmentLists()
BEGIN
  /* Programs activos */
  SELECT
      p.id,
      p.programCode,
      p.name              AS program,
      f.name              AS faculty,
      c.name              AS campus,
      p.modality,
      p.status
  FROM academic.program p
  JOIN academic.faculty f ON f.id = p.facultyId
  JOIN academic.campus  c ON c.id = p.campusId
  WHERE p.status = 'Active'
  ORDER BY f.name, p.name;

  /* Campus activos */
  SELECT
      c.id,
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
      t.id,
      CONCAT(t.year, '-', LPAD(t.termNumber,2,'0')) AS termLabel,
      t.name,
      t.enrollmentStartDate,
      t.enrollmentEndDate,
      t.status
  FROM academic.academicTerm t
  WHERE t.status IN ('Enrollment','InProgress')
  ORDER BY t.year DESC, t.termNumber DESC;
END $$


/* ---------------------------------------------------------------------
   2) Resultado de admisión por número de identidad
   - Entradas: p_identityNumber (con o sin guiones)
   - Salidas (result sets):
     1) Resumen persona/aspirante/última solicitud
     2) Programas aplicados (principal/secundario)
     3) Mejor nota por tipo de examen
     4) Programas elegibles SEGÚN NOTAS
     5) Programas elegibles DENTRO de los aplicados
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admissions.spAdmissionResultByIdentity $$
CREATE PROCEDURE admissions.spAdmissionResultByIdentity(IN pIdentityNumber VARCHAR(20))
proc_end: BEGIN
  /* Normalizar identidad: quitar guiones y espacios */
  SET @id_norm := REPLACE(REPLACE(pIdentityNumber, '-', ''), ' ', '');

  /* Variables locales */
  DECLARE vIdUser        INT UNSIGNED;
  DECLARE vIdApplicant   INT UNSIGNED;
  DECLARE vIdApplication INT UNSIGNED;

  /* Persona por identidad */
  SELECT u.id
    INTO vIdUser
  FROM identity.users u
  WHERE REPLACE(REPLACE(u.nationalId,'-',''),' ','') = @id_norm
  LIMIT 1;

  /* Si no existe persona, regresar mensaje y terminar */
  IF vIdUser IS NULL THEN
    SELECT 'NOT_FOUND' AS status, 'Identidad no registrada' AS message, @id_norm AS identityNumber;
    LEAVE proc_end;
  END IF;

  /* Applicant por persona */
  SELECT a.id
    INTO vIdApplicant
  FROM admissions.applicant a
  WHERE a.userId = vIdUser
  LIMIT 1;

  /* Última solicitud por applicant (más reciente) */
  SELECT ap.id
    INTO vIdApplication
  FROM admissions.admissionApplication ap
  WHERE ap.applicantId = vIdApplicant
  ORDER BY ap.createdAt DESC, ap.id DESC
  LIMIT 1;

  /* Resumen */
  SELECT
      u.id           AS idUser,
      a.id           AS idApplicant,
      ap.id          AS idApplication,
      u.nationalId   AS identityNumber,
      u.firstName,
      u.lastName,
      u.personalEmail,
      cam.name  AS campusApplication,
      CONCAT(te.year, '-', LPAD(te.termNumber,2,'0')) AS termLabel,
      ap.status AS applicationStatus,
      ap.createdAt
  FROM identity.users u
  LEFT JOIN admissions.applicant a           ON a.userId = u.id
  LEFT JOIN admissions.admissionApplication ap ON ap.id   = vIdApplication
  LEFT JOIN academic.campus cam              ON cam.id    = ap.campusId
  LEFT JOIN academic.academicTerm te         ON te.id     = ap.termId
  WHERE u.id = vIdUser;

  /* Si no hay solicitud, devolver conjuntos vacíos y terminar */
  IF vIdApplication IS NULL THEN
    SELECT NULL WHERE FALSE;  /* Programas aplicados */
    SELECT NULL WHERE FALSE;  /* Notas por tipo */
    SELECT NULL WHERE FALSE;  /* Elegibles (todos) */
    SELECT NULL WHERE FALSE;  /* Elegibles (aplicados) */
    LEAVE proc_end;
  END IF;

  /* Programas aplicados */
  SELECT 'Primary' AS kind,
         p1.id, p1.programCode, p1.name AS program, f1.name AS faculty
  FROM admissions.admissionApplication ap
  JOIN academic.program  p1 ON p1.id  = ap.primaryProgramId
  JOIN academic.faculty  f1 ON f1.id  = p1.facultyId
  WHERE ap.id = vIdApplication
  UNION ALL
  SELECT 'Secondary' AS kind,
         p2.id, p2.programCode, p2.name AS program, f2.name AS faculty
  FROM admissions.admissionApplication ap
  JOIN academic.program  p2 ON p2.id  = ap.secondaryProgramId
  JOIN academic.faculty  f2 ON f2.id  = p2.facultyId
  WHERE ap.id = vIdApplication
    AND ap.secondaryProgramId IS NOT NULL;

  /* Mejor nota por tipo de examen (para la solicitud) */
  SELECT
      et.id,
      et.name       AS examType,
      MAX(ex.score) AS bestScore,
      MAX(ex.status) AS lastStatus,
      MAX(ex.date)   AS lastDate
  FROM admissions.exam ex
  JOIN admissions.examType et ON et.id = ex.examTypeId
  WHERE ex.applicationId = vIdApplication
  GROUP BY et.id, et.name
  ORDER BY et.id;

  /* Elegibles según notas (todos los programas activos) */
  WITH best_scores AS (
    SELECT ex.examTypeId, MAX(ex.score) AS score
    FROM admissions.exam ex
    WHERE ex.applicationId = vIdApplication
    GROUP BY ex.examTypeId
  )
  SELECT
      p.id, p.programCode, p.name AS program,
      f.name AS faculty, c.name AS campus
  FROM academic.program p
  JOIN academic.faculty f ON f.id = p.facultyId
  JOIN academic.campus  c ON c.id = p.campusId
  WHERE p.status = 'Active'
    AND NOT EXISTS (
          SELECT 1
          FROM admissions.programExamRequirement x
          LEFT JOIN best_scores bs ON bs.examTypeId = x.examTypeId
          WHERE x.programId = p.id
            AND x.required = 1
            AND (bs.score IS NULL OR bs.score < x.minimumScore)
        )
  ORDER BY f.name, p.name;

  /* Elegibles dentro de los aplicados */
  WITH best_scores AS (
    SELECT ex.examTypeId, MAX(ex.score) AS score
    FROM admissions.exam ex
    WHERE ex.applicationId = vIdApplication
    GROUP BY ex.examTypeId
  ),
  applied AS (
    SELECT ap.primaryProgramId   AS programId FROM admissions.admissionApplication ap WHERE ap.id = vIdApplication
    UNION
    SELECT ap.secondaryProgramId AS programId FROM admissions.admissionApplication ap WHERE ap.id = vIdApplication AND ap.secondaryProgramId IS NOT NULL
  )
  SELECT
      p.id, p.programCode, p.name AS program,
      f.name AS faculty, c.name AS campus
  FROM applied a
  JOIN academic.program p ON p.id = a.programId
  JOIN academic.faculty f ON f.id = p.facultyId
  JOIN academic.campus  c ON c.id = p.campusId
  WHERE p.status = 'Active'
    AND NOT EXISTS (
          SELECT 1
          FROM admissions.programExamRequirement x
          LEFT JOIN best_scores bs ON bs.examTypeId = x.examTypeId
          WHERE x.programId = p.id
            AND x.required = 1
            AND (bs.score IS NULL OR bs.score < x.minimumScore)
        )
  ORDER BY f.name, p.name;

END $$

/* Restaurar delimitador */
DELIMITER ;
