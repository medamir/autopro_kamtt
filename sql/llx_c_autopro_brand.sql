CREATE TABLE IF NOT EXISTS llx_c_autopro_brand (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    fee DECIMAL(10, 2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,

    UNIQUE KEY unique_label (label),
    KEY idx_active (active)
);