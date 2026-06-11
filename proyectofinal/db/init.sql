-- Tablas
CREATE TABLE IF NOT EXISTS docentes (
    cod_doc  VARCHAR(20)  PRIMARY KEY,
    nomb_doc VARCHAR(100) NOT NULL,
    clave    VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS estudiantes (
    cod_est  VARCHAR(20)  PRIMARY KEY,
    nomb_est VARCHAR(200) NOT NULL
);

CREATE TABLE IF NOT EXISTS cursos (
    cod_cur  VARCHAR(20)  PRIMARY KEY,
    nomb_cur VARCHAR(100) NOT NULL,
    cod_doc  VARCHAR(20)  NOT NULL REFERENCES docentes(cod_doc) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS inscripciones (
    cod_cur VARCHAR(20) NOT NULL REFERENCES cursos(cod_cur)      ON DELETE CASCADE,
    cod_est VARCHAR(20) NOT NULL REFERENCES estudiantes(cod_est) ON DELETE CASCADE,
    year    INTEGER     NOT NULL CHECK (year BETWEEN 2000 AND 2100),
    periodo VARCHAR(10) NOT NULL CHECK (periodo IN ('I', 'II')),
    PRIMARY KEY (cod_cur, cod_est, year, periodo)
);

CREATE TABLE IF NOT EXISTS notas (
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

CREATE TABLE IF NOT EXISTS calificaciones (
    cod_cal SERIAL       PRIMARY KEY,
    nota    INTEGER      NOT NULL REFERENCES notas(nota)          ON DELETE CASCADE,
    cod_est VARCHAR(20)  NOT NULL REFERENCES estudiantes(cod_est) ON DELETE CASCADE,
    valor   NUMERIC(4,2) NOT NULL CHECK (valor >= 0 AND valor <= 5),
    fecha   DATE         NOT NULL DEFAULT CURRENT_DATE,
    UNIQUE (nota, cod_est)
);

-- Triggers
CREATE OR REPLACE FUNCTION fn_validar_calificacion()
RETURNS TRIGGER AS $$
BEGIN	
    IF NEW.valor < 0 THEN
        RAISE EXCEPTION 'La calificación no puede ser negativa: %', NEW.valor;
    END IF;
    IF NEW.valor > 5 THEN
        RAISE EXCEPTION 'La calificación no puede superar 5.0: %', NEW.valor;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_calificacion ON calificaciones;
CREATE TRIGGER trg_validar_calificacion
BEFORE INSERT OR UPDATE ON calificaciones
FOR EACH ROW EXECUTE FUNCTION fn_validar_calificacion();

CREATE OR REPLACE FUNCTION fn_validar_porcentaje()
RETURNS TRIGGER AS $$
DECLARE
    total NUMERIC;
BEGIN
    SELECT COALESCE(SUM(porcentaje), 0) INTO total
    FROM notas
    WHERE cod_cur = NEW.cod_cur
      AND year    = NEW.year
      AND periodo = NEW.periodo
      AND nota   != COALESCE(NEW.nota, -1);

    IF (total + NEW.porcentaje) > 100 THEN
        RAISE EXCEPTION 'La suma de porcentajes supera 100%%. Acumulado: %', total;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_porcentaje ON notas;
CREATE TRIGGER trg_validar_porcentaje
BEFORE INSERT OR UPDATE ON notas
FOR EACH ROW EXECUTE FUNCTION fn_validar_porcentaje();

CREATE OR REPLACE FUNCTION fn_validar_inscripcion_cal()
RETURNS TRIGGER AS $$
DECLARE
    v_cod_cur VARCHAR(20);
    v_year    INTEGER;
    v_periodo VARCHAR(10);
BEGIN
    SELECT cod_cur, year, periodo
      INTO v_cod_cur, v_year, v_periodo
      FROM notas WHERE nota = NEW.nota;

    IF NOT EXISTS (
        SELECT 1 FROM inscripciones
        WHERE cod_cur = v_cod_cur
          AND cod_est = NEW.cod_est
          AND year    = v_year
          AND periodo = v_periodo
    ) THEN
        RAISE EXCEPTION 'El estudiante % no está inscrito en el curso % para %-%',
            NEW.cod_est, v_cod_cur, v_year, v_periodo;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_inscripcion_cal ON calificaciones;
CREATE TRIGGER trg_validar_inscripcion_cal
BEFORE INSERT OR UPDATE ON calificaciones
FOR EACH ROW EXECUTE FUNCTION fn_validar_inscripcion_cal();

CREATE OR REPLACE FUNCTION fn_cascade_calificaciones_desinscripcion()
RETURNS TRIGGER AS $$
BEGIN
    DELETE FROM calificaciones
    WHERE cod_est = OLD.cod_est
      AND nota IN (
          SELECT nota FROM notas
          WHERE cod_cur = OLD.cod_cur
            AND year    = OLD.year
            AND periodo = OLD.periodo
      );
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_cascade_calificaciones_desinscripcion ON inscripciones;
CREATE TRIGGER trg_cascade_calificaciones_desinscripcion
AFTER DELETE ON inscripciones
FOR EACH ROW EXECUTE FUNCTION fn_cascade_calificaciones_desinscripcion();


INSERT INTO docentes (cod_doc, nomb_doc, clave) VALUES
     ('DOC001', 'Jesús Reyes', '1234'),
    ('DOC002', 'Nestor Suat',  '432'),
    ('DOC003', 'Christian Gomez', '12')
ON CONFLICT (cod_doc) DO UPDATE SET nomb_doc=EXCLUDED.nomb_doc, clave=EXCLUDED.clave;

INSERT INTO cursos (cod_cur, nomb_cur, cod_doc) VALUES
     ('603401',  'Base de Datos',  'DOC001'),
    ('PRG202', 'Programación Orientada Objetos', 'DOC001'),
    ('ALG303', 'Estructura de Datos',     'DOC002'),
    ('CAL404', 'Calculo Integral', 'DOC003')
ON CONFLICT (cod_cur) DO UPDATE SET nomb_cur=EXCLUDED.nomb_cur, cod_doc=EXCLUDED.cod_doc;

INSERT INTO estudiantes (cod_est, nomb_est) VALUES
     ('160005380', 'Daniel Samboni'),
    ('160005370', 'Julian Baez'),
    ('160005360', 'Carlos Perez'),
    ('160005350', 'pepito'),
    ('160005340', 'Nicolas Gonzales')
ON CONFLICT (cod_est) DO UPDATE SET nomb_est=EXCLUDED.nomb_est;
