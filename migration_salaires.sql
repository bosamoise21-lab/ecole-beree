-- Migration additive pour activer la gestion des salaires.
-- Sauvegarder la base avant l'exécution et lancer ce fichier une seule fois.
-- Aucune table ni donnée existante n'est supprimée.

USE ecole;

ALTER TABLE enseignants
    ADD COLUMN salaire_mensuel DECIMAL(12,2) NULL AFTER date_embauche;

CREATE TABLE employes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(30) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    poste VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    telephone VARCHAR(30),
    date_embauche DATE,
    salaire_mensuel DECIMAL(12,2),
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_employe_salaire CHECK (salaire_mensuel IS NULL OR salaire_mensuel > 0)
) ENGINE = InnoDB;

CREATE TABLE paiements_salaires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enseignant_id INT UNSIGNED,
    employe_id INT UNSIGNED,
    periode DATE NOT NULL,
    montant DECIMAL(12,2) NOT NULL,
    date_paiement DATE NOT NULL,
    mode_paiement ENUM('cash', 'banque', 'mobile_money', 'cheque') NOT NULL DEFAULT 'cash',
    commentaire VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_paiement_salaire_montant CHECK (montant > 0),
    CONSTRAINT chk_paiement_salaire_periode CHECK (DAYOFMONTH(periode) = 1),
    CONSTRAINT chk_paiement_salaire_personne CHECK (
        (enseignant_id IS NOT NULL AND employe_id IS NULL)
        OR (enseignant_id IS NULL AND employe_id IS NOT NULL)
    ),
    CONSTRAINT fk_paiement_salaire_enseignant
        FOREIGN KEY (enseignant_id) REFERENCES enseignants(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_paiement_salaire_employe
        FOREIGN KEY (employe_id) REFERENCES employes(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_paiement_salaire_periode (periode),
    INDEX idx_paiement_salaire_date (date_paiement)
) ENGINE = InnoDB;
