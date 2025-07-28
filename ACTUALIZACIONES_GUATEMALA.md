# 🇬🇹 Actualizaciones para Guatemala - Car Wash Emanuel

## Fecha: 29 de Julio, 2025 - 17:00 hrs

### 📋 **Resumen de Cambios**

Este archivo contiene todas las actualizaciones realizadas para adaptar el sistema Car Wash Emanuel a Guatemala, incluyendo:

- ✅ **Moneda**: Cambio de Colones (₡) a Quetzales Guatemaltecos (Q)
- ✅ **Teléfonos**: Adaptación al código +502 y formato guatemalteco
- ✅ **Validaciones**: Números de teléfono válidos para Guatemala
- ✅ **Precios**: Actualizados a valores apropiados en Quetzales

---

## 💰 **Cambios de Moneda**

### **Antes:**
- Símbolo: ₡ (Colones)
- Formato: ₡25.00

### **Después:**
- Símbolo: Q (Quetzales)
- Formato: Q25.00

### **Precios Actualizados:**
- Lavado Básico: Q25.00
- Lavado Completo: Q45.00
- Lavado Premium: Q65.00
- Encerado: Q85.00
- Lavado y Encerado: Q120.00
- Detallado Completo: Q200.00

---

## 📞 **Cambios de Teléfono**

### **Formato Anterior:**
- Placeholder: +506 8888-1234
- Validación: Básica

### **Formato Guatemalteco:**
- Placeholder: +502 1234-5678
- Código de país: +502
- Formato automático: +502 1234-5678

### **Números Válidos:**
- ✅ `12345678` → se convierte a `+502 1234-5678`
- ✅ `50212345678` → se convierte a `+502 1234-5678`
- ✅ `+50212345678` → se convierte a `+502 1234-5678`
- ✅ `23456789` → se convierte a `+502 2345-6789`

### **Validación:**
- Números que inician con 2-7 (válidos en Guatemala)
- 8 dígitos después del código de país
- Acepta con o sin código +502

---

## 🔧 **Archivos Modificados**

### **1. includes/functions.php**
- ✅ `formatCurrency()` - Actualizada para Quetzales (Q)
- ✅ `validatePhone()` - Validación para números guatemaltecos
- ✅ `formatPhone()` - Nueva función para formatear teléfonos

### **2. clients.php**
- ✅ Placeholder actualizado a +502
- ✅ Texto de ayuda para formato guatemalteco
- ✅ Formateo automático en visualización
- ✅ Evento onblur para formateo automático

### **3. includes/footer.php**
- ✅ `formatPhoneInput()` - Nueva función JavaScript
- ✅ Formateo automático de teléfonos en tiempo real

### **4. Nuevos Archivos:**
- ✅ `actualizar_guatemala.php` - Script de actualización
- ✅ `verificar_usuarios.php` - Verificación del sistema
- ✅ `ACTUALIZACIONES_GUATEMALA.md` - Esta documentación

---

## 🚀 **Instalación y Uso**

### **1. Descomprimir el ZIP:**
```bash
unzip "Carwash Actualizado 17 horas.zip"
```

### **2. Copiar a XAMPP (Mac):**
```bash
sudo cp -r * /Applications/XAMPP/xamppfiles/htdocs/carwash-emanuel/
```

### **3. Ejecutar Script de Actualización:**
```
http://localhost/carwash-emanuel/actualizar_guatemala.php
```

### **4. Acceder al Sistema:**
```
http://localhost/carwash-emanuel/login.php
```

**Credenciales:**
- Usuario: `admin`
- Contraseña: `admin123`

---

## 📱 **Ejemplos de Uso**

### **Entrada de Teléfonos:**
Los usuarios pueden escribir cualquiera de estos formatos:
- `12345678`
- `502 1234 5678`
- `+502 1234 5678`
- `50212345678`

**Todos se formatearán automáticamente a:** `+502 1234-5678`

### **Visualización de Precios:**
- En reportes: `Q1,250.00`
- En servicios: `Q45.00`
- En dashboard: `Q3,500.00`

---

## ⚙️ **Funciones Técnicas**

### **formatCurrency($amount)**
```php
// Antes: return '₡' . number_format($amount, 2);
// Después: return 'Q' . number_format($amount, 2);
```

### **validatePhone($phone)**
```php
// Valida números guatemaltecos
return preg_match('/^(\+502|502)?[2-7][0-9]{7}$/', $phone);
```

### **formatPhone($phone)**
```php
// Formatea a +502 1234-5678
// Acepta múltiples formatos de entrada
```

---

## 🛠️ **Archivos de Prueba Incluidos**

- `test_simple.php` - Prueba básica de PHP
- `debug_seguro.php` - Diagnóstico del sistema
- `verificar_usuarios.php` - Verificación de usuarios
- `actualizar_guatemala.php` - Script de actualización

**Nota:** Estos archivos se pueden eliminar después de la instalación.

---

## 📞 **Soporte**

Si necesitas ayuda adicional o personalizaciones:
- Todos los cambios están documentados en el código
- Las funciones son reutilizables y modulares
- El sistema mantiene compatibilidad con datos existentes

---

## 🎯 **Próximas Mejoras Sugeridas**

- [ ] Reportes específicos para Guatemala (impuestos IVA)
- [ ] Integración con facturación guatemalteca
- [ ] Múltiples sucursales
- [ ] Notificaciones SMS con números guatemaltecos
- [ ] Backup automático de datos

---

**¡Sistema Car Wash Emanuel totalmente adaptado para Guatemala! 🇬🇹**