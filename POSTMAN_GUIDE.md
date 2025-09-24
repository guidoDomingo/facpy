# 📮 Guía Postman - Facturación Paraguay SIFEN

## 🚀 Configuración Rápida

### 1. Importar Colección y Environment

1. **Abrir Postman**
2. **Import** → **Upload Files**
3. Seleccionar archivos:
   - `postman_collection_paraguay.json`
   - `postman_environment_paraguay.json`
4. **Seleccionar Environment**: `Paraguay SIFEN - Local`

### 2. Variables de Environment

Las siguientes variables están preconfiguradas:

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `base_url` | `http://localhost:8000` | Servidor Laravel local |
| `test_email` | `admin@paraguay.com` | Usuario de prueba |
| `test_password` | `password123` | Password de prueba |
| `test_ruc_emisor` | `80123456-7` | RUC empresa demo |
| `test_ruc_receptor` | `80024242-1` | RUC cliente demo |
| `current_date` | `2025-09-24` | Fecha actual |

## ✅ Secuencia de Testing

### Paso 1: Verificar Sistema
```
🔍 Tests del Sistema
  ├── Info del Sistema (GET /api/test/system)
  └── Test Conexión SIFEN (GET /api/test/connection)
```

### Paso 2: Autenticación
```
🔐 Autenticación
  ├── Login (POST /api/login) → Guarda token automáticamente
  ├── Mi Perfil (POST /api/me)
  └── Logout (POST /api/logout)
```

### Paso 3: Gestión Empresas
```
🏢 Empresas Paraguay
  ├── Listar Empresas (GET /api/companies) → Guarda company_id
  ├── Crear Empresa Paraguay (POST /api/companies)
  └── Ver Empresa (GET /api/companies/{{company_id}})
```

### Paso 4: Facturación SIFEN
```
📄 Facturación SIFEN
  ├── Generar XML Factura (POST /api/invoices/xml)
  ├── Enviar Factura a SIFEN (POST /api/invoices/send) → Guarda CDC
  ├── Consultar Estado Documento (POST /api/invoices/status)
  ├── Generar Reporte (POST /api/invoices/report)
  └── Configuración SIFEN (GET /api/invoices/config)
```

### Paso 5: Ejemplos Paraguay
```
📊 Ejemplos Específicos Paraguay
  ├── Factura IVA Mixto (10%, 5%, Exento)
  ├── Autofactura Electrónica (Tipo 04)
  └── Nota de Crédito (Tipo 05)
```

## 🧪 Tests Automáticos Incluidos

Cada request incluye tests automáticos que verifican:

- ✅ **Status codes** correctos (200, 201, etc.)
- ✅ **Estructura JSON** de respuestas
- ✅ **Datos Paraguay** (RUC formato, departamentos, CDC)
- ✅ **Tokens JWT** y autenticación
- ✅ **Variables automáticas** (token, company_id, cdc)

## 📋 Ejemplos de Uso

### 1. Factura Simple IVA 10%
```json
{
  "company_id": 1,
  "tipoDocumento": "01",
  "serie": "001", 
  "numero": "0000001",
  "fechaEmision": "2025-09-24",
  "receptor": {
    "ruc": "80024242-1",
    "razonSocial": "Cliente Paraguay SA",
    "direccion": "Av. Test 123, Asunción"
  },
  "items": [
    {
      "descripcion": "Producto Paraguay",
      "cantidad": 1,
      "precioUnitario": 110000,
      "tipoIva": "10"
    }
  ]
}
```

### 2. Factura IVA Mixto
```json
{
  "items": [
    {
      "descripcion": "Arroz (IVA 10%)",
      "cantidad": 10,
      "precioUnitario": 22000,
      "tipoIva": "10"
    },
    {
      "descripcion": "Leche (IVA 5%)", 
      "cantidad": 20,
      "precioUnitario": 6300,
      "tipoIva": "5"
    },
    {
      "descripcion": "Medicamentos (Exento)",
      "cantidad": 5,
      "precioUnitario": 15000,
      "tipoIva": "0"
    }
  ]
}
```

### 3. Autofactura (Tipo 04)
```json
{
  "tipoDocumento": "04",
  "items": [
    {
      "descripcion": "Servicios profesionales",
      "cantidad": 1,
      "precioUnitario": 500000,
      "tipoIva": "10"
    }
  ],
  "observaciones": "Autofactura según Art. 105 Ley 125/91"
}
```

## 🔍 Validaciones Paraguay

### RUC Format
- ✅ **Formato**: `########-#` (8 dígitos + guión + 1 dígito)
- ✅ **Ejemplo**: `80123456-7`

### CDC (Código de Control)
- ✅ **Longitud**: 44 dígitos exactos
- ✅ **Ejemplo**: `01080123456700120250924001000000011234567890`

### Departamentos
- ✅ **11**: CAPITAL
- ✅ **15**: CAAGUAZÚ  
- ✅ **17**: ITAPÚA

### Tipos IVA
- ✅ **10%**: Tasa estándar
- ✅ **5%**: Tasa reducida
- ✅ **0%**: Exento

## 🐛 Debugging

### Ver Logs en Postman
1. Abrir **Console** (View → Show Postman Console)
2. Los requests muestran logs automáticos:
   - Status codes
   - Response times  
   - JSON responses (primeros 500 chars)

### Variables Dinámicas
- `{{token}}` - Se actualiza automáticamente en login
- `{{company_id}}` - Se obtiene de listar empresas
- `{{cdc}}` - Se guarda al enviar facturas

### Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| 401 Unauthorized | Token expirado | Hacer login nuevamente |
| 422 Validation Error | Datos inválidos | Verificar formato RUC, fechas |
| 500 Server Error | Error servidor | Ver logs Laravel |

## 🔧 Configuración Avanzada

### Usar Diferentes Ambientes
1. Duplicar environment
2. Cambiar `base_url` para staging/producción
3. Actualizar `sifen_test_url` por `sifen_prod_url`

### Scripts Pre-request
Algunos requests incluyen scripts que:
- Calculan fechas automáticamente
- Generan números de documento únicos
- Validan formatos antes del envío

## 📊 Reportes y Monitoreo

### Runner Collection
1. **Collection Runner** → Seleccionar colección
2. **Run Order**: Seguir secuencia recomendada
3. **Iterations**: 1 para testing manual
4. **Data**: Usar CSV para testing masivo

### Newman (CLI)
```bash
newman run postman_collection_paraguay.json -e postman_environment_paraguay.json
```

---

## 🎯 Quick Start (5 minutos)

1. **Import** ambos archivos JSON en Postman
2. **Select Environment**: "Paraguay SIFEN - Local"  
3. **Run**: "Info del Sistema" ✅
4. **Run**: "Login" ✅ (guarda token automáticamente)
5. **Run**: "Listar Empresas" ✅ (guarda company_id)
6. **Run**: "Generar XML Factura" ✅
7. **🎉 Sistema funcionando!**

**🇵🇾 Ready to test Paraguay SIFEN integration!**
