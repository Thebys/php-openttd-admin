async function fetchData(url) {
    const response = await fetch(url);
    return await response.json();
}

async function updateDashboard() {
    const serverInfo = await fetchData('api/getServers.php');
    const companies = await fetchData('api/getCompanies.php?server_id=1');
    const stats = await fetchData('api/getStats.php?server_id=1');

    // Update Server Info
    const serverInfoDiv = document.getElementById('server-info');
    serverInfoDiv.innerHTML = `
        <h3>${serverInfo[0].server_name}</h3>
        <p>Host: ${serverInfo[0].host}, Admin Port: ${serverInfo[0].port}</p>
        <p>Last Updated: ${serverInfo[0].last_updated}</p>
    `;

    // Map latest stats per company
    const companyLatestStats = {};
    stats.forEach(stat => {
        const companyId = stat.company_id;
        const timestamp = new Date(stat.timestamp);
        if (!companyLatestStats[companyId] || timestamp > companyLatestStats[companyId].timestamp) {
            companyLatestStats[companyId] = {
                ...stat,
                timestamp: timestamp
            };
        }
    });

    // Update Leaderboard with more stats
    const leaderboardDiv = document.getElementById('leaderboard');
    leaderboardDiv.innerHTML = companies.map(company => {
        const latestStat = companyLatestStats[company.company_id] || {};
        return `
            <div class="mb-2">
                <strong>${company.company_name}</strong> (Manager: ${company.manager})
                <ul>
                    <li>Money: ${latestStat.money || 'N/A'}</li>
                    <li>Income: ${latestStat.income || 'N/A'}</li>
                    <li>Loan: ${latestStat.loan || 'N/A'}</li>
                    <li>Performance Last Quarter: ${latestStat.perf_lastq || 'N/A'}</li>
                    <li>Trains: ${latestStat.trains_count || 'N/A'}</li>
                    <!-- Add more stats as needed -->
                </ul>
            </div>
        `;
    }).join('');

    // Map company_id to company_name
    const companyMap = {};
    companies.forEach(company => {
        companyMap[company.company_id] = company.company_name;
    });

    // Prepare data per company
    const companyData = {};
    stats.forEach(stat => {
        const companyId = stat.company_id;
        if (!companyData[companyId]) {
            companyData[companyId] = {
                company_name: companyMap[companyId] || 'Unknown',
                data: []
            };
        }
        // Convert timestamp to Date object
        const timestamp = new Date(stat.timestamp);
        // Push data point
        companyData[companyId].data.push({
            x: timestamp,
            money: stat.money,
            loan: stat.loan,
            income: stat.income,
            value_lastq: stat.value_lastq,
            value_prevq: stat.value_prevq,
            perf_lastq: stat.perf_lastq,
            perf_prevq: stat.perf_prevq,
            deliver_lastq: stat.deliver_lastq,
            deliver_prevq: stat.deliver_prevq,
            trains_count: stat.trains_count,
            lorries_count: stat.lorries_count,
            busses_count: stat.busses_count,
            planes_count: stat.planes_count,
            ships_count: stat.ships_count,
        });
    });

    // Metrics to plot
    const metrics = ['money', 'income', 'loan']; // Add more metrics as needed

    // Prepare datasets for chart.js
    const datasets = [];
    Object.keys(companyData).forEach(companyId => {
        const company = companyData[companyId];
        metrics.forEach(metric => {
            const existingDataset = window.companyStatsChart ? window.companyStatsChart.data.datasets.find(ds => ds.label === `${company.company_name} - ${metric}`) : null;
            if (existingDataset) {
                // Update existing dataset
                existingDataset.data = company.data.sort((a, b) => a.x - b.x).map(d => ({ x: d.x, y: d[metric] }));
            } else {
                // Create new dataset
                datasets.push({
                    label: `${company.company_name} - ${metric}`,
                    data: company.data.sort((a, b) => a.x - b.x).map(d => ({ x: d.x, y: d[metric] })),
                    fill: false,
                    tension: 0.1
                });
            }
        });
    });

    // If chart exists, update it; otherwise, create a new one
    if (window.companyStatsChart) {
        window.companyStatsChart.update();
    } else {
        const ctx = document.getElementById('company-stats-chart').getContext('2d');
        window.companyStatsChart = new Chart(ctx, {
            type: 'line',
            data: {
                datasets: datasets
            },
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute'
                        },
                        title: {
                            display: true,
                            text: 'Time'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    }
                }
            }
        });
    }
}

// Fetch and update data every 10 seconds
// setInterval(updateDashboard, 10000);
updateDashboard();