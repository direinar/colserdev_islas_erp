---
paths:
  - 'app/Services/Nomina/**'
---

# Nomina

## Nómina del mes y prestaciones (plantilla NOMINA PARA PROGRAMACION)
NominaLiquidacionService replica la hoja NOMINA: básico = sueldo/30×días; vacaciones = sueldo/30×(disfrutadas+dinero); recargos = horas × valor hora del PARÁMETRO (salario mínimo/horas_mensuales redondeado a pesos, $7.959 en 2026; no el sueldo del empleado); base aportes = básico+vacaciones+recargos; auxilio = aux/30×días; salud y pensión del empleado y aportes del empleador sobre la base; provisiones sobre el total devengado y los intereses sobre la provisión de cesantías (así lo hace la plantilla). Todo se redondea a pesos y se guarda en nomina_liquidacion_empleados; APROBADA queda fija y solo esas alimentan acumulados, prima, consolidación y certificados (PrestacionesSocialesService). Días con DAYS360 europeo +1 (dias360). Liquidación de contrato: base = promedio de 12 meses liquidados (ingreso salarial llevado a 30 días) o sueldo+auxilio sin historial; cesantías base/360×días año, intereses ×12%/360×días año, prima base/360×días semestre, vacaciones base/720×días año. Cuentas de la contabilización en nomina_cuentas_contables (la de bancos no venía en la plantilla). Datos del empleador en config/nomina.php (NOMINA_* en .env).
