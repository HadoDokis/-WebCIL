BEGIN;

ALTER TABLE wkf_etapes ADD COLUMN cpt_retard INT;
ALTER TABLE wkf_visas ADD COLUMN date_retard TIMESTAMP WITHOUT TIME ZONE;

COMMIT;