-- =========================================
-- TABLES DE RÉFÉRENCE
-- =========================================

CREATE TABLE log_level (
    id SMALLINT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO log_level (id, name) VALUES
(100, 'debug'),
(200, 'info'),
(300, 'warning'),
(400, 'error'),
(500, 'critical');

-- -----------------------------------------

CREATE TABLE log_env (
    id SMALLINT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO log_env (id, name) VALUES
(1, 'dev'),
(2, 'test'),
(3, 'prod');

-- -----------------------------------------

CREATE TABLE log_source (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

-- -----------------------------------------

CREATE TABLE log_url (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    url VARCHAR(768) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE log_uri (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    url_id BIGINT NOT NULL,
    uri VARCHAR(255) NOT NULL UNIQUE,
    INDEX idx_log_uri_url_id (url_id),
    CONSTRAINT fk_log_uri_url FOREIGN KEY (url_id) REFERENCES log_url(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------

CREATE TABLE log_tag (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- =========================================
-- TABLE PRINCIPALE
-- =========================================

CREATE TABLE log_entry (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    ts DATETIME(6) NOT NULL,
    level_id SMALLINT NOT NULL,

    source_id BIGINT NOT NULL,
    env_id SMALLINT NOT NULL,

    url_id BIGINT NULL,
    uri_id BIGINT NULL,

    title VARCHAR(255) NULL,
    message TEXT NOT NULL,

    http_status SMALLINT NULL,
    duration_ms INT NULL,

    fingerprint CHAR(64) NULL,

    context JSON NULL,

    created_at DATETIME(6) NOT NULL,

    -- INDEX
    INDEX idx_ts (ts),
    INDEX idx_level (level_id),
    INDEX idx_source (source_id),
    INDEX idx_env (env_id),
    INDEX idx_fingerprint (fingerprint),
    INDEX idx_url_id (url_id),
    INDEX idx_uri_id (uri_id),

    -- FOREIGN KEYS
    CONSTRAINT fk_log_level FOREIGN KEY (level_id) REFERENCES log_level(id),
    CONSTRAINT fk_log_source FOREIGN KEY (source_id) REFERENCES log_source(id),
    CONSTRAINT fk_log_env FOREIGN KEY (env_id) REFERENCES log_env(id),
    CONSTRAINT fk_log_url FOREIGN KEY (url_id) REFERENCES log_url(id),
    CONSTRAINT fk_log_uri FOREIGN KEY (uri_id) REFERENCES log_uri(id)

) ENGINE=InnoDB;

-- =========================================
-- RELATION TAGS (MANY TO MANY)
-- =========================================

CREATE TABLE log_entry_tag (
    log_entry_id BIGINT NOT NULL,
    tag_id BIGINT NOT NULL,

    PRIMARY KEY (log_entry_id, tag_id),

    CONSTRAINT fk_tag_entry FOREIGN KEY (log_entry_id) REFERENCES log_entry(id) ON DELETE CASCADE,
    CONSTRAINT fk_tag FOREIGN KEY (tag_id) REFERENCES log_tag(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================
-- INDEX AVANCÉS (PERF)
-- =========================================

CREATE INDEX idx_level_ts ON log_entry(level_id, ts);
CREATE INDEX idx_source_ts ON log_entry(source_id, ts);
CREATE INDEX idx_env_ts ON log_entry(env_id, ts);

-- =========================================
-- OPTIONNEL : PARTITION (GROS VOLUME)
-- =========================================
-- ⚠️ à activer uniquement si gros trafic

-- ALTER TABLE log_entry
-- PARTITION BY RANGE (YEAR(ts)*100 + MONTH(ts)) (
--     PARTITION p202501 VALUES LESS THAN (202502),
--     PARTITION p202502 VALUES LESS THAN (202503)
-- );