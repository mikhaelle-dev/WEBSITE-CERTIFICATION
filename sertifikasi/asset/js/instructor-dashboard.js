document.addEventListener('DOMContentLoaded', function () {
    var chartCanvas = document.getElementById('instructorEnrollmentChart');
    if (!chartCanvas || typeof Chart === 'undefined') {
        return;
    }

    var palette = getComputedStyle(document.documentElement);
    var purple = palette.getPropertyValue('--soft-chart-purple').trim();
    var yellow = palette.getPropertyValue('--soft-chart-yellow').trim();
    var context = chartCanvas.getContext('2d');
    var purpleFill = context.createLinearGradient(0, 0, 0, 260);
    purpleFill.addColorStop(0, palette.getPropertyValue('--soft-chart-purple-fill-top').trim());
    purpleFill.addColorStop(1, palette.getPropertyValue('--soft-chart-purple-fill-bottom').trim());

    var yellowFill = context.createLinearGradient(0, 0, 0, 260);
    yellowFill.addColorStop(0, palette.getPropertyValue('--soft-chart-yellow-fill-top').trim());
    yellowFill.addColorStop(1, palette.getPropertyValue('--soft-chart-yellow-fill-bottom').trim());

    new Chart(chartCanvas, {
        type: 'line',
        data: {
            // Replace these illustrative values with instructor-scoped PHP data when available.
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [
                {
                    label: 'Pendaftar',
                    data: [24, 31, 28, 43, 39, 52, 47, 61, 55, 68, 63, 76],
                    borderColor: purple,
                    backgroundColor: purpleFill,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5
                },
                {
                    label: 'Lulus',
                    data: [16, 20, 19, 29, 27, 36, 34, 42, 39, 48, 46, 56],
                    borderColor: yellow,
                    backgroundColor: yellowFill,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#282433',
                    padding: 11,
                    titleFont: {
                        family: '"Plus Jakarta Sans", sans-serif',
                        size: 11,
                        weight: '600'
                    },
                    bodyFont: {
                        family: '"Plus Jakarta Sans", sans-serif',
                        size: 10
                    },
                    displayColors: true,
                    usePointStyle: true
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    border: {
                        display: false
                    },
                    ticks: {
                        color: '#625e6e',
                        font: {
                            family: '"Plus Jakarta Sans", sans-serif',
                            size: 10
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 80,
                    border: {
                        display: false
                    },
                    grid: {
                        color: 'rgba(98, 94, 110, 0.12)',
                        drawTicks: false
                    },
                    ticks: {
                        color: '#625e6e',
                        padding: 8,
                        precision: 0,
                        font: {
                            family: '"Plus Jakarta Sans", sans-serif',
                            size: 10
                        }
                    }
                }
            }
        }
    });
});
