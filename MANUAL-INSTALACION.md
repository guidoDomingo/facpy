# 🇵🇾 Manual de Instalación SIFEN Paraguay

## Guía Completa de Configuración del Sistema de Facturación Electrónica

### 📋 Requisitos Previos

- **PHP 8.1+** con extensiones:
  - OpenSSL
  - SOAP
  - XML
  - GD o Imagick
  - cURL
- **Composer** instalado
- **Base de datos** MySQL 8.0+
- **Certificado P12** válido de la SET Paraguay

### 🚀 Instalación Paso a Paso

#### 1. Dependencias del Proyecto

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias específicas SIFEN (si no están)
composer require endroid/qr-code:^6.0
composer require tecnickcom/tcpdf:^6.10
composer require tymon/jwt-auth
```

#### 2. Configuración Base

```bash
# Copiar archivo de configuración
copy .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Generar clave JWT
php artisan jwt:secret
```

#### 3. Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Poblar datos iniciales (opcional)
php artisan db:seed
```

#### 4. Configuración SIFEN

##### Archivo .env
Agregar estas líneas al archivo `.env`:

```env
# Configuración SIFEN Paraguay
SIFEN_TEST_URL=https://sifen-test.set.gov.py/de/ws/
SIFEN_PROD_URL=https://sifen.set.gov.py/de/ws/
SIFEN_DEFAULT_ENV=test

# JWT para API
JWT_SECRET=tu_clave_jwt_generada
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Configuraciones adicionales SIFEN
SIFEN_TIMEOUT=30
SIFEN_MAX_RETRY=3
SIFEN_BATCH_SIZE=15
```

#### 5. Configuración Inicial Automática

```bash
# Ejecutar configuración automática
php artisan sifen:setup

# Configurar empresa
php artisan sifen:company

# Probar conexión
php artisan sifen:test
```

### 📁 Estructura de Directorios SIFEN

El sistema creará automáticamente:

```
storage/
├── certificates/           # Certificados P12
├── sifen/
│   ├── xml/               # XMLs generados
│   ├── pdf/               # PDFs (KuDE)
│   ├── logs/              # Logs SIFEN
│   └── temp/              # Archivos temporales
```

### 🔐 Configuración de Certificados

#### Certificado de Pruebas
1. Colocar certificado P12 en `storage/certificates/`
2. Configurar ruta en base de datos a través del comando `sifen:company`
3. Verificar con `php artisan sifen:test --service=certificate`

#### Certificado de Producción
- Seguir el mismo proceso con certificado oficial de SET Paraguay
- Cambiar ambiente a 'production' en configuración de empresa

### 🌐 Endpoints de la API

#### Autenticación
```
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/refresh
```

#### Documentos Electrónicos
```
POST /api/sifen/documents                    # Crear documento
GET  /api/sifen/documents                    # Listar documentos
GET  /api/sifen/documents/{id}              # Ver documento específico
POST /api/sifen/documents/{id}/send         # Enviar a SIFEN
POST /api/sifen/documents/{id}/generate-kude # Generar KuDE
```

#### Eventos
```
POST /api/sifen/events                      # Crear evento
GET  /api/sifen/events                      # Listar eventos
POST /api/sifen/events/cancel/{documentId}  # Cancelar documento
POST /api/sifen/events/inutilize            # Inutilizar rango
```

#### NCE (Notas de Crédito)
```
POST /api/sifen/nce                         # Crear NCE
GET  /api/sifen/nce                         # Listar NCE
POST /api/sifen/nce/{id}/send              # Enviar NCE
```

#### Consultas
```
GET  /api/sifen/ruc/{ruc}                   # Consultar RUC
GET  /api/sifen/document/{cdc}              # Consultar documento por CDC
GET  /api/sifen/batch/{batchId}             # Consultar lote
```

### 🖥️ Dashboard Web

#### Acceso
```
http://tu-dominio/sifen/dashboard
```

#### Funcionalidades
- **Panel Principal**: Estadísticas y resumen
- **Documentos**: Gestión de documentos electrónicos
- **Eventos**: Administración de eventos
- **Empresas**: Configuración de empresa
- **Certificados**: Estado de certificados
- **Logs**: Registro de actividades

### 🧪 Pruebas del Sistema

#### Comando de Pruebas Completas
```bash
php artisan sifen:test
```

#### Pruebas Específicas
```bash
# Probar solo certificado
php artisan sifen:test --service=certificate

# Probar conectividad
php artisan sifen:test --service=ping

# Probar consulta RUC
php artisan sifen:test --service=ruc
```

### 📊 Cronograma de Tareas

```bash
# Procesar eventos pendientes (ejecutar cada hora)
php artisan sifen:process-events

# Limpiar archivos temporales (ejecutar diariamente)
php artisan sifen:cleanup

# Verificar estado de certificados (ejecutar semanalmente)
php artisan sifen:check-certificates
```

### 🔧 Configuración de Producción

#### 1. Certificado Oficial
- Obtener certificado P12 oficial de SET Paraguay
- Colocar en `storage/certificates/production.p12`
- Actualizar configuración de empresa

#### 2. Ambiente de Producción
```bash
# Cambiar a producción
php artisan sifen:company --update
# Seleccionar 'production' como ambiente
```

#### 3. Verificación Final
```bash
# Probar en producción
php artisan sifen:test

# Verificar certificado oficial
php artisan sifen:test --service=certificate
```

### 🚨 Solución de Problemas

#### Error de Certificado
```
# Verificar permisos
chmod 644 storage/certificates/*.p12

# Verificar password
php artisan sifen:test --service=certificate
```

#### Error de Conexión
```
# Verificar conectividad
ping sifen-test.set.gov.py

# Verificar DNS
nslookup sifen-test.set.gov.py
```

#### Error de Base de Datos
```
# Verificar migraciones
php artisan migrate:status

# Re-ejecutar migraciones
php artisan migrate:fresh --seed
```

### 📞 Soporte Técnico

#### Documentación Oficial
- [SIFEN SET Paraguay](https://www.set.gov.py/sifen)
- [Manual Técnico XML v150](https://ekuatia.set.gov.py/sifen/documentos)

#### Logs del Sistema
```bash
# Ver logs SIFEN
tail -f storage/logs/sifen.log

# Ver logs Laravel
tail -f storage/logs/laravel.log
```

#### Comandos de Diagnóstico
```bash
# Estado completo del sistema
php artisan sifen:setup

# Información de empresa
php artisan sifen:company

# Pruebas completas
php artisan sifen:test
```

### ✅ Lista de Verificación Final

- [ ] Dependencias instaladas
- [ ] Base de datos migrada
- [ ] Certificado P12 configurado
- [ ] Empresa configurada
- [ ] Pruebas de conexión exitosas
- [ ] Dashboard accesible
- [ ] API funcionando
- [ ] Logs configurados

### 🎯 Próximos Pasos

1. **Homologación**: Realizar pruebas con documentos reales en ambiente de test
2. **Certificación**: Solicitar certificación oficial con SET Paraguay
3. **Producción**: Migrar a ambiente productivo
4. **Monitoreo**: Implementar alertas y monitoreo continuo

---

**Sistema SIFEN Paraguay v1.0**  
*Implementación completa según especificaciones SET Paraguay*  
*Compatible con XML v150 y todas las funcionalidades requeridas*
