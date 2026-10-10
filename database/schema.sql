CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS carnets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(30) NOT NULL DEFAULT '',
    qr_token CHAR(40) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    nombres VARCHAR(120) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    cargo VARCHAR(150) NOT NULL,
    dependencia VARCHAR(150) NOT NULL,
    foto VARCHAR(255) NULL,
    estado ENUM('activo', 'suspendido', 'anulado') NOT NULL DEFAULT 'activo',
    emitido_en DATE NOT NULL,
    vence_en DATE NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_carnets_codigo (codigo),
    UNIQUE KEY uq_carnets_qr_token (qr_token),
    UNIQUE KEY uq_carnets_cedula (cedula),
    KEY idx_carnets_nombre (apellidos, nombres),
    KEY idx_carnets_estado (estado),
    CONSTRAINT fk_carnets_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
