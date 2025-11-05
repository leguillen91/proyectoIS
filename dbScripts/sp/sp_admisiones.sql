

/* Cambiar delimitador para crear SP */
DELIMITER $$

/* ---------------------------------------------------------------------
   1) Listas para el formulario
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admisiones.sp_form_listas_inscripcion $$
CREATE PROCEDURE admisiones.sp_form_listas_inscripcion()
BEGIN
  /* Carreras activas */
  SELECT
      c.idCarrera,
      c.codigoCarrera,
      c.nombre           AS carrera,
      f.nombre           AS facultad,
      ca.nombre          AS campus,
      c.modalidad,
      c.estado
  FROM academico.carrera c
  JOIN academico.facultad f ON f.idFacultad = c.idFacultad
  JOIN academico.campus  ca ON ca.idCampus  = c.idCampus
  WHERE c.estado = 'Activo'
  ORDER BY f.nombre, c.nombre;

  /* Centros regionales activos */
  SELECT
      ca.idCampus,
      ca.codigoCampus,
      ca.nombre,
      ca.direccion,
      ca.telefono,
      ca.estado
  FROM academico.campus ca
  WHERE ca.estado = 'Activo'
  ORDER BY ca.nombre;

  /* Períodos activos (Matricula o EnCurso) — Útil si el front pide mostrarlos */
  SELECT
      p.idPeriodo,
      CONCAT(p.anio, '-', LPAD(p.numero,2,'0')) AS periodo,
      p.nombre,
      p.fechaInicioMatricula,
      p.fechaFinMatricula,
      p.estado
  FROM academico.periodoAcademico p
  WHERE p.estado IN ('Matricula','EnCurso')
  ORDER BY p.anio DESC, p.numero DESC;
END $$


/* ---------------------------------------------------------------------
   2) Resultados de admisión por número de identidad
   Entradas:
     - p_numeroIdentidad — acepta con o sin guiones
   Salidas:
     -Resumen persona/aspirante/última solicitud
     -Carreras a las que aplicó (principal/secundaria)
     -Exámenes del aspirante (por solicitud; mejor nota por tipo)
     -Carreras elegibles SEGÚN NOTAS (todas las carreras activas)
     -Carreras elegibles DENTRO de las que aplicó (filtro aplicado)
   --------------------------------------------------------------------- */
DROP PROCEDURE IF EXISTS admisiones.sp_resultado_admision_por_identidad $$
CREATE PROCEDURE admisiones.sp_resultado_admision_por_identidad(IN p_numeroIdentidad VARCHAR(20))
BEGIN
  SET @id_norm := REPLACE(REPLACE(p_numeroIdentidad, '-', ''), ' ', '');

  DECLARE v_idPersona   INT UNSIGNED;
  DECLARE v_idAspirante INT UNSIGNED;
  DECLARE v_idSolicitud INT UNSIGNED;

  /* Persona por identidad */
  SELECT p.idPersona
    INTO v_idPersona
  FROM identidad.persona p
  WHERE REPLACE(REPLACE(p.numeroIdentidad,'-',''),' ','') = @id_norm
  LIMIT 1;

  /* Si no existe persona, regresar mensaje y terminar */
  IF v_idPersona IS NULL THEN
    SELECT 'NOT_FOUND' AS status, 'Identidad no registrada' AS message, @id_norm AS identidad;
    LEAVE proc_end;
  END IF;

  /* Aspirante por persona */
  SELECT a.idAspirante
    INTO v_idAspirante
  FROM admisiones.aspirante a
  WHERE a.idPersona = v_idPersona
  LIMIT 1;

  /* Última solicitud por aspirante (la más reciente) */
  SELECT sa.idSolicitud
    INTO v_idSolicitud
  FROM admisiones.solicitudAdmision sa
  WHERE sa.idAspirante = v_idAspirante
  ORDER BY sa.fechaCreacion DESC, sa.idSolicitud DESC
  LIMIT 1;

  /* Resumen persona/aspirante/última solicitud */
  SELECT
      p.idPersona,
      a.idAspirante,
      s.idSolicitud,
      p.numeroIdentidad,
      p.nombres,
      p.apellidos,
      p.correoPersonal,
      cam.nombre  AS campusSolicitud,
      CONCAT(pe.anio, '-', LPAD(pe.numero,2,'0')) AS periodo,
      s.estado    AS estadoSolicitud,
      s.fechaCreacion
  FROM identidad.persona p
  LEFT JOIN admisiones.aspirante a ON a.idPersona = p.idPersona
  LEFT JOIN admisiones.solicitudAdmision s ON s.idSolicitud = v_idSolicitud
  LEFT JOIN academico.campus cam ON cam.idCampus = s.idCampus
  LEFT JOIN academico.periodoAcademico pe ON pe.idPeriodo = s.idPeriodo
  WHERE p.idPersona = v_idPersona;

  /* Si no hay solicitud, devolver vacíos y finalizar */
  IF v_idSolicitud IS NULL THEN
    /* Carreras a las que aplicó */
    SELECT NULL WHERE FALSE;
    /* Exámenes del aspirante */
    SELECT NULL WHERE FALSE;
    /* Carreras elegibles SEGÚN NOTAS*/
    SELECT NULL WHERE FALSE;
    /* Carreras elegibles DENTRO de las que aplicó */
    SELECT NULL WHERE FALSE;
    LEAVE proc_end;
  END IF;

  /* Carreras a las que aplicó (principal y secundaria) */
  SELECT 'Principal' AS tipo,
         c1.idCarrera,
         c1.codigoCarrera,
         c1.nombre AS carrera,
         f1.nombre AS facultad
  FROM admisiones.solicitudAdmision s
  JOIN academico.carrera c1 ON c1.idCarrera = s.idCarreraPrincipal
  JOIN academico.facultad f1 ON f1.idFacultad = c1.idFacultad
  WHERE s.idSolicitud = v_idSolicitud
  UNION ALL
  SELECT 'Secundaria' AS tipo,
         c2.idCarrera,
         c2.codigoCarrera,
         c2.nombre AS carrera,
         f2.nombre AS facultad
  FROM admisiones.solicitudAdmision s
  JOIN academico.carrera c2 ON c2.idCarrera = s.idCarreraSecundaria
  JOIN academico.facultad f2 ON f2.idFacultad = c2.idFacultad
  WHERE s.idSolicitud = v_idSolicitud
    AND s.idCarreraSecundaria IS NOT NULL;

  /* Mejor nota por tipo de examen */
  /*Toma la mayor nota */
  SELECT
      te.idTipoExamen,
      te.nombre      AS tipoExamen,
      MAX(e.nota)    AS mejorNota,
      MAX(e.estado)  AS estadoUltimo,
      MAX(e.fecha)   AS fechaUltima
  FROM admisiones.examen e
  JOIN admisiones.tipoExamen te ON te.idTipoExamen = e.idTipoExamen
  WHERE e.idSolicitud = v_idSolicitud
  GROUP BY te.idTipoExamen, te.nombre
  ORDER BY te.idTipoExamen;

  /*Carreras elegibles según notas */
  /* Usa la mejor nota por tipo  */
  WITH mejores_notas AS (
    SELECT e.idTipoExamen, MAX(e.nota) AS nota
    FROM admisiones.examen e
    WHERE e.idSolicitud = v_idSolicitud
    GROUP BY e.idTipoExamen
  )
  SELECT
      c.idCarrera,
      c.codigoCarrera,
      c.nombre AS carrera,
      f.nombre AS facultad,
      ca.nombre AS campus
  FROM academico.carrera c
  JOIN academico.facultad f ON f.idFacultad = c.idFacultad
  JOIN academico.campus  ca ON ca.idCampus  = c.idCampus
  WHERE c.estado = 'Activo'
    AND NOT EXISTS (
          SELECT 1
          FROM admisiones.carrerasXexamen x
          LEFT JOIN mejores_notas mn
                 ON mn.idTipoExamen = x.idTipoExamen
          WHERE x.idCarrera = c.idCarrera
            AND x.obligatorio = 1
            AND (mn.nota IS NULL OR mn.nota < x.puntajeMinimo)
        )
  ORDER BY f.nombre, c.nombre;

  /*Elegibles dentro de las que aplicó (principal/secundaria) */
  WITH mejores_notas AS (
    SELECT e.idTipoExamen, MAX(e.nota) AS nota
    FROM admisiones.examen e
    WHERE e.idSolicitud = v_idSolicitud
    GROUP BY e.idTipoExamen
  ),
  aplicadas AS (
    SELECT s.idCarreraPrincipal AS idCarrera FROM admisiones.solicitudAdmision s WHERE s.idSolicitud = v_idSolicitud
    UNION
    SELECT s.idCarreraSecundaria FROM admisiones.solicitudAdmision s WHERE s.idSolicitud = v_idSolicitud AND s.idCarreraSecundaria IS NOT NULL
  )
  SELECT
      c.idCarrera,
      c.codigoCarrera,
      c.nombre AS carrera,
      f.nombre AS facultad,
      ca.nombre AS campus
  FROM aplicadas a
  JOIN academico.carrera c ON c.idCarrera = a.idCarrera
  JOIN academico.facultad f ON f.idFacultad = c.idFacultad
  JOIN academico.campus  ca ON ca.idCampus  = c.idCampus
  WHERE c.estado = 'Activo'
    AND NOT EXISTS (
          SELECT 1
          FROM admisiones.carrerasXexamen x
          LEFT JOIN mejores_notas mn
                 ON mn.idTipoExamen = x.idTipoExamen
          WHERE x.idCarrera = c.idCarrera
            AND x.obligatorio = 1
            AND (mn.nota IS NULL OR mn.nota < x.puntajeMinimo)
        )
  ORDER BY f.nombre, c.nombre;

  proc_end: BEGIN END;
END $$

DELIMITER ;
