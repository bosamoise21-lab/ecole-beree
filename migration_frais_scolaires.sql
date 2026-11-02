-- Migration additive pour les tarifs par classe et année scolaire.
-- Sauvegarder la base avant l'exécution et lancer ce fichier une seule fois.
-- Les anciens paiements trimestriels gardent leur trimestre à 0 tant qu'il
-- n'a pas été vérifié et affecté manuellement dans l'espace Frais scolaires.

USE ecole;

ALTER TABLE paiements
    ADD COLUMN inscription_id INT UNSIGNED NULL AFTER eleve_id,
    ADD COLUMN trimestre TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER type_paiement,
    ADD INDEX idx_paiement_inscription_composant (inscription_id, type_paiement, trimestre),
    ADD CONSTRAINT chk_paiement_trimestre
        CHECK (
            (type_paiement = 'trimestre' AND trimestre BETWEEN 0 AND 3)
            OR (type_paiement <> 'trimestre' AND trimestre = 0)
        ),
    ADD CONSTRAINT fk_paiement_inscription
        FOREIGN KEY (inscription_id) REFERENCES inscriptions(id)
        ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE paiements p
INNER JOIN (
    SELECT eleve_id, MIN(id) AS inscription_id
    FROM inscriptions
    GROUP BY eleve_id
    HAVING COUNT(*) = 1
) i ON i.eleve_id = p.eleve_id
SET p.inscription_id = i.inscription_id;

CREATE TABLE tarifs_scolaires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    classe_id INT UNSIGNED NOT NULL,
    type_paiement ENUM('inscription', 'trimestre', 'frais', 'autre') NOT NULL,
    trimestre TINYINT UNSIGNED NOT NULL DEFAULT 0,
    montant DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tarif_classe_composant (classe_id, type_paiement, trimestre),
    CONSTRAINT chk_tarif_montant CHECK (montant > 0),
    CONSTRAINT chk_tarif_trimestre
        CHECK (
            (type_paiement = 'trimestre' AND trimestre BETWEEN 1 AND 3)
            OR (type_paiement <> 'trimestre' AND trimestre = 0)
        ),
    CONSTRAINT fk_tarif_classe
        FOREIGN KEY (classe_id) REFERENCES classes(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
