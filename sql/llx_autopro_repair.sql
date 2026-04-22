CREATE TABLE IF NOT EXISTS llx_autopro_repair (
    rowid INT AUTO_INCREMENT PRIMARY KEY,

    ref VARCHAR(30) NULL,
    label VARCHAR(255) NULL,

    registration_number VARCHAR(9) NOT NULL,

    brand_id INT NULL,

    kilometrage INT DEFAULT 0,

    delivery_date DATETIME NULL,
    expected_return_date DATETIME NULL,
    
    fee DECIMAL(10, 2) NOT NULL DEFAULT 0,

    status SMALLINT DEFAULT 0,

    entity INT DEFAULT 1,

    tms TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    import_key VARCHAR(14) NULL,

    INDEX idx_autopro_ref (ref),
    INDEX idx_autopro_status (status),
    INDEX idx_autopro_entity (entity)
);

CREATE TABLE IF NOT EXISTS llx_c_autopro_brand (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    fee DECIMAL(10, 2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unique_label (label)
);