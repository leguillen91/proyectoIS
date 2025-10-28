/* =======================================================
  JUSTES TEMPORALES
======================================================= */
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';







/* =======================================================
   ESQUEMAS
======================================================= */
CREATE SCHEMA IF NOT EXISTS identidad   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS academico   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS matricula   DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS recursos    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE SCHEMA IF NOT EXISTS admisiones  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;







/* =======================================================
   IDENTIDAD
======================================================= */

USE identidad;

CREATE TABLE persona (
  idPersona           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  numeroIdentidad     VARCHAR(20)  NOT NULL,
  nombres             VARCHAR(60)  NOT NULL,
  apellidos           VARCHAR(60)  NOT NULL,
  correoInstitucional VARCHAR(100) NULL,
  correoPersonal      VARCHAR(120) NULL,
  telefonoContacto    VARCHAR(20)  NULL,
  direccion           VARCHAR(255) NULL,
  fotoPerfil          LONGBLOB     NULL,
  fechaRegistro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estadoCuenta        ENUM('Activo','Inactivo','Suspendido','Eliminado') NOT NULL DEFAULT 'Activo',
  UNIQUE KEY uq_persona_identidad (numeroIdentidad)
) ENGINE=InnoDB;

CREATE TABLE credenciales (
  idCredenciales      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idPersona           INT UNSIGNED NOT NULL,
  usuario             VARCHAR(45)  NOT NULL,
  rol                 ENUM('ADMIN','ESTUDIANTE','DOCENTE','COORDINADOR','JEFE_DEPTO','ASPIRANTE') 
                       NOT NULL DEFAULT 'ASPIRANTE',
  claveHash           VARCHAR(255) NOT NULL,
  ultimoLogin         DATETIME NULL,
  intentosLogin       INT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado           TINYINT(1) NOT NULL DEFAULT 0,
  tokenRecuperacion   VARCHAR(100) NULL,
  fechaCreacion       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  UNIQUE KEY uq_credenciales_usuario (usuario),
  UNIQUE KEY uq_credenciales_persona (idPersona),
  KEY fk_credenciales_persona (idPersona),

  CONSTRAINT fk_credenciales_persona
    FOREIGN KEY (idPersona) REFERENCES persona(idPersona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE perfil (
  idPerfil      TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clavePerfil   VARCHAR(45)  NOT NULL,
  descripcion   VARCHAR(255) NULL,
  UNIQUE KEY uq_perfil_clave (clavePerfil)
) ENGINE=InnoDB;

CREATE TABLE credencialesXperfil (
  idCredenciales INT UNSIGNED NOT NULL,
  idPerfil       TINYINT UNSIGNED NOT NULL,
  fechaAsignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idCredenciales,idPerfil),
  KEY fk_cxp_perfil (idPerfil),
  CONSTRAINT fk_cxp_credenciales FOREIGN KEY (idCredenciales) REFERENCES credenciales(idCredenciales),
  CONSTRAINT fk_cxp_perfil        FOREIGN KEY (idPerfil)       REFERENCES perfil(idPerfil)
) ENGINE=InnoDB;

CREATE TABLE alumno (
  idPersona           INT UNSIGNED PRIMARY KEY,
  numeroCuenta        VARCHAR(11)  NOT NULL,
  preparacionAcademica ENUM('Pregrado','Posgrado') NOT NULL,
  idCarrera           INT UNSIGNED NULL,        /* FK academico.carrera */
  idCarreraSecundaria INT UNSIGNED NULL,        /* FK academico.carrera */
  indiceAcademico     DECIMAL(4,2)  NULL,
  fechaIngreso        DATE          NULL,
  KEY fk_alumno_carrera1 (idCarrera),
  KEY fk_alumno_carrera2 (idCarreraSecundaria),
  CONSTRAINT fk_alumno_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona)
) ENGINE=InnoDB;

CREATE TABLE docente (
  idPersona               INT UNSIGNED PRIMARY KEY,
  numeroEmpleado          VARCHAR(20) NULL,
  idDepartamentoAcademico INT UNSIGNED NULL,   /* FK academico.carrera (o depto si lo crea después) */
  fechaContrato           DATE NULL,
  shift                   ENUM('TiempoCompleto','MedioTiempo') NULL,
  cubiculo                VARCHAR(45) NULL,
  CONSTRAINT fk_docente_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona)
) ENGINE=InnoDB;

CREATE TABLE jefeDepartamento (
  idPersona               INT UNSIGNED PRIMARY KEY,
  idDepartamentoAcademico INT UNSIGNED NULL,   /* FK academico.carrera (o depto) */
  fechaInicioCargo        DATE NOT NULL,
  fechaFinCargo           DATE NULL,
  razonFinalizacion       VARCHAR(255) NULL,
  CONSTRAINT fk_jefe_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona)
) ENGINE=InnoDB;

CREATE TABLE coordinador (
  idPersona INT UNSIGNED PRIMARY KEY,
  area      ENUM('Software','Musica','BibliotecaVirtual','Admisiones','Matricula') NOT NULL,
  activo    TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_coord_persona FOREIGN KEY (idPersona) REFERENCES persona(idPersona)
) ENGINE=InnoDB;











/* =======================================================
   ACADÉMICO 
======================================================= */

USE academico;

CREATE TABLE facultad (
  idFacultad     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigoFacultad VARCHAR(10) NOT NULL,
  nombre         VARCHAR(80) NOT NULL,
  estado         VARCHAR(45) NOT NULL DEFAULT 'Activo',
  UNIQUE KEY uq_facultad_codigo (codigoFacultad)
) ENGINE=InnoDB;

CREATE TABLE campus (
  idCampus     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigoCampus VARCHAR(10) NOT NULL,
  nombre       VARCHAR(80) NOT NULL,
  direccion    VARCHAR(255) NULL,
  telefono     VARCHAR(20)  NULL,
  estado       VARCHAR(45)  NOT NULL DEFAULT 'Activo',
  UNIQUE KEY uq_campus_codigo (codigoCampus)
) ENGINE=InnoDB;

CREATE TABLE edificio (
  idEdificio INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idCampus   INT UNSIGNED NOT NULL,
  nombre     VARCHAR(80) NOT NULL,
  estado     VARCHAR(45) NOT NULL DEFAULT 'Activo',
  KEY fk_edificio_campus (idCampus),
  CONSTRAINT fk_edificio_campus FOREIGN KEY (idCampus) REFERENCES campus(idCampus)
) ENGINE=InnoDB;

CREATE TABLE aula (
  idAula     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idEdificio INT UNSIGNED NOT NULL,
  numeroAula VARCHAR(10)  NOT NULL,
  capacidad  INT UNSIGNED NOT NULL,
  tipoAula   ENUM('Salon','Laboratorio') NOT NULL,
  estado     VARCHAR(45) NOT NULL DEFAULT 'Activo',
  KEY fk_aula_edificio (idEdificio),
  CONSTRAINT fk_aula_edificio FOREIGN KEY (idEdificio) REFERENCES edificio(idEdificio)
) ENGINE=InnoDB;

CREATE TABLE carrera (
  idCarrera        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigoCarrera    VARCHAR(10) NOT NULL,
  nombre           VARCHAR(100) NOT NULL,
  idFacultad       INT UNSIGNED NOT NULL,
  uvTotales        INT UNSIGNED NULL,
  duracionSemestre INT UNSIGNED NULL,
  estado           VARCHAR(45) NOT NULL DEFAULT 'Activo',
  idCampus         INT UNSIGNED NOT NULL,
  modalidad        ENUM('Presencial','Virtual') NOT NULL,
  UNIQUE KEY uq_carrera_codigo (codigoCarrera),
  KEY fk_carrera_facultad (idFacultad),
  KEY fk_carrera_campus (idCampus),
  CONSTRAINT fk_carrera_facultad FOREIGN KEY (idFacultad) REFERENCES facultad(idFacultad),
  CONSTRAINT fk_carrera_campus   FOREIGN KEY (idCampus)   REFERENCES campus(idCampus)
) ENGINE=InnoDB;

CREATE TABLE asignatura (
  idAsignatura    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigoAsignatura VARCHAR(15) NOT NULL,
  nombre          VARCHAR(100) NOT NULL,
  uv              TINYINT UNSIGNED NOT NULL,  /* UV 1–5 aprox; tope 25/periodo */
  categoria       ENUM('Obligatoria','Electiva','Selectiva') NOT NULL,
  estado          VARCHAR(45) NOT NULL DEFAULT 'Activa',
  UNIQUE KEY uq_asignatura_codigo (codigoAsignatura)
) ENGINE=InnoDB;

CREATE TABLE prerequisito (
  idAsignatura  INT UNSIGNED NOT NULL,
  idPrerequisito INT UNSIGNED NOT NULL,
  PRIMARY KEY (idAsignatura,idPrerequisito),
  KEY fk_pre_req (idPrerequisito),
  CONSTRAINT fk_pre_asig   FOREIGN KEY (idAsignatura)   REFERENCES asignatura(idAsignatura),
  CONSTRAINT fk_pre_asig_2 FOREIGN KEY (idPrerequisito) REFERENCES asignatura(idAsignatura)
) ENGINE=InnoDB;

CREATE TABLE planCurricular (
  idCarrera    INT UNSIGNED NOT NULL,
  idAsignatura INT UNSIGNED NOT NULL,
  semestre     TINYINT UNSIGNED NOT NULL,
  tipo         ENUM('Obligatorio','Electivo') NOT NULL,
  PRIMARY KEY (idCarrera,idAsignatura),
  KEY fk_plan_asig (idAsignatura),
  CONSTRAINT fk_plan_carrera FOREIGN KEY (idCarrera) REFERENCES carrera(idCarrera),
  CONSTRAINT fk_plan_asig    FOREIGN KEY (idAsignatura) REFERENCES asignatura(idAsignatura)
) ENGINE=InnoDB;

CREATE TABLE periodoAcademico (
  idPeriodo            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  anio                 SMALLINT UNSIGNED NOT NULL,
  numero               TINYINT  UNSIGNED NOT NULL,  /* 1..3 (excepto med/odonto) */
  nombre               VARCHAR(45) NULL,
  fechaInicio          DATE NOT NULL,
  fechaFin             DATE NOT NULL,
  fechaInicioMatricula DATE NOT NULL,
  fechaFinMatricula    DATE NOT NULL,
  fechaInicioClases    DATE NOT NULL,
  fechaFinClases       DATE NOT NULL,
  estado               ENUM('Matricula','EnCurso','Finalizado') NOT NULL
) ENGINE=InnoDB;

/* Back-links de identidad a académico (declarados ahora que existen) */
USE identidad;
ALTER TABLE alumno
  ADD CONSTRAINT fk_alumno_carrera1 FOREIGN KEY (idCarrera)           REFERENCES academico.carrera(idCarrera),
  ADD CONSTRAINT fk_alumno_carrera2 FOREIGN KEY (idCarreraSecundaria) REFERENCES academico.carrera(idCarrera);

ALTER TABLE docente
  ADD CONSTRAINT fk_docente_depto FOREIGN KEY (idDepartamentoAcademico) REFERENCES academico.carrera(idCarrera);

ALTER TABLE jefeDepartamento
  ADD CONSTRAINT fk_jefe_depto FOREIGN KEY (idDepartamentoAcademico) REFERENCES academico.carrera(idCarrera);










/* =======================================================
   MATRÍCULA
======================================================= */

USE matricula;

CREATE TABLE seccion (
  idSeccion      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idAsignatura   INT UNSIGNED NOT NULL,  /* FK academico.asignatura */
  idPeriodo      INT UNSIGNED NOT NULL,  /* FK academico.periodoAcademico */
  idDocente      INT UNSIGNED NOT NULL,  /* FK identidad.docente(idPersona) */
  idAula         INT UNSIGNED NOT NULL,  /* FK academico.aula */
  codigoSeccion  VARCHAR(20) NOT NULL,
  cupoMaximo     SMALLINT UNSIGNED NOT NULL,
  modalidad      ENUM('Presencial','Virtual') NOT NULL,
  estado         ENUM('Activa','Cerrada','Cancelada') NOT NULL,
  fechaCreacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fk_sec_asig (idAsignatura),
  KEY fk_sec_per  (idPeriodo),
  KEY fk_sec_doc  (idDocente),
  KEY fk_sec_aula (idAula),
  CONSTRAINT fk_sec_asig FOREIGN KEY (idAsignatura) REFERENCES academico.asignatura(idAsignatura),
  CONSTRAINT fk_sec_per  FOREIGN KEY (idPeriodo)    REFERENCES academico.periodoAcademico(idPeriodo),
  CONSTRAINT fk_sec_doc  FOREIGN KEY (idDocente)    REFERENCES identidad.docente(idPersona),
  CONSTRAINT fk_sec_aula FOREIGN KEY (idAula)       REFERENCES academico.aula(idAula)
) ENGINE=InnoDB;

CREATE TABLE horarioSeccion (
  idHorario  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idSeccion  INT UNSIGNED NOT NULL,
  dias       VARCHAR(45)  NOT NULL,     /* p.ej. "LU-MI" */
  horaInicio TIME NOT NULL,
  horaFin    TIME NOT NULL,
  idAula     INT UNSIGNED NOT NULL,
  KEY fk_hs_seccion (idSeccion),
  KEY fk_hs_aula    (idAula),
  CONSTRAINT fk_hs_seccion FOREIGN KEY (idSeccion) REFERENCES seccion(idSeccion),
  CONSTRAINT fk_hs_aula    FOREIGN KEY (idAula)    REFERENCES academico.aula(idAula)
) ENGINE=InnoDB;

CREATE TABLE listaEspera (
  idListaEspera INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idEstudiante  INT UNSIGNED NOT NULL,  /* FK identidad.alumno(idPersona) */
  idSeccion     INT UNSIGNED NOT NULL,
  fechaSolicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  posicion      INT UNSIGNED NOT NULL,
  prioridad     TINYINT UNSIGNED NULL,
  atendida      TINYINT(1) NOT NULL DEFAULT 0,
  KEY fk_le_est (idEstudiante),
  KEY fk_le_sec (idSeccion),
  CONSTRAINT fk_le_est FOREIGN KEY (idEstudiante) REFERENCES identidad.alumno(idPersona),
  CONSTRAINT fk_le_sec FOREIGN KEY (idSeccion)    REFERENCES seccion(idSeccion)
) ENGINE=InnoDB;

CREATE TABLE pago (
  idPago      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idEstudiante INT UNSIGNED NOT NULL,  /* FK identidad.alumno */
  idPeriodo   INT UNSIGNED NOT NULL,   /* FK academico.periodoAcademico */
  concepto    ENUM('Matricula','Reposicion','Otro') NOT NULL,
  monto       DECIMAL(10,2) NOT NULL,
  metodoPago  ENUM('Efectivo','Tarjeta') NOT NULL,
  estado      ENUM('Registrado','Confirmado','Rechazado','Anulado') NOT NULL,
  fechaPago   DATETIME NOT NULL,
  KEY fk_pago_est (idEstudiante),
  KEY fk_pago_per (idPeriodo),
  CONSTRAINT fk_pago_est FOREIGN KEY (idEstudiante) REFERENCES identidad.alumno(idPersona),
  CONSTRAINT fk_pago_per FOREIGN KEY (idPeriodo)    REFERENCES academico.periodoAcademico(idPeriodo)
) ENGINE=InnoDB;

CREATE TABLE matricula_estudiante (
  idMatricula   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idEstudiante  INT UNSIGNED NOT NULL, /* FK identidad.alumno */
  idSeccion     INT UNSIGNED NOT NULL, /* FK matricula.seccion */
  fechaMatricula DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado        ENUM('Activa','Retirada','Cancelada') NOT NULL,
  notaFinal     DECIMAL(5,2) NULL,
  idPago        INT UNSIGNED NOT NULL,
  KEY fk_m_est (idEstudiante),
  KEY fk_m_sec (idSeccion),
  KEY fk_m_pago (idPago),
  CONSTRAINT fk_m_est  FOREIGN KEY (idEstudiante) REFERENCES identidad.alumno(idPersona),
  CONSTRAINT fk_m_sec  FOREIGN KEY (idSeccion)    REFERENCES seccion(idSeccion),
  CONSTRAINT fk_m_pago FOREIGN KEY (idPago)       REFERENCES pago(idPago)
) ENGINE=InnoDB;

CREATE TABLE solicitudEstudiante (
  idSolicitud       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idEstudiante      INT UNSIGNED NOT NULL, /* identidad.alumno */
  idCarreraActual   INT UNSIGNED NULL,     /* academico.carrera */
  idCarreraSolicitada INT UNSIGNED NULL,   /* academico.carrera */
  idCentroActual    INT UNSIGNED NULL,     /* academico.campus */
  idCentroSolicitado INT UNSIGNED NULL,    /* academico.campus */
  idPeriodo         INT UNSIGNED NOT NULL, /* academico.periodoAcademico */
  tipoSolicitud     ENUM('CambioCarrera','CancelacionExcepcional','Reposicion','CambioCentro') NOT NULL,
  justificacion     VARCHAR(255) NULL,
  pdfJustificacion  LONGBLOB NULL,
  estado            ENUM('Revision','Aprobada','Rechazada','Anulada') NOT NULL,
  coordinadorAsignado INT UNSIGNED NULL,   /* identidad.coordinador(idPersona) */
  aprobadoPor       INT UNSIGNED NULL,     /* identidad.jefeDepartamento(idPersona) */
  fechaSolicitud    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaAprobacion   DATETIME NULL,
  KEY fk_sol_est (idEstudiante),
  KEY fk_sol_per (idPeriodo),
  CONSTRAINT fk_sol_est FOREIGN KEY (idEstudiante) REFERENCES identidad.alumno(idPersona),
  CONSTRAINT fk_sol_per FOREIGN KEY (idPeriodo)    REFERENCES academico.periodoAcademico(idPeriodo),
  CONSTRAINT fk_sol_carr1 FOREIGN KEY (idCarreraActual)     REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_sol_carr2 FOREIGN KEY (idCarreraSolicitada) REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_sol_camp1 FOREIGN KEY (idCentroActual)      REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_sol_camp2 FOREIGN KEY (idCentroSolicitado)  REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_sol_coord FOREIGN KEY (coordinadorAsignado) REFERENCES identidad.coordinador(idPersona),
  CONSTRAINT fk_sol_aprob FOREIGN KEY (aprobadoPor)         REFERENCES identidad.jefeDepartamento(idPersona)
) ENGINE=InnoDB;

CREATE TABLE solicitudSeccion (
  idSolicitudSeccion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idSolicitud  INT UNSIGNED NOT NULL, /* FK matricula.solicitudEstudiante */
  idSeccion    INT UNSIGNED NOT NULL, /* FK matricula.seccion */
  motivo       VARCHAR(255) NULL,
  estado       ENUM('Pendiente','Aprobado','Rechazado') NOT NULL,
  comentario   VARCHAR(255) NULL,
  aprobadoPor  INT UNSIGNED NULL,     /* identidad.jefeDepartamento */
  fechaRevision DATETIME NULL,
  KEY fk_ss_sol (idSolicitud),
  KEY fk_ss_sec (idSeccion),
  CONSTRAINT fk_ss_sol FOREIGN KEY (idSolicitud) REFERENCES solicitudEstudiante(idSolicitud),
  CONSTRAINT fk_ss_sec FOREIGN KEY (idSeccion)   REFERENCES seccion(idSeccion),
  CONSTRAINT fk_ss_apr FOREIGN KEY (aprobadoPor) REFERENCES identidad.jefeDepartamento(idPersona)
) ENGINE=InnoDB;






/* =======================================================
   (Biblioteca + Música)
======================================================= */

USE recursos;

CREATE TABLE tags (
  id_tags    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(60) NOT NULL,
  descripcion VARCHAR(255) NULL,
  UNIQUE KEY uq_tag_nombre (nombre)
) ENGINE=InnoDB;

/* Biblioteca */
CREATE TABLE recurso_biblioteca (
  id_recurso_biblioteca INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_asignatura         INT UNSIGNED NULL, /* academico.asignatura */
  id_departamento       INT UNSIGNED NULL, /* academico.carrera */
  titulo                VARCHAR(100) NOT NULL,
  autor                 VARCHAR(100) NOT NULL,
  anio_publicacion      YEAR NULL,
  descripcion           VARCHAR(255) NULL,
  tipo_recurso          VARCHAR(45) NOT NULL,  /* Libro, Artículo, etc. */
  archivo_pdf           LONGBLOB NULL,
  tamanio_bytes         INT UNSIGNED NULL,
  fecha_subida          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  permisos              VARCHAR(45) NULL, /* visible/descargable/streaming (si se usa) */
  KEY fk_rb_asig (id_asignatura),
  KEY fk_rb_depto (id_departamento),
  CONSTRAINT fk_rb_asig  FOREIGN KEY (id_asignatura)   REFERENCES academico.asignatura(idAsignatura),
  CONSTRAINT fk_rb_depto FOREIGN KEY (id_departamento) REFERENCES academico.carrera(idCarrera)
) ENGINE=InnoDB;

CREATE TABLE recurso_biblioteca_x_tag (
  id_recurso_biblioteca INT UNSIGNED NOT NULL,
  id_tags               INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_recurso_biblioteca,id_tags),
  KEY fk_rbx_tag (id_tags),
  CONSTRAINT fk_rbx_recurso FOREIGN KEY (id_recurso_biblioteca) REFERENCES recurso_biblioteca(id_recurso_biblioteca),
  CONSTRAINT fk_rbx_tag     FOREIGN KEY (id_tags)               REFERENCES tags(id_tags)
) ENGINE=InnoDB;

/* Música */
CREATE TABLE recurso_musica (
  id_recurso_musica INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_asignatura     INT UNSIGNED NULL, /* academico.asignatura */
  id_departamento   INT UNSIGNED NULL, /* academico.carrera */
  titulo            VARCHAR(100) NOT NULL,
  compositor        VARCHAR(100) NULL,
  autor             VARCHAR(100) NOT NULL,
  anio_publicacion  YEAR NULL,
  descripcion       VARCHAR(255) NULL,
  tipo_recurso      VARCHAR(45) NOT NULL,   /* Partitura, Audio, etc. */
  formato_recurso   VARCHAR(45) NOT NULL,   /* PDF, MP3, etc. */
  tamanio_bytes     INT UNSIGNED NULL,
  fecha_subida      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  duracion_segundos INT UNSIGNED NULL,
  archivo_pdf       LONGBLOB NULL,
  archivo_audio     LONGBLOB NULL,
  archivo_partitura VARCHAR(100) NULL,
  KEY fk_rm_asig (id_asignatura),
  KEY fk_rm_depto (id_departamento),
  CONSTRAINT fk_rm_asig  FOREIGN KEY (id_asignatura)   REFERENCES academico.asignatura(idAsignatura),
  CONSTRAINT fk_rm_depto FOREIGN KEY (id_departamento) REFERENCES academico.carrera(idCarrera)
) ENGINE=InnoDB;

CREATE TABLE recurso_musica_x_tag (
  id_recurso_musica INT UNSIGNED NOT NULL,
  id_tags           INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_recurso_musica,id_tags),
  KEY fk_rmx_tag (id_tags),
  CONSTRAINT fk_rmx_recurso FOREIGN KEY (id_recurso_musica) REFERENCES recurso_musica(id_recurso_musica),
  CONSTRAINT fk_rmx_tag     FOREIGN KEY (id_tags)           REFERENCES tags(id_tags)
) ENGINE=InnoDB;






/* =======================================================
   ADMISIONES
======================================================= */

USE admisiones;

CREATE TABLE aspirante (
  idAspirante   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idPersona     INT UNSIGNED NOT NULL, /* identidad.persona (antes de ser alumno) */
  correoPersonal VARCHAR(120) NULL,
  telefono       VARCHAR(45)  NULL,
  fechaRegistro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  estado         ENUM('Activo','Inactivo','Bloqueado') NOT NULL DEFAULT 'Activo',
  KEY fk_asp_persona (idPersona),
  CONSTRAINT fk_asp_persona FOREIGN KEY (idPersona) REFERENCES identidad.persona(idPersona)
) ENGINE=InnoDB;

CREATE TABLE solicitudAdmision (
  idSolicitud        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idAspirante        INT UNSIGNED NOT NULL, /* admisiones.aspirante */
  idPeriodo          INT UNSIGNED NOT NULL, /* academico.periodoAcademico */
  idCarreraPrincipal INT UNSIGNED NOT NULL, /* academico.carrera */
  idCampus           INT UNSIGNED NOT NULL, /* academico.campus */
  idCarreraSecundaria INT UNSIGNED NULL,    /* academico.carrera */
  estado             ENUM('Pendiente','EnRevision','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
  motivoRechazo      VARCHAR(255) NULL,
  fechaCreacion      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaDecision      DATETIME NULL,
  KEY fk_sol_asp (idAspirante),
  KEY fk_sol_per (idPeriodo),
  KEY fk_sol_carr1 (idCarreraPrincipal),
  KEY fk_sol_carr2 (idCarreraSecundaria),
  KEY fk_sol_camp (idCampus),
  CONSTRAINT fk_sol_asp FOREIGN KEY (idAspirante)        REFERENCES aspirante(idAspirante),
  CONSTRAINT fk_sol_per FOREIGN KEY (idPeriodo)          REFERENCES academico.periodoAcademico(idPeriodo),
  CONSTRAINT fk_sol_carr1 FOREIGN KEY (idCarreraPrincipal) REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_sol_carr2 FOREIGN KEY (idCarreraSecundaria) REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_sol_camp FOREIGN KEY (idCampus)          REFERENCES academico.campus(idCampus)
) ENGINE=InnoDB;

CREATE TABLE documentacion (
  idDocumento  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idSolicitud  INT UNSIGNED NOT NULL,
  tipoDocumento ENUM('CertificadoSecundaria') NOT NULL,
  archivoPdf   LONGBLOB NOT NULL,
  tamanioBytes INT UNSIGNED NOT NULL,     /* máx 20MB a nivel de app */
  fechaSubido  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY fk_doc_sol (idSolicitud),
  CONSTRAINT fk_doc_sol FOREIGN KEY (idSolicitud) REFERENCES solicitudAdmision(idSolicitud)
) ENGINE=InnoDB;

CREATE TABLE tipoExamen (
  idTipoExamen TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       ENUM('PAA','PAM','PCCNS') NOT NULL,
  rangoMaximo  INT UNSIGNED NOT NULL, /* PAA=2000, PAM/PCCNS=800 */
  descripcion  VARCHAR(200) NULL
) ENGINE=InnoDB;

CREATE TABLE carrerasXexamen (
  idCarrera    INT UNSIGNED NOT NULL, /* academico.carrera */
  idTipoExamen TINYINT UNSIGNED NOT NULL, /* admisiones.tipoExamen */
  puntajeMinimo INT UNSIGNED NOT NULL,
  obligatorio   TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (idCarrera,idTipoExamen),
  KEY fk_cxe_tipo (idTipoExamen),
  CONSTRAINT fk_cxe_carrera FOREIGN KEY (idCarrera)    REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_cxe_tipo    FOREIGN KEY (idTipoExamen) REFERENCES tipoExamen(idTipoExamen)
) ENGINE=InnoDB;

CREATE TABLE examen (
  idExamen      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idSolicitud   INT UNSIGNED NOT NULL,     /* admisiones.solicitudAdmision */
  idTipoExamen  TINYINT UNSIGNED NOT NULL, /* admisiones.tipoExamen */
  idPeriodo     INT UNSIGNED NOT NULL,     /* academico.periodoAcademico */
  idCampus      INT UNSIGNED NOT NULL,     /* academico.campus */
  idAula        INT UNSIGNED NOT NULL,     /* academico.aula */
  fecha         DATE NOT NULL,
  hora          TIME NOT NULL,
  asistio       TINYINT(1) NOT NULL DEFAULT 0,
  nota          INT UNSIGNED NULL,         /* según tipo: 0–2000 o 0–800 */
  calificadoPor INT UNSIGNED NULL,         /* identidad.jefeDepartamento (revisor) */
  estado        ENUM('Programado','Presentado','Calificado','Anulado') NOT NULL DEFAULT 'Programado',
  KEY fk_ex_sol  (idSolicitud),
  KEY fk_ex_tipo (idTipoExamen),
  KEY fk_ex_per  (idPeriodo),
  KEY fk_ex_camp (idCampus),
  KEY fk_ex_aula (idAula),
  KEY fk_ex_cal  (calificadoPor),
  CONSTRAINT fk_ex_sol  FOREIGN KEY (idSolicitud)  REFERENCES solicitudAdmision(idSolicitud),
  CONSTRAINT fk_ex_tipo FOREIGN KEY (idTipoExamen) REFERENCES tipoExamen(idTipoExamen),
  CONSTRAINT fk_ex_per  FOREIGN KEY (idPeriodo)    REFERENCES academico.periodoAcademico(idPeriodo),
  CONSTRAINT fk_ex_camp FOREIGN KEY (idCampus)     REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_ex_aula FOREIGN KEY (idAula)       REFERENCES academico.aula(idAula),
  CONSTRAINT fk_ex_cal  FOREIGN KEY (calificadoPor) REFERENCES identidad.jefeDepartamento(idPersona)
) ENGINE=InnoDB;

CREATE TABLE cuposCarreraCampusPeriodo (
  idCupo          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idCarrera       INT UNSIGNED NOT NULL, /* academico.carrera */
  idCampus        INT UNSIGNED NOT NULL, /* academico.campus */
  idPeriodo       INT UNSIGNED NOT NULL, /* academico.periodoAcademico */
  cuposDisponibles INT UNSIGNED NOT NULL,
  cuposAsignados   INT UNSIGNED NOT NULL DEFAULT 0,
  fechaApertura    DATE NOT NULL,
  fechaCierre      DATE NOT NULL,
  KEY fk_ccp_car (idCarrera),
  KEY fk_ccp_cam (idCampus),
  KEY fk_ccp_per (idPeriodo),
  CONSTRAINT fk_ccp_car FOREIGN KEY (idCarrera) REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_ccp_cam FOREIGN KEY (idCampus)  REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_ccp_per FOREIGN KEY (idPeriodo) REFERENCES academico.periodoAcademico(idPeriodo)
) ENGINE=InnoDB;

CREATE TABLE listaEsperaAdmision (
  idLista     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idCarrera   INT UNSIGNED NOT NULL, /* academico.carrera */
  idCampus    INT UNSIGNED NOT NULL, /* academico.campus */
  idPeriodo   INT UNSIGNED NOT NULL, /* academico.periodoAcademico */
  idSolicitud INT UNSIGNED NOT NULL, /* admisiones.solicitudAdmision */
  posicion    INT UNSIGNED NOT NULL,
  estado      ENUM('EnEspera','Promovido','Removido') NOT NULL DEFAULT 'EnEspera',
  KEY fk_lea_car (idCarrera),
  KEY fk_lea_cam (idCampus),
  KEY fk_lea_per (idPeriodo),
  KEY fk_lea_sol (idSolicitud),
  CONSTRAINT fk_lea_car FOREIGN KEY (idCarrera)   REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_lea_cam FOREIGN KEY (idCampus)    REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_lea_per FOREIGN KEY (idPeriodo)   REFERENCES academico.periodoAcademico(idPeriodo),
  CONSTRAINT fk_lea_sol FOREIGN KEY (idSolicitud) REFERENCES solicitudAdmision(idSolicitud)
) ENGINE=InnoDB;

CREATE TABLE resultadoAdmision (
  idResultado       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idSolicitud       INT UNSIGNED NOT NULL, /* admisiones.solicitudAdmision */
  decision          ENUM('AprobadoPrincipal','AprobadoSecundaria','Rechazado') NOT NULL,
  puntajePAA        INT UNSIGNED NULL,
  puntajePAM        INT UNSIGNED NULL,
  puntajePCCNS      INT UNSIGNED NULL,
  idCarreraAsignada INT UNSIGNED NULL,     /* academico.carrera */
  idCampusAsignado  INT UNSIGNED NULL,     /* academico.campus */
  idPeriodo         INT UNSIGNED NOT NULL, /* academico.periodoAcademico */
  fechaResultado    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observaciones     VARCHAR(255) NULL,
  KEY fk_res_sol (idSolicitud),
  KEY fk_res_car (idCarreraAsignada),
  KEY fk_res_cam (idCampusAsignado),
  KEY fk_res_per (idPeriodo),
  CONSTRAINT fk_res_sol FOREIGN KEY (idSolicitud)       REFERENCES solicitudAdmision(idSolicitud),
  CONSTRAINT fk_res_car FOREIGN KEY (idCarreraAsignada) REFERENCES academico.carrera(idCarrera),
  CONSTRAINT fk_res_cam FOREIGN KEY (idCampusAsignado)  REFERENCES academico.campus(idCampus),
  CONSTRAINT fk_res_per FOREIGN KEY (idPeriodo)         REFERENCES academico.periodoAcademico(idPeriodo)
) ENGINE=InnoDB;

CREATE TABLE aceptacionCupo (
  idAceptacion        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idResultado         INT UNSIGNED NOT NULL, /* admisiones.resultadoAdmision */
  tokenConfirmacion   CHAR(64) NOT NULL,
  fechaEmisionToken   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechaLimiteToken    DATETIME NOT NULL,
  estado              ENUM('Pendiente','Aceptado','Rechazado','Vencido') NOT NULL DEFAULT 'Pendiente',
  fechaRespuesta      DATETIME NULL,
  KEY fk_ac_res (idResultado),
  CONSTRAINT fk_ac_res FOREIGN KEY (idResultado) REFERENCES resultadoAdmision(idResultado)
) ENGINE=InnoDB;



/* =======================================================
   AJUSTES
======================================================= */
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;









/* =======================================================
    INSERTAR EN TABLAS PERSONAS
======================================================= */
USE identidad;

INSERT INTO persona
  (numeroIdentidad, nombres, apellidos, correoInstitucional, correoPersonal, telefonoContacto, direccion)
VALUES
  -- ADMIN (sin institucional; login con correo personal)
  ('0801-2000-02941', 'admin',  'admin',     NULL,                'admina@gmail.com',  '11111111', 'unah'),

  -- ESTUDIANTE (institucional @unah.hn)
  ('0801-2000-02942', 'Milton', 'Alvarado',  'malvarado@unah.hn', 'miltona@gmail.com', '22222222', 'unah'),

  -- DOCENTE (institucional @unah.edu.hn)
  ('0801-2000-02943', 'Manuel', 'Inestroza', 'minestroza@unah.edu.hn', 'manueli@gmail.com', '33333333', 'unah'),

  -- COORDINADOR (login con correo personal)
  ('0801-2000-02944', 'Jose',   'Mario',     NULL,                'josem@gmail.com',   '44444444', 'unah'),

  -- JEFE_DEPTO (login con correo personal)
  ('0801-2000-02945', 'Irma',   'Gamez',     NULL,                'irmag@gmail.com',   '55555555', 'unah'),

  -- ASPIRANTE (login con correo personal)
  ('0801-2000-02946', 'Miguel', 'Zepeda',    NULL,                'miguelz@gmail.com', '66666666', 'unah')
;

/* =======================================================
   FILAS DE ROL
======================================================= */

-- ESTUDIANTE -> identidad.alumno 
INSERT INTO alumno (idPersona, numeroCuenta, preparacionAcademica, idCarrera, idCarreraSecundaria, indiceAcademico, fechaIngreso)
SELECT p.idPersona, '20202002001', 'Pregrado', NULL, NULL, NULL, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02942';

-- DOCENTE -> identidad.docente 
INSERT INTO docente (idPersona, numeroEmpleado, idDepartamentoAcademico, fechaContrato, shift, cubiculo)
SELECT p.idPersona, '20102002001', NULL, NULL, NULL, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02943';

-- COORDINADOR -> identidad.coordinador
INSERT INTO coordinador (idPersona, area, activo)
SELECT p.idPersona, 'Software', 1
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02944';

-- JEFE_DEPTO -> identidad.jefeDepartamento
INSERT INTO jefeDepartamento (idPersona, idDepartamentoAcademico, fechaInicioCargo, fechaFinCargo, razonFinalizacion)
SELECT p.idPersona, NULL, DATE('2025-02-01'), NULL, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02945';

-- ASPIRANTE -> admisiones.aspirante
USE admisiones;

INSERT INTO aspirante (idPersona, correoPersonal, telefono, estado)
SELECT p.idPersona, p.correoPersonal, p.telefonoContacto, 'Activo'
FROM identidad.persona p
WHERE p.numeroIdentidad = '0801-2000-02946';

/* =======================================================
   CREDENCIALES (UN USUARIO = UN ROL)
======================================================= */
USE identidad;

/* Hash de pruebas */
SET @DUMMY_HASH := '$argon2id$v=19$m=65536,t=3,p=1$ZHVtbXlTYWx0$ZHVtbXlIYXNoRm9yVGVzdA';

/* ADMIN */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoPersonal, 'ADMIN', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02941';

/* ESTUDIANTE */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoInstitucional, 'ESTUDIANTE', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02942';

/* DOCENTE */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoInstitucional, 'DOCENTE', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02943';

/* COORDINADOR */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoPersonal, 'COORDINADOR', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02944';

/* JEFE_DEPTO */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoPersonal, 'JEFE_DEPTO', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02945';

/* ASPIRANTE */
INSERT INTO credenciales (idPersona, usuario, rol, claveHash, ultimoLogin, intentosLogin, bloqueado, tokenRecuperacion)
SELECT p.idPersona, p.correoPersonal, 'ASPIRANTE', @DUMMY_HASH, NULL, 0, 0, NULL
FROM persona p
WHERE p.numeroIdentidad = '0801-2000-02946';





/* =======================================================
   Para cambiar los hash por claves normales
======================================================= */
UPDATE identidad.credenciales SET claveHash='admin123' WHERE usuario='admina@gmail.com';
UPDATE identidad.credenciales SET claveHash='estudiante123' WHERE usuario='malvarado@unah.hn';
UPDATE identidad.credenciales SET claveHash='docente123' WHERE usuario='minestroza@unah.edu.hn';
UPDATE identidad.credenciales SET claveHash='coordinador123' WHERE usuario='josem@gmail.com';
UPDATE identidad.credenciales SET claveHash='jefe123' WHERE usuario='irmag@gmail.com';
UPDATE identidad.credenciales SET claveHash='aspirante123' WHERE usuario='miguelz@gmail.com';