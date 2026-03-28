Quiero una auditoría técnica PROFUNDA de este sistema POS llamado "Pulpería Chebs".

NO apliques cambios todavía. Solo análisis.

Contexto del sistema:

* PHP + MySQL (XAMPP)
* Manejo de ventas, productos, lotes, inventario
* Uso de FIFO en lotes
* Control de stock por lotes
* Roles: admin y empleado
* Historial de ventas y turnos

Audita específicamente:

1. ARQUITECTURA

* Organización de controladores, modelos y vistas
* Uso de funciones globales vs estructura mantenible
* Separación de responsabilidades

2. BASE DE DATOS

* Integridad entre productos, lotes y movimientos_inventario
* Riesgo de desincronización de stock
* Uso correcto de claves foráneas
* Posibles inconsistencias en FIFO

3. LÓGICA DE INVENTARIO (CRÍTICO)

* Flujo completo de:
  compra → lote → venta → movimiento
* Riesgo de:

  * stock negativo
  * lotes huérfanos
  * ventas sin lote válido
  * reactivación incorrecta de lotes

4. SEGURIDAD

* SQL Injection
* Validación de inputs
* Control de acceso por rol (admin vs empleado)
* Manipulación directa por URL

5. ERRORES Y TRANSACCIONES

* Uso (o ausencia) de transacciones en operaciones críticas
* Qué pasa si falla una venta a mitad de proceso
* Riesgo de datos corruptos

6. RENDIMIENTO

* Consultas pesadas
* Falta de índices
* Escalabilidad con +1000 productos y ventas

7. BUGS LÓGICOS

* Casos donde el sistema puede fallar en producción
* Ejemplo: eliminar, devolver, anular ventas

8. ARCHIVOS CRÍTICOS
   Identifica los archivos más peligrosos del sistema
   (modelos, controladores, funciones clave)

---

ENTREGA:

* Resumen ejecutivo (estado real del sistema)
* Hallazgos CRÍTICOS (alto riesgo)
* Hallazgos IMPORTANTES
* Hallazgos MENORES
* Archivos exactos involucrados
* Explicación técnica clara
* Cómo reproducir cada problema
* Impacto real en negocio (ventas, dinero, stock)
* Orden exacto de corrección (prioridad real)

NO modifiques nada.
Solo auditoría profesional.

