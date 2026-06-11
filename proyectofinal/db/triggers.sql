\c registro_notas;
-- ------------------------------------------------------------
-- TRIGGER 1: Validar que calificación esté entre 0.0 y 5.0
-- ------------------------------------------------------------
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

CREATE TRIGGER trg_validar_calificacion
BEFORE INSERT OR UPDATE ON calificaciones
FOR EACH ROW EXECUTE FUNCTION fn_validar_calificacion();

-- ------------------------------------------------------------
-- TRIGGER 2: Validar que la suma de porcentajes por
--            curso/año/periodo no supere el 100%
-- ------------------------------------------------------------
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
        RAISE EXCEPTION 'La suma de porcentajes supera 100%%. Acumulado actual: %', total;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_porcentaje
BEFORE INSERT OR UPDATE ON notas
FOR EACH ROW EXECUTE FUNCTION fn_validar_porcentaje();

-- ------------------------------------------------------------
-- TRIGGER 3: Validar que el estudiante esté inscrito en el
--            curso/periodo al que pertenece la nota
-- ------------------------------------------------------------
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

CREATE TRIGGER trg_validar_inscripcion_cal
BEFORE INSERT OR UPDATE ON calificaciones
FOR EACH ROW EXECUTE FUNCTION fn_validar_inscripcion_cal();

-- ------------------------------------------------------------
-- TRIGGER 4: Eliminar calificaciones huérfanas cuando se
--            desinscribe un estudiante de un curso/periodo
-- ------------------------------------------------------------
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

CREATE TRIGGER trg_cascade_calificaciones_desinscripcion
AFTER DELETE ON inscripciones
FOR EACH ROW EXECUTE FUNCTION fn_cascade_calificaciones_desinscripcion();

