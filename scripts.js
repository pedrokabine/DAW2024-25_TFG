//scripts de apoyo para la interfaz del Diario Personal Digital comentados para más aclaración

// Autoajustar altura de los textareas según el contenido
document.addEventListener('input', function (event) {
    if (event.target.tagName && event.target.tagName.toLowerCase() === 'textarea') {
        const ta = event.target;
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    }
});

// Al cargar el DOM, si hay datos de gráficas y Chart.js, pintamos las gráficas
document.addEventListener('DOMContentLoaded', function () {
    // Esto solo funcionará en la página de estadísticas,
    // porque es donde esta definido window.datosGraficos y cargo Chart.js
    if (typeof Chart === 'undefined') {
        // En el resto de páginas no hay librería Chart.js asi que salimos
        return;
    }

    if (!window.datosGraficos) {
        return;
    }

    const datos = window.datosGraficos;

    // ---- Gráfico 1: Emociones ----
    const ctxEmociones = document.getElementById('graficoEmociones');
    if (ctxEmociones && Array.isArray(datos.labelsEmocion) && datos.labelsEmocion.length > 0) {
        new Chart(ctxEmociones, {
            type: 'bar',
            data: {
                labels: datos.labelsEmocion,
                datasets: [{
                    label: 'Número de entradas',
                    data: datos.valoresEmocion,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // ---- Gráfico 2: Entradas por mes ----
    const ctxMeses = document.getElementById('graficoMeses');
    if (ctxMeses && Array.isArray(datos.labelsMes) && datos.labelsMes.length > 0) {
        new Chart(ctxMeses, {
            type: 'line',
            data: {
                labels: datos.labelsMes,
                datasets: [{
                    label: 'Entradas registradas',
                    data: datos.valoresMes,
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                tension: 0.3,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // ---- Gráfico 3: Evolución emocional por semana ----
    const ctxSemana = document.getElementById('graficoSemana');
    if (ctxSemana && Array.isArray(datos.labelsSemana) && datos.labelsSemana.length > 0) {
        new Chart(ctxSemana, {
            type: 'line',
            data: {
                labels: datos.labelsSemana,
                datasets: [{
                    label: 'Emoción media semanal',
                    data: datos.valoresSemana,
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                tension: 0.3,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    console.log('Diario Personal Digital - gráficas inicializadas');
});
