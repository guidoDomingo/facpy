# Proyecto de Facturación Electrónica Multi-País

Este proyecto ha sido adaptado para soportar tanto la facturación electrónica de **Perú (SUNAT)** como la de **Paraguay (SIFEN - e-Kuatia)**.

## Características Principales

### ✅ Soporte Multi-País
- **Perú**: Sistema SUNAT (manteniendo compatibilidad con el código original)
- **Paraguay**: Sistema SIFEN e-Kuatia (nueva implementación)

### ✅ API Unificada
- Detección automática del país según la configuración de la empresa
- Endpoints uniformes que funcionan para ambos países
- Adaptación automática de formatos de datos

### ✅ Servicios Específicos
- `SunatService`: Para Perú (existente, mantenido)
- `SifenService`: Para Paraguay (nuevo)
- `BillingServiceFactory`: Factory para crear el servicio apropiado

## Estructura del Proyecto

```
app/
├── Services/
│   ├── SunatService.php          # Servicio original para Perú
│   ├── SifenService.php          # Nuevo servicio para Paraguay
│   └── BillingServiceFactory.php # Factory para seleccionar servicio
├── Http/Controllers/Api/
│   ├── InvoiceController.php     # Controlador original para Perú
│   ├── SifenController.php       # Controlador específico Paraguay
│   └── UnifiedBillingController.php # Controlador unificado
└── Models/
    └── Company.php               # Modelo extendido con campos Paraguay

config/
└── billing.php                  # Configuración de ambos países

database/migrations/
└── 2024_01_15_000000_add_paraguay_fields_to_companies_table.php
```

## Configuración

### Configurar Archivo .env

```env
# País por defecto (PE=Perú, PY=Paraguay)
BILLING_COUNTRY=PE

# Para desarrollo/pruebas
APP_ENV=local
```

### Migrar Base de Datos

```powershell
php artisan migrate
```

Esto agregará los campos necesarios para Paraguay en la tabla `companies`:
- `cert_password`: Contraseña del certificado digital
- `nombre_fantasia`: Nombre comercial/fantasía
- `codigo_departamento`, `departamento`: Datos de ubicación
- `codigo_distrito`, `distrito`
- `codigo_ciudad`, `ciudad`
- `numero_casa`: Número de casa
- `punto_expedicion`: Punto de expedición (001 por defecto)
- `pais`: País de la empresa (PE/PY)

## Uso de la API

### Endpoints Originales (Solo Perú)
```
POST /api/invoices/send    # Enviar factura SUNAT
POST /api/invoices/xml     # Generar XML SUNAT
POST /api/invoices/pdf     # Generar PDF SUNAT
```

### Endpoints Específicos Paraguay
```
POST /api/sifen/send       # Enviar documento SIFEN
POST /api/sifen/xml        # Generar XML SIFEN
POST /api/sifen/report     # Generar reporte SIFEN
POST /api/sifen/status     # Consultar estado documento
GET  /api/sifen/config     # Obtener configuración
```

### Endpoints Unificados (Recomendados) 🚀
```
POST /api/billing/send     # Enviar documento (detecta país automáticamente)
POST /api/billing/xml      # Generar XML (detecta país automáticamente)
POST /api/billing/report   # Generar reporte (detecta país automáticamente)
GET  /api/billing/info     # Información empresa y país
```

## Ejemplos de Uso

### 1. Enviar Factura (Endpoint Unificado)

```json
POST /api/billing/send
{
  "company_ruc": "20123456789",
  "document_type": "01",
  "series": "F001",
  "number": "00000001",
  "issue_date": "2024-01-15",
  "currency": "PEN",
  "customer": {
    "ruc": "20123456788",
    "razonSocial": "Cliente SAC",
    "direccion": "Av. Cliente 123"
  },
  "items": [
    {
      "description": "Producto 1",
      "quantity": 2,
      "unit_price": 100.00,
      "code": "PROD001",
      "unit": "NIU",
      "tax_exempt": false,
      "tax_rate": 18
    }
  ]
}
```

### 2. Para Paraguay (campos específicos)

Al configurar una empresa con `pais: "PY"`, el sistema adaptará automáticamente:

```json
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
      "unit_price": 1000000,
      "unit": "77",
      "tax_rate": 10
    }
  ]
}
```

### 3. Obtener Información de Configuración

```json
GET /api/billing/info?company_ruc=20123456789

Respuesta:
{
  "success": true,
  "company": {
    "ruc": "20123456789",
    "razon_social": "Mi Empresa SAC",
    "country": "PE",
    "environment": "test"
  },
  "country_config": {
    "name": "Perú",
    "service": "sunat",
    "currency": "PEN",
    "tax_name": "IGV",
    "tax_rates": {
      "standard": 18,
      "reduced": 0,
      "exempt": 0
    },
    "document_types": {
      "01": "Factura",
      "03": "Boleta de Venta"
    }
  }
}
```

## Diferencias por País

### Perú (SUNAT)
- **Moneda**: PEN (Soles)
- **Impuesto**: IGV 18%
- **Formato RUC**: 11 dígitos
- **Certificado**: Requerido (PFX)
- **Endpoints**: SUNAT Beta/Producción

### Paraguay (SIFEN)
- **Moneda**: PYG (Guaraníes)
- **Impuesto**: IVA 10% o 5%
- **Formato RUC**: Formato paraguayo
- **Certificado**: Requerido (P12)
- **CDC**: Código de Control del Documento (44 dígitos)
- **Endpoints**: SIFEN Test/Producción

## Instalación de Dependencias

El proyecto utiliza las mismas dependencias base, pero ahora también soporta:

```json
{
  "require": {
    "guzzlehttp/guzzle": "^7.2",
    "laravel/framework": "^10.10"
  }
}
```

Para Paraguay se utilizan las APIs REST de SIFEN en lugar de SOAP como en Perú.

## Configuración de Empresas

### Para Empresa Peruana
```php
Company::create([
    'ruc' => '20123456789',
    'razon_social' => 'Mi Empresa SAC',
    'direccion' => 'Av. Lima 123',
    'sol_user' => 'MODDATOS',
    'sol_pass' => 'moddatos',
    'cert_path' => 'certificates/certificado.pfx',
    'pais' => 'PE',
    'production' => false,
    'user_id' => 1
]);
```

### Para Empresa Paraguaya
```php
Company::create([
    'ruc' => '80123456-7',
    'razon_social' => 'Mi Empresa SRL',
    'nombre_fantasia' => 'Mi Empresa',
    'direccion' => 'Av. Asunción 123',
    'cert_path' => 'certificates/certificado.p12',
    'cert_password' => 'mi_password',
    'codigo_departamento' => '11',
    'departamento' => 'CAPITAL',
    'codigo_distrito' => '1',
    'distrito' => 'ASUNCIÓN',
    'codigo_ciudad' => '1',
    'ciudad' => 'ASUNCIÓN',
    'punto_expedicion' => '001',
    'pais' => 'PY',
    'production' => false,
    'user_id' => 1
]);
```

## Autenticación

Todos los endpoints requieren autenticación JWT:

```
Authorization: Bearer {tu_jwt_token}
```

## Estados de Respuesta

### Exitoso
```json
{
  "success": true,
  "country": "Paraguay",
  "service": "SIFEN",
  "cdc": "01801234567001000000120240115001234567890123456",
  "xml": "<?xml version='1.0'...",
  "message": "Documento enviado exitosamente"
}
```

### Error
```json
{
  "success": false,
  "error": "Error description",
  "message": "Error al procesar el documento"
}
```

## Desarrollo y Testing

### Ejecutar Migraciones
```powershell
php artisan migrate
```

### Iniciar Servidor de Desarrollo
```powershell
php artisan serve
```

### Testing de APIs
Se recomienda usar Postman o herramientas similares para testear los endpoints.

## Próximas Mejoras

- [ ] Implementación de firma digital robusta para Paraguay
- [ ] Soporte para notas de crédito/débito en Paraguay
- [ ] Generación de PDF mejorada
- [ ] Cache de respuestas
- [ ] Logs detallados por país
- [ ] Soporte para más tipos de documento

## Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## Soporte

Para soporte específico:
- **Perú (SUNAT)**: Consultar documentación SUNAT
- **Paraguay (SIFEN)**: Consultar documentación en https://www.dnit.gov.py/web/e-kuatia/documentacion-tecnica

## Licencia

MIT License - Ver archivo `LICENSE` para más detalles.
