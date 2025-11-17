SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO tesoreria_oficinas.tes_cheques (
    id,
    cuenta_bancaria_id,
    serie,
    numero_cheque,
    documento_tipo,
    documento_numero,
    fecha_emision,
    emitido_por,
    beneficiario,
    concepto,
    monto,
    fecha_anulacion,
    anulado_por,
    motivo_anulacion,
    fecha_planilla_anulada,
    planilla_anulada_por,
    planilla_id,
    estado,
    created_at,
    created_by,
    updated_at,
    updated_by,
    deleted_at,
    deleted_by
)
SELECT
    v.id,
    1, -- Hardcoded value
    v.serie,
    v.numero,
    v.doc_tipo,
    v.doc_numero,
    v.issued_at,
    v.issued_id,
    v.titular,
    v.concepto,
    v.monto,
    v.canceled_at,
    v.canceled_id,
    v.canceled_motivo,
    v.pl_canceled_at,
    v.pl_canceled_id,
    v.ch_planillas_id,
    CASE
        WHEN v.issued_at IS NULL AND v.canceled_at IS NULL THEN 'disponible'
        ELSE 'en_planilla'
    END AS estado,
    v.created_at,
    1, -- Hardcoded value
    v.updated_at,
    1, -- Hardcoded value
    v.deleted_at,
    v.deleted_id
FROM
    `jpm-api`.cheques AS v;

SET FOREIGN_KEY_CHECKS = 1;