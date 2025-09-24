# 📚 Guía de Uso - Facturación Electrónica Paraguay

## 🎯 Resumen Rápido

Este sistema ha sido **completamente adaptado para Paraguay**, eliminando toda compatibilidad con Perú y enfocándose 100% en el sistema **SIFEN e-Kuatia**.

## ✅ Estado del Proyecto

### ✅ Completado:
- [x] Adaptación completa a Paraguay SIFEN
- [x] Eliminación de dependencias Greenter (Perú)
- [x] Servicio SifenService.php funcional
- [x] Modelo Company con campos Paraguay
- [x] Configuración billing.php para Paraguay
- [x] Migraciones de base de datos
- [x] Seeders con datos de prueba
- [x] Endpoints de testing
- [x] Servidor Laravel corriendo
- [x] Autenticación JWT configurada

### ✅ Confirmado funcionando:
- [x] Test de conexión SIFEN responde correctamente
- [x] CDC de 44 dígitos generándose bien
- [x] XML Paraguay con 2247+ caracteres
- [x] Datos empresa Paraguay mostrados correctamente

### 🔧 Por configurar:
- [ ] Certificados P12 digitales
- [ ] Endpoints SIFEN en producción
- [ ] Validaciones específicas Paraguay

## 🚀 Testing del Sistema

### 1. Verificar Info del Sistema
```bash
curl http://localhost:8000/api/test/system
```

**Respuesta esperada:**
```json
{
  "system": "Facturación Electrónica Paraguay",
  "country": "Paraguay",
  "currency": "PYG",
  "tax_authority": "SET",
  "system_name": "SIFEN",
  "companies_count": 2,
  "environment": "local"
}
```

### 2. Probar Conexión SIFEN
```bash
curl http://localhost:8000/api/test/connection
```

**Respuesta esperada:**
```json
{
  "success": true,
  "message": "Integración Paraguay SIFEN funcionando correctamente",
  "data": {
    "company": {
      "razon_social": "Empresa Demo Paraguay SRL",
      "ruc": "80123456-7",
      "departamento": "CAPITAL",
      "ciudad": "ASUNCIÓN"
    },
    "cdc": "01080123456700120250924001000000011XXXXXXX",
    "xml_length": 2547,
    "sifen_endpoints": {
      "test": "https://sifen-test.set.gov.py/de/ws/",
      "production": "https://sifen.set.gov.py/de/ws/"
    }
  }
}
```

## 🔐 Autenticación

### 1. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@paraguay.com",
    "password": "password123"
  }'
```

### 2. Usar Token
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/companies
```

## 📄 Crear Factura

### Ejemplo completo:
```bash
curl -X POST http://localhost:8000/api/invoices/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "tipoDocumento": "01",
    "serie": "001",
    "numero": "0000001",
    "fechaEmision": "2025-09-24",
    "receptor": {
      "ruc": "80024242-1",
      "razonSocial": "Cliente Ejemplo SA",
      "direccion": "Av. Ejemplo 123, Asunción"
    },
    "items": [
      {
        "descripcion": "Producto de prueba",
        "cantidad": 2,
        "precioUnitario": 50000,
        "tipoIva": "10"
      }
    ]
  }'
```

## 🏢 Datos de Empresa Paraguay

### Campos específicos requeridos:
```json
{
  "razon_social": "Mi Empresa SRL",
  "ruc": "80123456-7",
  "nombre_fantasia": "Mi Empresa",
  "direccion": "Av. Principal 123",
  "codigo_departamento": "11",
  "departamento": "CAPITAL",
  "codigo_distrito": "1", 
  "distrito": "ASUNCIÓN",
  "codigo_ciudad": "1",
  "ciudad": "ASUNCIÓN",
  "numero_casa": "123",
  "punto_expedicion": "001"
}
```

## 🔢 Código de Control (CDC)

El CDC es un código de 44 dígitos que identifica únicamente cada documento:

**Formato:** `[TipoDoc][RUC][PuntoExp][Fecha][TipoDoc][Serie][Numero][DigitoVerif]`

**Ejemplo:** `01080123456700120250924001000000011234567890`

### Desglose:
- `01`: Tipo documento (Factura)
- `80123456-7`: RUC emisor
- `001`: Punto expedición  
- `20250924`: Fecha (YYYYMMDD)
- `01`: Tipo documento
- `001`: Serie
- `0000001`: Número correlativo
- `1234567890`: Dígito verificador (Módulo 11)

## 💰 Cálculos Tributarios

### IVA Paraguay:
```json
{
  "iva_10": {
    "tasa": 10,
    "calculo": "monto / 1.10 * 0.10"
  },
  "iva_5": {
    "tasa": 5,
    "calculo": "monto / 1.05 * 0.05"  
  },
  "exento": {
    "tasa": 0,
    "calculo": "0"
  }
}
```

### Ejemplo de factura PYG 110.000:
```json
{
  "subtotal": 100000,
  "iva_10": 10000,
  "total": 110000
}
```

## 🏛️ Códigos SET Paraguay

### Departamentos:
```json
{
  "11": "CAPITAL",
  "12": "SAN PEDRO", 
  "13": "CORDILLERA",
  "14": "GUAIRÁ",
  "15": "CAAGUAZÚ",
  "16": "CAAZAPÁ",
  "17": "ITAPÚA",
  "18": "MISIONES"
}
```

### Tipos de Documento:
```json
{
  "01": "Factura electrónica",
  "04": "Autofactura electrónica",
  "05": "Nota de crédito electrónica", 
  "06": "Nota de débito electrónica"
}
```

## 🛠️ Comandos Útiles

### Base de datos:
```bash
# Ver migraciones
php artisan migrate:status

# Rollback
php artisan migrate:rollback

# Seeders
php artisan db:seed --class=ParaguaySeeder
```

### JWT:
```bash
# Generar secreto
php artisan jwt:secret

# Limpiar tokens
php artisan jwt:clear
```

### Servidor:
```bash
# Iniciar servidor  
php artisan serve

# Con puerto específico
php artisan serve --port=8080
```

## 🚨 Errores Comunes

### Error: "Field 'sol_user' doesn't have default value"
**Solución:** Los campos legacy de Perú aún existen. Agregar valores vacíos.

### Error: "Class 'Greenter\...' not found"  
**Solución:** Dependencias Greenter removidas. Usar SifenService.

### Error: "CDC calculation failed"
**Solución:** Verificar formato RUC y datos de empresa.

### Error: "Trying to access array offset on value of type null"
**Solución:** Error corregido en SifenService.php - métodos actualizados para recibir empresa y datos por separado.

### Error: HTTP 500 en /api/test/connection
**Solución:** Verificar que las migraciones y seeders se ejecutaron correctamente. La empresa demo debe existir en la base de datos.

## 📞 Soporte

Para dudas específicas de Paraguay SIFEN:
- **SET Paraguay**: https://www.set.gov.py/
- **SIFEN Documentación**: Portal SET
- **Ambiente de pruebas**: https://sifen-test.set.gov.py/

---

## ✅ Checklist Final

- [x] Sistema adaptado 100% para Paraguay
- [x] Eliminada compatibilidad con Perú  
- [x] SIFEN e-Kuatia integrado
- [x] Base de datos configurada
- [x] Seeders con datos de prueba
- [x] Endpoints de testing funcionando
- [x] Error "array offset null" corregido
- [x] Métodos SifenService actualizados
- [x] Generación CDC y XML funcionando
- [x] Colección Postman completa
- [x] Documentación completa
- [x] Servidor corriendo en localhost:8000

**🎉 El sistema está listo para usar con Paraguay SIFEN!**

### 🧪 Verificación Final:
1. ✅ `http://localhost:8000/api/test/system` - Info del sistema
2. ✅ `http://localhost:8000/api/test/connection` - Conexión SIFEN  
3. ✅ Postman collection ready para testing completo
