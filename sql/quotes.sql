CREATE TABLE IF NOT EXISTS crm_quotes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reference      VARCHAR(40)  NOT NULL,
    token          CHAR(32)     NOT NULL,
    deal_id        INT          NULL,
    client_name    VARCHAR(150) NULL,
    client_company VARCHAR(150) NULL,
    client_phone   VARCHAR(40)  NULL,
    client_email   VARCHAR(190) NULL,
    service        VARCHAR(200) NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    valid_until    DATE         NULL,
    downloaded_at  DATETIME     NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_reference (reference),
    UNIQUE KEY uniq_token (token),
    INDEX idx_deal (deal_id),
    CONSTRAINT fk_quote_deal FOREIGN KEY (deal_id)
        REFERENCES crm_deals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
