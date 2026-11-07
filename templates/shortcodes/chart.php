<!-- ========================================== -->
<!-- templates/shortcodes/chart.php             -->
<!-- ========================================== -->
<?php if (!defined('ABSPATH')) exit; ?>

<?php
$chart_colors = array(
    '#e10600', '#00d2be', '#0600ef', '#ff8700',
    '#006f62', '#b6babd', '#52e252', '#ff1e8c',
    '#f5d20d', '#a855f7', '#00aaff', '#ff6600',
);
?>

<style>
.f1-chart-wrap { margin: 24px 0; }
.f1-chart-wrap h2 { margin-bottom: 16px; }
.f1-chart-box {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 24px 20px 16px;
}
.f1-chart-status {
    color: #888;
    font-size: 14px;
    text-align: center;
    padding: 40px 0;
}
.f1-chart-canvas-wrap { display: none; }
.f1-chart-canvas-wrap canvas { max-height: 400px; }
</style>

<div class="f1-chart-wrap">
    <h2>📈 Evolución de Puntos</h2>
    <div class="f1-chart-box">
        <div id="f1-chart-status" class="f1-chart-status">⏳ Cargando datos...</div>
        <div id="f1-chart-canvas-wrap" class="f1-chart-canvas-wrap">
            <canvas id="f1PointsChart"></canvas>
        </div>
    </div>
</div>

<script>
(function() {
    function waitAndInit(tries) {
        if (tries <= 0) {
            document.getElementById('f1-chart-status').textContent = '⚠️ Error: no se pudo cargar Chart.js.';
            return;
        }
        if (typeof jQuery === 'undefined' || typeof Chart === 'undefined' || typeof f1Stars === 'undefined') {
            setTimeout(function() { waitAndInit(tries - 1); }, 200);
            return;
        }
        initChart();
    }

    function initChart() {
        var $ = jQuery;
        var colors = <?php echo json_encode(array_values($chart_colors)); ?>;
        var chartInstance = null;

        $.ajax({
            url: f1Stars.ajax_url,
            type: 'POST',
            data: { action: 'f1_chart_data', nonce: f1Stars.nonce },
            success: function(response) {
                var $status = $('#f1-chart-status');
                var $wrap   = $('#f1-chart-canvas-wrap');

                if (!response.success) {
                    $status.text('⚠️ Error al obtener los datos.');
                    return;
                }

                var series = response.data.series || [];
                var labels = response.data.labels  || [];

                if (series.length === 0 || labels.length === 0) {
                    $status.html('🏁 Aún no hay resultados puntuados esta temporada.<br><small>El gráfico aparecerá cuando se carguen los primeros resultados.</small>');
                    return;
                }

                series.sort(function(a, b) {
                    var sa = a.data.reduce(function(acc, v) { return acc + v; }, 0);
                    var sb = b.data.reduce(function(acc, v) { return acc + v; }, 0);
                    return sb - sa;
                });

                var fullNames = response.data.full_names || labels;

                var datasets = series.map(function(s, i) {
                    var color = colors[i % colors.length];
                    return {
                        label: s.nick,
                        data: s.data,
                        borderColor: color,
                        backgroundColor: color + '18',
                        borderWidth: 2.5,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: color,
                        tension: 0.3,
                        fill: false
                    };
                });

                $status.hide();
                $wrap.show();

                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart(
                    document.getElementById('f1PointsChart'),
                    {
                        type: 'line',
                        data: { labels: labels, datasets: datasets },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: '#ccc',
                                        padding: 14,
                                        font: { size: 13 },
                                        usePointStyle: true
                                    }
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15,15,25,0.95)',
                                    titleColor: '#fff',
                                    bodyColor: '#bbb',
                                    borderColor: 'rgba(255,255,255,0.1)',
                                    borderWidth: 1,
                                    padding: 12,
                                    callbacks: {
                                        title: function(items) {
                                            return fullNames[items[0].dataIndex] || items[0].label;
                                        },
                                        label: function(item) {
                                            return '  ' + item.dataset.label + ': ' + item.parsed.y + ' pts';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(255,255,255,0.06)' },
                                    ticks: { color: '#888', font: { size: 12 } }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(255,255,255,0.06)' },
                                    ticks: { color: '#888', font: { size: 12 }, precision: 0 },
                                    title: {
                                        display: true,
                                        text: 'Puntos acumulados',
                                        color: '#666',
                                        font: { size: 11 }
                                    }
                                }
                            }
                        }
                    }
                );
            },
            error: function() {
                $('#f1-chart-status').text('⚠️ Error de conexión al cargar el gráfico.');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { waitAndInit(30); });
    } else {
        waitAndInit(30);
    }
})();
</script>
