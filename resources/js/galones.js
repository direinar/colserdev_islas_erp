// Que hace este codigo? --- IGNORE ---
document.addEventListener('DOMContentLoaded', () => {// Cuando el DOM esté completamente cargado, se ejecuta esta función
    const parseGalones = value => {// Esta función toma un valor de entrada y lo convierte en un número de galones, manejando formatos con puntos y comas
        return parseFloat(// Convierte el valor a un número de punto flotante
            value
                .toString()// Convierte el valor a una cadena de texto
                .replace(/\./g, '')// Elimina todos los puntos del valor (usados como separadores de miles)
                .replace(',', '.')// Reemplaza la coma por un punto (usado como separador decimal)
        );
    };

    const formatGalones = number => {// Esta función formatea un número de galones para mostrarlo con tres decimales y separadores de miles según la convención colombiana
        if (isNaN(number)) {// Si el número no es un valor numérico válido, devuelve una cadena vacía
            return '';// Devuelve una cadena vacía si el número no es válido
        }

        return number.toLocaleString('es-CO', {// Formatea el número según la configuración regional de Colombia
            minimumFractionDigits: 3,// Asegura que siempre se muestren al menos 3 dígitos decimales
            maximumFractionDigits: 3// Asegura que no se muestren más de 3 dígitos decimales
        });
    };

    const formatMoney = number => {// Esta función formatea un número como una cantidad de dinero, sin decimales y con separadores de miles según la convención colombiana
        if (isNaN(number)) {// Si el número no es un valor numérico válido, devuelve una cadena vacía
            return '';// Devuelve una cadena vacía si el número no es válido
        }

        return number.toLocaleString('es-CO', {// Formatea el número según la configuración regional de Colombia
            minimumFractionDigits: 0,// Asegura que no se muestren dígitos decimales
            maximumFractionDigits: 0// Asegura que no se muestren dígitos decimales
        });
    };

    const formatMoneyWithDecimals = (number, decimals = 3) => {
        if (isNaN(number)) {
            return '';
        }

        return number.toLocaleString('es-CO', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    };

    const parseCurrency = value => {
        return parseFloat(
            value
                .toString()
                .replace(/\./g, '')
                .replace(',', '.')
        );
    };

    const updateGrandTotal = () => {// Esta función calcula la suma total de los valores en los campos con la clase 'valor-total' y actualiza el campo que muestra el total general del turno
        const totalField = document.querySelector('.ventas-total-turno');// Selecciona el elemento que muestra el total general del turno

        if (!totalField) {// Si no se encuentra el elemento para mostrar el total, la función termina sin hacer nada
            return;// Si no se encuentra el elemento para mostrar el total, se sale de la función
        }

        let sum = 0;// Inicializa la variable para acumular la suma total

        document.querySelectorAll('.valor-total').forEach(input => {// Itera sobre todos los campos con la clase 'valor-total' para calcular la suma total
            const value = input.value// Toma el valor del campo de entrada
                .toString()// Convierte el valor a una cadena de texto
                .replace(/\./g, '')// Elimina todos los puntos del valor (usados como separadores de miles)
                .replace(',', '.');// Reemplaza la coma por un punto (usado como separador decimal)

            const parsed = parseFloat(value);// Convierte el valor a un número de punto flotante

            if (!isNaN(parsed)) {// Si el valor convertido es un número válido, se suma al total acumulado
                sum += parsed;// Si el valor convertido es un número válido, se agrega a la suma total
            }
        });

        totalField.textContent = formatMoney(sum);// Actualiza el contenido del campo que muestra el total general con el valor formateado como dinero
    };

    const updateTirillasTotals = () => {// Esta función calcula los totales de galones para corriente y ACPM, y actualiza los campos correspondientes en la sección de tirillas
        const totalCorriente = Array.from(document.querySelectorAll('.galones-cte'))// Selecciona todos los campos de entrada con la clase 'galones-cte' y calcula la suma total de galones para corriente
            .reduce((sum, input) => {// Utiliza el método reduce para acumular la suma total de galones para corriente
                const value = parseGalones(input.value);// Convierte el valor del campo de entrada a un número de galones utilizando la función parseGalones
                return sum + (isNaN(value) ? 0 : value);// Si el valor convertido no es un número válido, se suma 0; de lo contrario, se suma el valor convertido
            }, 0);// El segundo argumento '0' es el valor inicial para la suma total de galones para corriente

        const totalAcpms = Array.from(document.querySelectorAll('.galones-acpm'))// Selecciona todos los campos de entrada con la clase 'galones-acpm' y calcula la suma total de galones para ACPM
            .reduce((sum, input) => {// Utiliza el método reduce para acumular la suma total de galones para ACPM
                const value = parseGalones(input.value);// Convierte el valor del campo de entrada a un número de galones utilizando la función parseGalones
                return sum + (isNaN(value) ? 0 : value);// Si el valor convertido no es un número válido, se suma 0; de lo contrario, se suma el valor convertido
            }, 0);// El segundo argumento '0' es el valor inicial para la suma total de galones para ACPM

        const corrienteInput = document.querySelector('.tirillas-galones-corriente');// Selecciona el campo de entrada que muestra el total de galones para corriente en la sección de tirillas
        const acpmInput = document.querySelector('.tirillas-galones-acpm');// Selecciona el campo de entrada que muestra el total de galones para ACPM en la sección de tirillas
        const valorCorrienteInput = document.querySelector('.tirillas-valor-corriente');
        const valorAcpmsInput = document.querySelector('.tirillas-valor-acpm');

        if (corrienteInput) {// Si se encuentra el campo de entrada para corriente, actualiza su valor con el total de galones para corriente formateado
            corrienteInput.value = formatGalones(totalCorriente);// Si el campo de entrada para corriente existe, se actualiza su valor con el total de galones para corriente formateado
        }

        if (acpmInput) {// Si se encuentra el campo de entrada para ACPM, actualiza su valor con el total de galones para ACPM formateado
            acpmInput.value = formatGalones(totalAcpms);// Si el campo de entrada para ACPM existe, se actualiza su valor con el total de galones para ACPM formateado
        }

        if (valorCorrienteInput) {
            const precioCorriente = parseFloat(valorCorrienteInput.dataset.precio);
            const totalValorCorriente = totalCorriente * (isNaN(precioCorriente) ? 0 : precioCorriente);
            valorCorrienteInput.value = formatMoneyWithDecimals(totalValorCorriente, 3);
        }

        if (valorAcpmsInput) {
            const precioAcpms = parseFloat(valorAcpmsInput.dataset.precio);
            const totalValorAcpms = totalAcpms * (isNaN(precioAcpms) ? 0 : precioAcpms);
            valorAcpmsInput.value = formatMoney(totalValorAcpms);
        }
    };

    const updateVentasSegunLecturas = () => {
        const glsCorriente = Array.from(document.querySelectorAll('tr[data-combustible="corriente"] .lectura-gls'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const glsAcpms = Array.from(document.querySelectorAll('tr[data-combustible="acpm"] .lectura-gls'))
            .reduce((sum, input) => {
                const value = parseGalones(input.value);
                return sum + (isNaN(value) ? 0 : value);
            }, 0);

        const galonesCorrienteInput = document.querySelector('.ventas-lectura-galones-corriente');
        const galonesAcpmsInput = document.querySelector('.ventas-lectura-galones-acpm');
        const valorCorrienteInput = document.querySelector('.ventas-lectura-valor-corriente');
        const valorAcpmsInput = document.querySelector('.ventas-lectura-valor-acpm');
        const ventasTotal = document.querySelector('.ventas-total-lectura');

        if (galonesCorrienteInput) {
            galonesCorrienteInput.value = formatGalones(glsCorriente);
        }

        if (galonesAcpmsInput) {
            galonesAcpmsInput.value = formatGalones(glsAcpms);
        }

        if (valorCorrienteInput) {
            const precioCorriente = parseFloat(valorCorrienteInput.dataset.precio);
            const total = glsCorriente * (isNaN(precioCorriente) ? 0 : precioCorriente);
            valorCorrienteInput.value = formatMoney(total);
        }

        if (valorAcpmsInput) {
            const precioAcpms = parseFloat(valorAcpmsInput.dataset.precio);
            const total = glsAcpms * (isNaN(precioAcpms) ? 0 : precioAcpms);
            valorAcpmsInput.value = formatMoney(total);
        }

        if (ventasTotal) {
            const valorCorriente = valorCorrienteInput ? parseCurrency(valorCorrienteInput.value) : 0;
            const valorAcpms = valorAcpmsInput ? parseCurrency(valorAcpmsInput.value) : 0;
            const sum = (isNaN(valorCorriente) ? 0 : valorCorriente) + (isNaN(valorAcpms) ? 0 : valorAcpms);
            ventasTotal.textContent = formatMoney(sum);
        }
    };

    document.querySelectorAll('.galones-input').forEach(input => {// Selecciona todos los campos de entrada con la clase 'galones-input' y les agrega eventos para formatear el valor y actualizar los totales cuando el usuario interactúa con ellos
        const formatValue = function () {// Esta función se ejecuta cuando el campo de entrada pierde el foco, cambia su valor o se completa la edición, y formatea el valor de galones, actualiza los totales de tirillas y el total general
            const galones = parseGalones(this.value);// Convierte el valor del campo de entrada a un número de galones utilizando la función parseGalones
            this.value = formatGalones(galones);// Formatea el valor de galones utilizando la función formatGalones y actualiza el campo de entrada con el valor formateado
            updateGrandTotal();// Actualiza el total general después de formatear el valor de galones
            updateTirillasTotals();// Actualiza los totales de tirillas después de formatear el valor de galones
        };

        input.addEventListener('blur', formatValue);// Agrega un evento para formatear el valor de galones cuando el campo de entrada pierde el foco
        input.addEventListener('change', formatValue);// Agrega un evento para formatear el valor de galones cuando el valor del campo de entrada cambia
        input.addEventListener('focusout', formatValue);// Agrega un evento para formatear el valor de galones cuando se completa la edición del campo de entrada

        input.addEventListener('input', function () {// Agrega un evento para actualizar el total de la fila, el total general y los totales de tirillas cada vez que el usuario ingresa un nuevo valor en el campo de entrada
            const precio = parseFloat(this.dataset.precio);// Toma el precio del combustible desde el atributo data-precio del campo de entrada y lo convierte a un número de punto flotante
            const galones = parseGalones(this.value);// Convierte el valor del campo de entrada a un número de galones utilizando la función parseGalones

            if (isNaN(galones) || isNaN(precio)) {// Si el valor de galones o el precio no son números válidos, se borra el valor total de la fila y se actualizan los totales generales y de tirillas, luego se sale de la función
                const totalInput = this.closest('tr')?.querySelector('.valor-total');// Busca el campo de entrada que muestra el valor total en la misma fila que el campo de entrada actual

                if (totalInput) {// Si se encuentra el campo de entrada para el valor total, se borra su valor
                    totalInput.value = '';// Si el campo de entrada para el valor total existe, se borra su valor
                }

                updateGrandTotal();// Actualiza el total general después de borrar el valor total de la fila
                updateTirillasTotals();// Actualiza los totales de tirillas después de borrar el valor total de la fila
                return;// Si el valor de galones o el precio no son válidos, se sale de la función después de actualizar los totales
            }

            const total = galones * precio;// Calcula el valor total de la fila multiplicando el número de galones por el precio
            const totalInput = this.closest('tr')?.querySelector('.valor-total');// Busca el campo de entrada que muestra el valor total en la misma fila que el campo de entrada actual

            if (totalInput) {// Si se encuentra el campo de entrada para el valor total, se actualiza su valor con el total calculado formateado como dinero
                totalInput.value = formatMoney(total);// Si el campo de entrada para el valor total existe, se actualiza su valor con el total calculado formateado como dinero
            }

            updateGrandTotal();// Actualiza el total general después de calcular el valor total de la fila
            updateTirillasTotals();// Actualiza los totales de tirillas después de calcular el valor total de la fila
        });
    });

    // Lecturas: formato y cálculo GLS por fila
    const updateRowGls = (row) => {
        const inicialInput = row.querySelector('.lectura-inicial');
        const finalInput = row.querySelector('.lectura-final');
        const glsInput = row.querySelector('.lectura-gls');

        const inicial = inicialInput ? parseGalones(inicialInput.value) : NaN;
        const final = finalInput ? parseGalones(finalInput.value) : NaN;

        if (!isNaN(inicial) && !isNaN(final)) {
            const diff = final - inicial;
            if (glsInput) glsInput.value = formatGalones(diff);
        } else {
            if (glsInput) glsInput.value = '';
        }

        updateVentasSegunLecturas();
    };

    // Añadir listeners a filas que contienen lecturas
    document.querySelectorAll('tbody tr').forEach(row => {
        const inicial = row.querySelector('.lectura-inicial');
        const final = row.querySelector('.lectura-final');

        if (inicial || final) {
            const handler = function () {
                updateRowGls(row);
            };

            if (inicial) {
                inicial.addEventListener('input', handler);
                inicial.addEventListener('blur', function () {
                    const v = parseGalones(this.value);
                    this.value = formatGalones(v);
                    updateRowGls(row);
                });
            }

            if (final) {
                final.addEventListener('input', handler);
                final.addEventListener('blur', function () {
                    const v = parseGalones(this.value);
                    this.value = formatGalones(v);
                    updateRowGls(row);
                });
            }
        }
    });

    // Inicializar valores al cargar
    document.querySelectorAll('tbody tr').forEach(row => updateRowGls(row));

    updateTirillasTotals();// Al cargar la página, se actualizan los totales de tirillas para reflejar cualquier valor preexistente en los campos de galones
    updateVentasSegunLecturas();
});
