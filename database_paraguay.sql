-- Base de datos para Facturación Electrónica Paraguay
-- Ejecutar este script en MySQL

CREATE DATABASE IF NOT EXISTS facpy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE facpy;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id)
);

-- Tabla de empresas (adaptada para Paraguay)
CREATE TABLE IF NOT EXISTS companies (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    razon_social VARCHAR(255) NOT NULL,
    ruc VARCHAR(20) NOT NULL,
    direccion TEXT NOT NULL,
    logo_path VARCHAR(500) NULL,
    cert_path VARCHAR(500) NULL,
    cert_password VARCHAR(255) NULL,
    production TINYINT(1) NOT NULL DEFAULT 0,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Campos específicos para Paraguay
    nombre_fantasia VARCHAR(255) NULL,
    codigo_departamento VARCHAR(2) NULL,
    departamento VARCHAR(100) NULL,
    codigo_distrito VARCHAR(3) NULL,
    distrito VARCHAR(100) NULL,
    codigo_ciudad VARCHAR(5) NULL,
    ciudad VARCHAR(100) NULL,
    numero_casa VARCHAR(50) NULL,
    punto_expedicion VARCHAR(3) NOT NULL DEFAULT '001',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabla de documentos electrónicos (para auditoría)
CREATE TABLE IF NOT EXISTS electronic_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    company_id BIGINT UNSIGNED NOT NULL,
    cdc VARCHAR(44) NOT NULL UNIQUE,
    tipo_documento VARCHAR(2) NOT NULL,
    serie VARCHAR(10) NOT NULL,
    numero_documento VARCHAR(20) NOT NULL,
    fecha_emision DATE NOT NULL,
    receptor_ruc VARCHAR(20) NULL,
    receptor_razon_social VARCHAR(255) NULL,
    total_documento DECIMAL(15,2) NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    xml_content LONGTEXT NULL,
    response_sifen LONGTEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    PRIMARY KEY (id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_cdc (cdc),
    INDEX idx_company_fecha (company_id, fecha_emision),
    INDEX idx_estado (estado)
);

-- Insertar usuario de prueba
INSERT INTO users (name, email, password, created_at, updated_at) VALUES 
('Admin Paraguay', 'admin@facpy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW(), NOW());

-- Insertar empresa de prueba para Paraguay
INSERT INTO companies (
    razon_social, ruc, direccion, nombre_fantasia,
    codigo_departamento, departamento, codigo_distrito, distrito,
    codigo_ciudad, ciudad, numero_casa, punto_expedicion,
    production, user_id, created_at, updated_at
) VALUES (
    'Empresa Demo Paraguay SRL',
    '80123456-7',
    'Av. Eusebio Ayala 1234',
    'Demo Paraguay',
    '11',
    'CAPITAL',
    '1',
    'ASUNCIÓN',
    '1',
    'ASUNCIÓN',
    '1234',
    '001',
    0,
    1,
    NOW(),
    NOW()
);

-- Tabla de migraciones de Laravel (para compatibilidad)
CREATE TABLE IF NOT EXISTS migrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    migration VARCHAR(255) NOT NULL,
    batch INT NOT NULL,
    PRIMARY KEY (id)
);

-- Registrar migraciones como ejecutadas
INSERT INTO migrations (migration, batch) VALUES 
('2014_10_12_000000_create_users_table', 1),
('2014_10_12_100000_create_password_reset_tokens_table', 1),
('2019_08_19_000000_create_failed_jobs_table', 1),
('2019_12_14_000001_create_personal_access_tokens_table', 1),
('2023_08_09_232231_create_companies_table', 1),
('2024_01_15_000000_add_paraguay_fields_to_companies_table', 1);

-- Crear tabla password_reset_tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    PRIMARY KEY (email)
);

-- Crear tabla failed_jobs
CREATE TABLE IF NOT EXISTS failed_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- Crear tabla personal_access_tokens
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
);

SELECT 'Base de datos creada exitosamente para Paraguay!' as status;
