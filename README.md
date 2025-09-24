# Sistema de Facturación Electrónica Paraguay (SIFEN)

Este proyecto está adaptado **exclusivamente para Paraguay** utilizando el sistema **SIFEN e-Kuatia**.

## 🇵🇾 Características

- ✅ **Solo Paraguay**: Sistema SIFEN e-Kuatia
- ✅ **API REST**: Integración directa con endpoints SIFEN
- ✅ **Generación XML**: Formato oficial Paraguay
- ✅ **CDC**: Código de Control del Documento automático
- ✅ **IVA 10%/5%**: Cálculos tributarios paraguayos
- ✅ **Guaraníes**: Moneda oficial PYG

## 📋 Requisitos

- PHP 8.1+
- Laravel 10+
- MySQL
- Certificado digital P12 (Paraguay)

## 🚀 Instalación

### 1. Clonar y Configurar

```powershell
# Clonar repositorio
git clone [tu-repo] facturacion-paraguay
cd facturacion-paraguay

# Ejecutar script de instalación
./install.bat
```

### 2. Configurar Base de Datos

```sql
CREATE DATABASE facpy;
```

### 3. Configurar .env

```env
DB_DATABASE=facpy
BILLING_COUNTRY=PY
SIFEN_TEST_URL=https://sifen-test.set.gov.py/de/ws/
SIFEN_PROD_URL=https://sifen.set.gov.py/de/ws/
```

### 4. Migrar y Configurar

```powershell
php artisan migrate
php artisan key:generate
php artisan jwt:secret
```

## 📡 API Endpoints

### Autenticación
```
POST /api/login
POST /api/register
POST /api/logout
```

### Empresas
```
GET    /api/companies
POST   /api/companies
GET    /api/companies/{id}
PUT    /api/companies/{id}
DELETE /api/companies/{id}
```

### Facturación Electrónica
```
POST /api/invoices/send     # Enviar documento a SIFEN
POST /api/invoices/xml      # Generar XML SIFEN
POST /api/invoices/report   # Generar reporte HTML
POST /api/invoices/status   # Consultar estado documento
GET  /api/invoices/config   # Configuración empresa
```

## 📄 Ejemplo de Uso

### 1. Crear Empresa

```json
POST /api/companies
{
  "razon_social": "Mi Empresa SRL",
  "ruc": "80123456-7",
  "direccion": "Av. Eusebio Ayala 1234",
  "nombre_fantasia": "Mi Empresa",
  "codigo_departamento": "11",
  "departamento": "CAPITAL",
  "codigo_distrito": "1",
  "distrito": "ASUNCIÓN",
  "codigo_ciudad": "1",
  "ciudad": "ASUNCIÓN",
  "numero_casa": "1234",
  "punto_expedicion": "001",
  "cert_path": "certificates/certificado.p12",
  "cert_password": "mi_password",
  "production": false
}
```

### 2. Enviar Factura

```json
POST /api/invoices/send
{
  "emisor": {
    "ruc": "80123456-7",
    "razonSocial": "Mi Empresa SRL",
    "direccion": "Av. Eusebio Ayala 1234"
  },
  "tipoDocumento": "1",
  "serie": "001",
  "numeroDocumento": "0000001",
  "fechaEmision": "2024-01-15",
  "receptor": {
    "ruc": "80987654-3",
    "razonSocial": "Cliente SAC",
    "direccion": "Av. Cliente 567"
  },
  "items": [
    {
      "descripcion": "Servicio de consultoría",
      "cantidad": 1,
      "precioUnitario": 5000000,
      "unidadMedida": "77",
      "tasaIva": 10
    }
  ]
}
```

### 3. Respuesta Exitosa

```json
{
  "success": true,
  "cdc": "01801234567001000000120240115001234567890123456",
  "xml": "<?xml version='1.0' encoding='UTF-8'?>...",
  "sifen_response": {
    "success": true,
    "status_code": 200
  },
  "message": "Documento enviado exitosamente"
}
```

## 📊 Códigos Paraguay

### Tipos de Documento
- `1`: Factura electrónica
- `4`: Autofactura electrónica
- `5`: Nota de crédito electrónica
- `6`: Nota de débito electrónica

### Departamentos
- `11`: CAPITAL
- `12`: SAN PEDRO
- `13`: CORDILLERA
- `21`: CENTRAL
- (etc...)

### Unidades de Medida
- `77`: Unidad
- `04`: Gramo
- `05`: Kilogramo
- `14`: Litro

### Tasas de IVA
- `10`: IVA 10% (estándar)
- `5`: IVA 5% (reducida)
- `0`: Exento

## 🔧 Configuración Avanzada

### Certificado Digital

1. Obtener certificado P12 de una AC autorizada en Paraguay
2. Colocar en `storage/certificates/`
3. Configurar password en company

### Ambientes

```env
# Test
SIFEN_TEST_URL=https://sifen-test.set.gov.py/de/ws/

# Producción
SIFEN_PROD_URL=https://sifen.set.gov.py/de/ws/
```

## 🐛 Troubleshooting

### Error: Certificado inválido
```
Verificar que el certificado P12 esté vigente y la contraseña sea correcta
```

### Error: CDC duplicado
```
El número de documento ya fue usado. Incrementar numeración.
```

### Error: XML inválido
```
Verificar que todos los campos requeridos estén presentes y en formato correcto
```

## 📚 Documentación Oficial

- [SIFEN e-Kuatia](https://www.dnit.gov.py/web/e-kuatia/)
- [Documentación Técnica](https://www.dnit.gov.py/web/e-kuatia/documentacion-tecnica)

## 📞 Soporte

- **Email**: facturacionelectronica@dnit.gov.py
- **Mesa de Ayuda**: [SIFEN](https://servicios.set.gov.py/eset-publico/EnvioMailSetIService.do)
- **Teléfono**: (021) 729 7000 Opción 2

## 📄 Licencia

MIT License

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
