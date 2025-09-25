# Guía Oficial de Registro SIFEN Paraguay

## 📋 Pasos para obtener credenciales SIFEN (según DNIT oficial)

### 1. Documentos oficiales requeridos:

#### Documentación técnica:
- **Manual Técnico v150**: https://www.dnit.gov.py/documents/20123/420592/Manual+T%C3%A9cnico+Versi%C3%B3n+150.pdf
- **Guía de Habilitación**: https://www.dnit.gov.py/documents/20123/424160/Guias+paso+a+paso+-+Habilitaci%C3%B3n+Facturador+Electronico.pdf
- **Guía de Pruebas**: https://www.dnit.gov.py/documents/20123/424160/Gu%C3%ADa+de+Pruebas+Fase+de+Voluntariedad+Abierta+para+el+Sistema+Integrado+de+Facturaci%C3%B3n+Electr%C3%B3nica+Nacional.pdf

### 2. Contactos oficiales DNIT:

- **Email principal**: facturacionelectronica@dnit.gov.py
- **Teléfono**: (021) 729 7000 Opción 2
- **Mesa de Ayuda**: https://servicios.set.gov.py/eset-publico/EnvioMailSetIService.do
- **Horario**: 07:30 a 12:00 hs. y de 13:00 a 16:00 hs.

### 3. Requisitos para desarrollo:

#### Para ambiente de pruebas:
1. **RUC válido** registrado en Paraguay
2. **Certificado digital** de entidad autorizada (ej: DÍGITO)
3. **Solicitud formal** a DNIT especificando:
   - Tipo de integración (API/Sistema propio)
   - Volumen estimado de documentos
   - Cronograma de implementación

#### Certificados digitales autorizados:
- **DÍGITO (Documenta S.A.)**
  - Email: comercialdigito@documenta.com.py
  - WhatsApp: 0976 538 954
- **ACRAIZ** (Autoridad Certificadora Raíz Paraguay)

### 4. Proceso de habilitación:

1. **Fase 1**: Registro en portal SIFEN
2. **Fase 2**: Solicitud de ambiente de pruebas
3. **Fase 3**: Desarrollo y testing
4. **Fase 4**: Certificación y habilitación para producción

### 5. Enlaces importantes:

- **Portal e-Kuatia**: https://www.dnit.gov.py/web/e-kuatia/ekuatia
- **Consulta de comprobantes**: https://www.dnit.gov.py/web/e-kuatia/consulta-de-comprobantes
- **Portal principal DNIT**: https://www.dnit.gov.py/

### 6. Email modelo para solicitar credenciales:

```
Para: facturacionelectronica@dnit.gov.py
Asunto: Solicitud de ambiente de pruebas SIFEN - [Nombre empresa]

Estimados,

Somos [Nombre de empresa/desarrollador] con RUC [Tu RUC] y solicitamos 
acceso al ambiente de pruebas del Sistema SIFEN e-Kuatia para desarrollar 
una integración de facturación electrónica.

Datos de la solicitud:
- RUC: [Tu RUC]
- Razón social: [Tu razón social]
- Contacto técnico: [Tu email]
- Teléfono: [Tu teléfono]
- Tipo de integración: Sistema web personalizado
- Volumen estimado: [X] documentos por mes
- Cronograma: [Tu cronograma]

Adjuntamos:
- Certificado digital válido
- Documentación de la empresa

Quedamos atentos a sus instrucciones.

Saludos cordiales,
[Tu nombre]
[Tu cargo]
[Tu empresa]
```

## ✅ Estado actual del proyecto:

- ✅ Sistema de facturación desarrollado
- ✅ Generación de XML SIFEN válido
- ✅ Conexión exitosa al servidor real SIFEN
- ✅ Certificado raíz Paraguay configurado
- ⏳ Pendiente: Certificado de empresa y credenciales oficiales