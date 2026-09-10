-- CRM SDS — contacts, opportunités et historique d'échanges.
-- Appliqué par tools/migrate_crm.php (idempotent).

CREATE TABLE IF NOT EXISTS crm_contacts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(150) NOT NULL,
    company      VARCHAR(150) NULL,
    email        VARCHAR(190) NULL,
    phone        VARCHAR(40)  NULL,
    city         VARCHAR(100) NULL,
    -- prospect : jamais acheté ; client : au moins une affaire gagnée
    kind         ENUM('prospect','client') NOT NULL DEFAULT 'prospect',
    notes        TEXT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kind (kind),
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_deals (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    contact_id    INT NULL,
    title         VARCHAR(200) NOT NULL,
    service_id    INT NULL,
    -- d'où vient la demande, pour savoir quel canal rapporte
    source        ENUM('chatbot','formulaire','whatsapp','recommandation','direct','autre')
                  NOT NULL DEFAULT 'autre',
    stage         ENUM('nouveau','contacte','devis','gagne','perdu')
                  NOT NULL DEFAULT 'nouveau',
    amount        DECIMAL(12,2) NULL,     -- montant estimé ou conclu, en FCFA
    summary       TEXT NULL,              -- la demande initiale, telle que reçue
    next_action   VARCHAR(200) NULL,      -- « rappeler », « envoyer le devis »…
    next_action_at DATE NULL,
    lost_reason   VARCHAR(200) NULL,
    closed_at     DATETIME NULL,
    -- Origine exacte (« message:12 », « chat:a1b2… ») : garantit qu'une reprise
    -- de l'existant relancée deux fois ne crée pas de doublon.
    source_ref    VARCHAR(190) NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_source_ref (source_ref),
    INDEX idx_stage (stage),
    INDEX idx_contact (contact_id),
    INDEX idx_next_action (next_action_at),
    CONSTRAINT fk_deal_contact FOREIGN KEY (contact_id)
        REFERENCES crm_contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS crm_activities (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    deal_id    INT NOT NULL,
    kind       ENUM('note','appel','whatsapp','email','rdv','systeme')
               NOT NULL DEFAULT 'note',
    body       TEXT NOT NULL,
    author     VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_deal (deal_id, created_at),
    CONSTRAINT fk_activity_deal FOREIGN KEY (deal_id)
        REFERENCES crm_deals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
