# Configuración de Ejemplo para Paraguay

## Paso 1: Configurar el archivo .env

Agrega estas variables a tu archivo `.env`:

```env
# Configuración de Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=facturacion_electronica
DB_USERNAME=root
DB_PASSWORD=

# Configuración de País por defecto
BILLING_COUNTRY=PY

# JWT Configuration
JWT_SECRET=tu_clave_jwt_aqui

# SIFEN Paraguay URLs
SIFEN_TEST_URL=https://sifen-test.set.gov.py/de/ws/
SIFEN_PROD_URL=https://sifen.set.gov.py/de/ws/

# SUNAT Peru URLs (mantener para compatibilidad)
SUNAT_TEST_URL=https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
SUNAT_PROD_URL=https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService
```

## Paso 2: Crear Usuario de Prueba

```sql
INSERT INTO users (name, email, email_verified_at, password, created_at, updated_at) 
VALUES (
    'Admin Usuario', 
    'admin@example.com', 
    NOW(), 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    NOW(), 
    NOW()
);
```

## Paso 3: Crear Empresa Paraguaya

```sql
INSERT INTO companies (
    razon_social, 
    ruc, 
    direccion, 
    nombre_fantasia,
    codigo_departamento,
    departamento,
    codigo_distrito,
    distrito,
    codigo_ciudad,
    ciudad,
    numero_casa,
    punto_expedicion,
    pais,
    production, 
    user_id,
    created_at, 
    updated_at
) VALUES (
    'Mi Empresa Paraguaya SRL',
    '80123456-7',
    'Av. Eusebio Ayala 1234',
    'Mi Empresa PY',
    '11',
    'CAPITAL',
    '1',
    'ASUNCIÓN',
    '1',
    'ASUNCIÓN',
    '1234',
    '001',
    'PY',
    0,
    1,
    NOW(),
    NOW()
);
```

## Paso 4: Crear Empresa Peruana (para testing)

```sql
INSERT INTO companies (
    razon_social, 
    ruc, 
    direccion, 
    sol_user,
    sol_pass,
    pais,
    production, 
    user_id,
    created_at, 
    updated_at
) VALUES (
    'Mi Empresa Peruana SAC',
    '20123456789',
    'Av. Lima 567',
    'MODDATOS',
    'moddatos',
    'PE',
    0,
    1,
    NOW(),
    NOW()
);
```

## Paso 5: Testing con Postman

1. Importar `postman-collection.json`
2. Hacer login para obtener JWT token
3. Usar el token en las demás requests
4. Probar endpoints unificados en `/api/billing/`

## Endpoints Principales

### Obtener información de empresa
```
GET /api/billing/info?company_ruc=80123456-7
```

### Enviar factura (Paraguay)
```
POST /api/billing/send
{
  "company_ruc": "80123456-7",
  "document_type": "1",
  "series": "001",
  "number": "0000001",
  "issue_date": "2024-01-15",
  "items": [
    {
      "description": "Servicio de consultoría",
      "quantity": 1,
      "unit_price": 5000000,
      "tax_rate": 10
    }
  ]
}
```

### Enviar factura (Perú)
```
POST /api/billing/send
{
  "company_ruc": "20123456789",
  "document_type": "01",
  "series": "F001",
  "number": "00000001",
  "issue_date": "2024-01-15",
  "items": [
    {
      "description": "Producto ejemplo",
      "quantity": 2,
      "unit_price": 100.00,
      "tax_rate": 18
    }
  ]
}
```

## Códigos Importantes Paraguay

### Tipos de Documento
- `1`: Factura electrónica
- `4`: Autofactura electrónica
- `5`: Nota de crédito electrónica
- `6`: Nota de débito electrónica

### Unidades de Medida
- `77`: Unidad
- `04`: Gramo
- `05`: Kilogramo
- `14`: Litro
- `15`: Metro

### Departamentos Paraguay
- `11`: CAPITAL
- `12`: SAN PEDRO
- `13`: CORDILLERA
- `14`: GUAIRÁ
- `15`: CAAGUAZÚ
- (etc...)

### Tasas de IVA Paraguay
- `10`: IVA 10% (tasa estándar)
- `5`: IVA 5% (tasa reducida)
- `0`: Exento de IVA

## Estructura del CDC (Paraguay)

El CDC (Código de Control del Documento) tiene 44 dígitos:
- Posiciones 1-2: Tipo de documento
- Posiciones 3-10: RUC del emisor (sin DV)
- Posiciones 11-13: Punto de expedición
- Posiciones 14-20: Número del documento
- Posiciones 21-21: Tipo de emisión
- Posiciones 22-29: Fecha de emisión (AAAAMMDD)
- Posiciones 30-37: Número de seguridad
- Posiciones 38-44: Código de control

Ejemplo: `01801234567001000000120240115001234567890123456`

## Troubleshooting

### Error: ext-sodium missing
```bash
composer install --ignore-platform-req=ext-sodium
```

### Error: No application encryption key
```bash
php artisan key:generate
```

### Error: JWT secret not set
```bash
php artisan jwt:secret
```

### Error: Class not found
```bash
composer dump-autoload
php artisan config:cache
php artisan route:cache
```
