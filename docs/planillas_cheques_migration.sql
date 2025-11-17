SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO tesoreria_oficinas.tes_planillas_cheques (
    id,
    numero_planilla,
    fecha_generacion,
    estado,
    fecha_anulacion,
    motivo_anulacion,
    generada_por,
    anulada_por,
    created_at,
    created_by,
    updated_at,
    updated_by,
    deleted_at,
    deleted_by
)
SELECT
    v.id,
    v.numero,
    v.created_at,
    CASE
        WHEN v.canceled_at IS NULL THEN 'creada'
        ELSE 'anulada'
    END AS estado,
    v.canceled_at,
    v.canceled_motivo,
    1,
    v.canceled_id,
    v.created_at,
    1,
    v.updated_at,
    1,
    v.deleted_at,
    v.deleted_id
FROM
    `jpm-api`.cheques AS v;

SET FOREIGN_KEY_CHECKS = 1;