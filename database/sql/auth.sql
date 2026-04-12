-- Remplacer ${SQL_PREFIXE} par la valeur de .env si vous appliquez ce script manuellement.

CREATE TABLE ${SQL_PREFIXE}auth_credential (
	id BIGINT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL,
	type VARCHAR(50) NOT NULL,
	api_key VARCHAR(64) NOT NULL,
	client_secret_hash VARCHAR(255) NOT NULL,
	is_active BOOLEAN DEFAULT TRUE,
	created_at DATETIME NOT NULL,
	updated_at DATETIME NOT NULL,
	CONSTRAINT api_key UNIQUE (api_key)
) ENGINE=InnoDB;
