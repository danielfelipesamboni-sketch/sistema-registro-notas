-- ============================================================
-- PROYECTO FINAL - BASES DE DATOS
-- Motor: PostgreSQL
-- ============================================================

CREATE DATABASE registro_notas;
\c registro_notas;

CREATE TABLE docentes (
    cod_doc  VARCHAR(20)  PRIMARY KEY,
    nomb_doc VARCHAR(100) NOT NULL,
    clave    VARCHAR(100) NOT NULL
);

CREATE TABLE estudiantes (
    cod_est  VARCHAR(20)  PRIMARY KEY,
    nomb_est VARCHAR(200) NOT NULL
);

CREATE TABLE cursos (
    cod_cur  VARCHAR(20)  PRIMARY KEY,
    nomb_cur VARCHAR(100) NOT NULL,
    cod_doc  VARCHAR(20)  NOT NULL REFERENCES docentes(cod_doc) ON DELETE RESTRICT
);

-- Un estudiante no puede inscribirse dos veces al mismo curso/periodo
CREATE TABLE inscripciones (
    cod_cur VARCHAR(20) NOT NULL REFERENCES cursos(cod_cur)      ON DELETE CASCADE,
    cod_est VARCHAR(20) NOT NULL REFERENCES estudiantes(cod_est)  ON DELETE CASCADE,
    year    INTEGER     NOT NULL CHECK (year BETWEEN 2000 AND 2100),
    periodo VARCHAR(10) NOT NULL CHECK (periodo IN ('I', 'II')),
    PRIMARY KEY (cod_cur, cod_est, year, periodo)
);

-- Estructura de evaluación por curso, año y periodo
-- Cada semestre puede tener su propia distribución de parciales
CREATE TABLE notas (
    nota        SERIAL       PRIMARY KEY,
    desc_nota   VARCHAR(100) NOT NULL,
    porcentaje  NUMERIC(5,2) NOT NULL CHECK (porcentaje > 0 AND porcentaje <= 100),
    posicion    INTEGER      NOT NULL CHECK (posicion > 0),
    cod_cur     VARCHAR(20)  NOT NULL REFERENCES cursos(cod_cur) ON DELETE CASCADE,
    year        INTEGER      NOT NULL CHECK (year BETWEEN 2000 AND 2100),
    periodo     VARCHAR(10)  NOT NULL CHECK (periodo IN ('I', 'II')),
    fecha_inicio DATE        NOT NULL,
    fecha_fin    DATE        NOT NULL,
    UNIQUE (cod_cur, year, periodo, posicion),
    CHECK (fecha_fin >= fecha_inicio)
);

-- NO repite cod_cur/year/periodo porque ya están en notas (cumple 3FN)
-- Trigger valida que el estudiante esté inscrito en ese curso/periodo
CREATE TABLE calificaciones (
    cod_cal SERIAL       PRIMARY KEY,
    nota    INTEGER      NOT NULL REFERENCES notas(nota)          ON DELETE CASCADE,
    cod_est VARCHAR(20)  NOT NULL REFERENCES estudiantes(cod_est) ON DELETE CASCADE,
    valor   NUMERIC(4,2) NOT NULL CHECK (valor >= 0 AND valor <= 5),
    fecha   DATE         NOT NULL DEFAULT CURRENT_DATE,
    UNIQUE (nota, cod_est)
);

-- ============================================================
-- DATOS DE PRUEBA  (contraseña: 1234)
-- ============================================================
INSERT INTO docentes (cod_doc, nomb_doc, clave) VALUES
    ('DOC001', 'Jesús Reyes Carvajal', '1234'),
    ('DOC002', 'Nestor Suat',      '4321');

INSERT INTO cursos (cod_cur, nomb_cur, cod_doc) VALUES
    ('603401',  'Base de Datos',  'DOC001'),
    ('PRG202', 'Programación Orientada Objetos', 'DOC001'),
    ('ALG303', 'Estructura de Datos',     'DOC002');

INSERT INTO estudiantes (cod_est, nomb_est) VALUES
    ('160005380', 'Daniel Samboni'),
    ('160005370', 'Julian Baez'),
    ('160005360', 'Carlos Perez');
