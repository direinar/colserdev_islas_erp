# Checklist — Gestión de Combustibles

> Elaborado a partir del documento **“OBSERVACIONES A: GESTION DE COMBUSTIBLES”**. Se conserva la terminología y el alcance de las observaciones fuente. fileciteturn0file0L2-L17

## 1. Información de ventas del turno

### Ventas según cierres de IAPROPIADA
- [ ] Hacer que el **No. de turno** salga automáticamente con el siguiente número.
- [ ] Corregir el problema por el cual el turno no se guarda, no se puede volver a llamar/revisar o el programa aborta al guardar.
- [ ] Alinear las cifras a la **derecha**.
- [ ] En **GALONES**, usar punto como separador en vez de coma.
- [ ] Corregir **VALOR TOTAL DEL MÓDULO** para que no muestre dígitos innecesarios y aplique a **ambos productos**.
- [ ] Evitar pérdida de información cuando el programa se cae, especialmente al seleccionar **NUEVO**.
- [ ] Evitar que al guardar el turno se salga del sistema y se pierda la información.

### Ventas Electrolinera
- [ ] Dejar proyectada/planeada la opción **PROGRAMADO: VENTAS ELECTROLINERA**.
- [ ] Ubicarla después de **VENTA SEGÚN LECTURAS**.
- [ ] Implementarla de forma similar a **LECTURA ELECTRÓNICA**.
- [ ] Incluir **mangueras**, **lectura inicial**, **lectura final**, **Precio vatio** y **total venta ELECTROLINERA**.
- [ ] Suprimir **VENTAS SEGÚN CIERRES DE IAPROPIADA**, porque no aplica.
- [ ] Dejar proyectada **ELECTROLINERA** en el **RESUMEN DE LO VENDIDO EN ESTE TURNO**. fileciteturn0file0L13-L20

## 2. Venta de canastilla
- [ ] Cambiar el nombre de **VENTA DE URREA Y LUBRICANTES - CONTADO Y CRÉDITO** a **VENTA DE CANASTILLA – CONTADO Y CRÉDITO**.
- [ ] Agregar desplegable para buscar/seleccionar el lubricante. fileciteturn0file0L20-L22

## 3. Traslado a sobrante y traslado a faltante
- [ ] Separar/formular el módulo según corresponda a **faltante** o **sobrante**.
- [ ] Hacer que la información viaje al **RESUMEN**. fileciteturn0file0L23-L24

## 4. Nombre del vendedor
- [ ] Amarrar **NOMBRE DEL VENDEDOR** con el usuario que está llenando la planilla. fileciteturn0file0L25-L26

## 5. Revisión de planillas
- [ ] Mostrar **REVISADO** en color **ROJO** cuando no se haya revisado.
- [ ] Mostrar **REVISADO** en color **VERDE** cuando ya se haya revisado.
- [ ] Permitir que el color verde solamente lo pueda colocar **ADMINISTRACIÓN**.
- [ ] Crear un control de **planillas pendientes de revisión**. fileciteturn0file0L27-L30

## 6. Formas de pago — Consignaciones y descuentos

### Consignaciones
- [ ] Agregar ventana/control para buscar un **nuevo renglón**.
- [ ] Separar el módulo de **CONSIGNACIONES** del módulo de **DESCUENTOS**.

### Descuentos
- [ ] Agregar ventana/control para buscar un **nuevo renglón**.
- [ ] Separar los módulos de **DESCUENTOS** y **CARTERA**. fileciteturn0file0L31-L38

## 7. TC, QR, Nequi, Daviplata y otras transferencias
- [ ] Distinguir **Datáfono No. 1 de TC** de **Datáfono No. 1 de QR** y aplicar la diferenciación a todos los datáfonos.
- [ ] Incluir **DAVIVIENDA**, **NEQUI** y **BANCOLOMBIA**.
- [ ] Permitir crear más bancos, datáfonos y opciones como Nequi.
- [ ] Aplicar separación de miles y millones con **punto**, no con coma, en **TODO EL SISTEMA**.
- [ ] Cambiar el título a **TC, QR, NEQUI, DAVIPLATA Y OTRAS TRANSFERENCIAS**.
- [ ] Aplicar los cambios también en **RESUMEN DE LO RECIBIDO EN EL TURNO**.
- [ ] Eliminar **TRANSFERENCIA BANCOLOMBIA** tanto del módulo como del resumen. fileciteturn0file0L39-L48

## 8. Todos los medios de pago — distribución
- [ ] Dejar visibles **3 columnas** de medios de pago.
- [ ] Columna 1: **CONSIGNACIONES**.
- [ ] Columna 2: **DESCUENTOS**.
- [ ] Columna 3: **CARTERA CREDITO DIRECTO**.
- [ ] Reducir las casillas de **No. de factura** y **valor** de cartera para igualar el tamaño de los demás módulos.
- [ ] Mantener una distribución simétrica.
- [ ] Organizar **3 columnas de 3 módulos cada una**.
- [ ] Ubicar **RESUMEN DE PAGOS** únicamente en el centro al final. fileciteturn0file0L49-L54

## 9. Transferencias / Puntos redimidos
- [ ] Eliminar **TRANSFERENCIAS BANCOLOMBIA** de este módulo.
- [ ] Mantener únicamente **PUNTOS REDIMIDOS**.
- [ ] Cambiar el nombre del módulo a **PUNTOS REDIMIDOS**. fileciteturn0file0L55-L57

## 10. Cartera — Crédito Directo
- [ ] Permitir seleccionar el **cliente**.
- [ ] Impedir avanzar si no se ha seleccionado un cliente. fileciteturn0file0L58-L60

## 11. Recaudos por islas
- [ ] Permitir seleccionar el **cliente**.
- [ ] Impedir avanzar si no se ha seleccionado un cliente.
- [ ] Cambiar el nombre a **RECAUDOS POR ISLAS**. fileciteturn0file0L62-L65

## 12. Recaudos por administración
- [ ] Agregar un desplegable en **Banco o Caja** para definir los **BANCOS o CAJA**.
- [ ] Eliminar la casilla **RESPONSABLE**, porque no se requiere.
- [ ] Alinear este módulo con **Cartera** y **Recaudos** para mantener simetría. fileciteturn0file0L66-L69

## 13. Validación final
- [ ] Probar creación de un nuevo turno.
- [ ] Probar guardado del turno.
- [ ] Probar recuperación/consulta de un turno guardado.
- [ ] Verificar que no se pierda información ante errores o reinicio del formulario.
- [ ] Verificar cálculos y totales de ambos productos.
- [ ] Verificar formato de galones y valores numéricos.
- [ ] Verificar que sobrantes/faltantes lleguen al resumen.
- [ ] Verificar correspondencia entre usuario y vendedor.
- [ ] Verificar permisos de revisión por Administración.
- [ ] Verificar control de planillas pendientes.
- [ ] Verificar búsqueda de nuevos renglones en consignaciones y descuentos.
- [ ] Verificar selección obligatoria de cliente en cartera y recaudos.
- [ ] Verificar bancos, datáfonos y medios configurables.
- [ ] Verificar separación de miles y millones con punto en todo el sistema.
- [ ] Verificar simetría visual de los módulos de medios de pago.
- [ ] Verificar que los cambios también aparezcan en los respectivos resúmenes.

## Estado

- **Pendiente:** todos los ítems al iniciar la implementación.
- Marcar cada elemento con `[x]` cuando esté implementado y validado.
