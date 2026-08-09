/**
 * Dashboard de Recaudaciones - Configuración de Gráficos Chart.js
 * 
 * Este script inicializa y configura los gráficos interactivos del dashboard:
 * - Gráfico de Barras: Recaudación por Tipo de Distribución SIIF
 * - Gráfico de Barras: Recaudación por Dependencia (Top 10)
 * - Gráfico de Torta: Distribución por Medio de Pago
 * 
 * Versión: 1.0
 * Requiere: Chart.js 3.9+
 */

document.addEventListener('DOMContentLoaded', function() {
    // Verificar que existan los datos
    if (typeof window.dashboardData === 'undefined') {
        console.warn('Dashboard data not found');
        return;
    }

    const data = window.dashboardData;

    // Configuración de colores consistentes
    const colorScheme = {
        primary: 'rgba(0, 123, 255, 0.8)',
        success: 'rgba(40, 167, 69, 0.8)',
        info: 'rgba(23, 162, 184, 0.8)',
        warning: 'rgba(255, 193, 7, 0.8)',
        danger: 'rgba(220, 53, 69, 0.8)',
        secondary: 'rgba(108, 117, 125, 0.8)',
        purple: 'rgba(111, 66, 193, 0.8)',
        teal: 'rgba(32, 201, 151, 0.8)',
        orange: 'rgba(253, 126, 20, 0.8)',
        pink: 'rgba(232, 62, 140, 0.8)',
    };

    const colorsArray = [
        colorScheme.primary,
        colorScheme.success,
        colorScheme.warning,
        colorScheme.danger,
        colorScheme.info,
        colorScheme.purple,
        colorScheme.teal,
        colorScheme.orange,
        colorScheme.pink,
        colorScheme.secondary,
    ];

    // Configuración común de tooltips
    const commonTooltipOptions = {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: '#fff',
        bodyColor: '#fff',
        borderColor: 'rgba(255, 255, 255, 0.3)',
        borderWidth: 1,
        padding: 12,
        displayColors: true,
        callbacks: {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                    label += ': ';
                }
                if (context.parsed.y !== null) {
                    label += '$ ' + formatNumber(context.parsed.y);
                } else if (context.parsed !== null) {
                    label += '$ ' + formatNumber(context.parsed);
                }
                return label;
            }
        }
    };

    /**
     * Formatea números con separadores de miles y decimales
     */
    function formatNumber(number) {
        return new Intl.NumberFormat('es-UY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    /**
     * Gráfico 1: Recaudación por Tipo de Distribución SIIF
     */
    const chartTipoSiifElement = document.getElementById('chartTipoSiif');
    if (chartTipoSiifElement && data.recaudacion_por_tipo_siif && data.recaudacion_por_tipo_siif.length > 0) {
        const tipoSiifData = data.recaudacion_por_tipo_siif;
        
        const labels = tipoSiifData.map(item => item.tipo);
        const valores = tipoSiifData.map(item => item.total);
        
        new Chart(chartTipoSiifElement, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Recaudación',
                    data: valores,
                    backgroundColor: colorScheme.info,
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                    barThickness: 40,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    },
                    tooltip: commonTooltipOptions
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$ ' + formatNumber(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0,
                            autoSkip: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Gráfico 2: Recaudación por Dependencia (Top 10)
     */
    const chartDependenciasElement = document.getElementById('chartDependencias');
    if (chartDependenciasElement && data.recaudacion_por_dependencia && data.recaudacion_por_dependencia.length > 0) {
        const dependenciasData = data.recaudacion_por_dependencia;
        
        const labels = dependenciasData.map(item => item.dependencia);
        const valores = dependenciasData.map(item => item.total);
        
        // Asignar colores diferentes a cada barra
        const backgroundColors = valores.map((_, index) => colorsArray[index % colorsArray.length]);
        
        new Chart(chartDependenciasElement, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Recaudación',
                    data: valores,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors.map(color => color.replace('0.8', '1')),
                    borderWidth: 1,
                    borderRadius: 5,
                    barThickness: 35,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y', // Gráfico horizontal
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    },
                    tooltip: commonTooltipOptions
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$ ' + formatNumber(value);
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Gráfico 3: Distribución por Medio de Pago (Pie Chart)
     */
    const chartMediosPagoElement = document.getElementById('chartMediosPago');
    if (chartMediosPagoElement && data.total_recaudado && data.total_recaudado.desglose) {
        const desglose = data.total_recaudado.desglose;
        
        // Filtrar solo medios con monto > 0
        const mediosConDatos = desglose.filter(item => item.monto > 0);
        
        if (mediosConDatos.length > 0) {
            const labels = mediosConDatos.map(item => item.medio);
            const valores = mediosConDatos.map(item => item.monto);
            const porcentajes = mediosConDatos.map(item => item.porcentaje);
            
            // Colores específicos por tipo de medio de pago
            const backgroundColors = mediosConDatos.map(item => {
                switch(item.medio) {
                    case 'Efectivo':
                        return colorScheme.success;
                    case 'Cheque':
                        return colorScheme.warning;
                    case 'Transferencia Bancaria':
                        return colorScheme.info;
                    case 'Tarjeta de Débito':
                        return colorScheme.primary;
                    default:
                        return colorScheme.secondary;
                }
            });
            
            new Chart(chartMediosPagoElement, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: valores,
                        backgroundColor: backgroundColors,
                        borderColor: '#fff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 13
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            const percentage = porcentajes[i];
                                            return {
                                                text: `${label}: ${percentage}%`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.3)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const percentage = porcentajes[context.dataIndex];
                                    return `${label}: $ ${formatNumber(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Listener para recarga de datos de Livewire
     * Recrea los gráficos cuando Livewire actualiza el componente
     */
    document.addEventListener('livewire:init', function () {
        Livewire.hook('commit', ({ succeed }) => {
            succeed((message, component) => {
                // Destruir gráficos existentes antes de recrear
                Chart.helpers.each(Chart.instances, function(instance) {
                    instance.destroy();
                });

                // Recargar el script para recrear los gráficos
                // (Los gráficos se recrearán automáticamente con los nuevos datos)
            });
        });
    });

    console.log('Dashboard charts initialized successfully');
});

/**
 * Función para exportar los gráficos como imágenes (opcional)
 */
function exportChartAsImage(chartId, filename) {
    const canvas = document.getElementById(chartId);
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = filename || 'chart.png';
        link.href = url;
        link.click();
    }
}

// Hacer disponible globalmente para uso desde botones de exportación
window.exportChartAsImage = exportChartAsImage;
