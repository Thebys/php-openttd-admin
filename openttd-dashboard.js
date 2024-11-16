async function fetchData(url) {
    const response = await fetch(url);
    return await response.json();
}

let selectedTimeScope = '1h'; // Default time scope
let selectedMetrics = ['money', 'income', 'loan']; // Default selected metrics
let selectedCompanies = []; // Default to all companies

const timeScopes = {
    '1m': moment.duration(1, 'minutes').asMilliseconds(),
    '5m': moment.duration(5, 'minutes').asMilliseconds(),
    '15m': moment.duration(15, 'minutes').asMilliseconds(),
    '30m': moment.duration(30, 'minutes').asMilliseconds(),
    '1h': moment.duration(1, 'hours').asMilliseconds(),
    '2h': moment.duration(2, 'hours').asMilliseconds(),
    '4h': moment.duration(4, 'hours').asMilliseconds(),
    '24h': moment.duration(1, 'days').asMilliseconds(),
};

window.setTimeScope = function(scope) {
    selectedTimeScope = scope;
    updateDashboard();
};

window.toggleMetric = function(metric) {
    const index = selectedMetrics.indexOf(metric);
    if (index > -1) {
        selectedMetrics.splice(index, 1);
    } else {
        selectedMetrics.push(metric);
    }
    updateDashboard();
};

window.toggleCompany = function(companyId) {
    const index = selectedCompanies.indexOf(companyId);
    if (index > -1) {
        selectedCompanies.splice(index, 1);
    } else {
        selectedCompanies.push(companyId);
    }
    updateDashboard();
};

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

    // Add Time Scope Selection UI
    let timeScopeDiv = document.getElementById('time-scope');
    if (!timeScopeDiv) {
        timeScopeDiv = document.createElement('div');
        timeScopeDiv.id = 'time-scope';
        timeScopeDiv.classList.add('mb-4');
        timeScopeDiv.innerHTML = `
            <label>Select Time Scope: </label>
            ${Object.keys(timeScopes)
                .map(
                    (scope) => `<button class="btn btn-secondary btn-sm m-1" onclick="setTimeScope('${scope}')">${scope}</button>`
                )
                .join('')}
        `;
        serverInfoDiv.parentNode.insertBefore(timeScopeDiv, serverInfoDiv.nextSibling);
    }

    // Add Metrics Selection UI
    let metricsDiv = document.getElementById('metrics-selection');
    if (!metricsDiv) {
        const allMetrics = [
            'money',
            'income',
            'loan',
            'value_lastq',
            'value_prevq',
            'perf_lastq',
            'perf_prevq',
            'deliver_lastq',
            'deliver_prevq',
            'trains_count',
            'lorries_count',
            'busses_count',
            'planes_count',
            'ships_count',
            'train_stations_count',
            'lorry_stations_count',
            'bus_stops_count',
            'airports_count',
            'harbours_count',
        ];
        metricsDiv = document.createElement('div');
        metricsDiv.id = 'metrics-selection';
        metricsDiv.classList.add('mb-4');
        metricsDiv.innerHTML = `
            <label>Select Metrics: </label>
            ${allMetrics
                .map(
                    (metric) => `
                <label class="form-check-label m-1">
                    <input type="checkbox" class="form-check-input" onchange="toggleMetric('${metric}')" ${
                        selectedMetrics.includes(metric) ? 'checked' : ''
                    }>
                    ${metric}
                </label>
            `
                )
                .join('')}
        `;
        timeScopeDiv.parentNode.insertBefore(metricsDiv, timeScopeDiv.nextSibling);
    }

    // Add Company Selection UI
    let companyDiv = document.getElementById('company-selection');
    if (!companyDiv) {
        companyDiv = document.createElement('div');
        companyDiv.id = 'company-selection';
        companyDiv.classList.add('mb-4');
        companyDiv.innerHTML = `
            <label>Select Companies: </label>
            ${companies
                .map(
                    (company) => `
                <label class="form-check-label m-1">
                    <input type="checkbox" class="form-check-input" onchange="toggleCompany('${company.company_id}')" ${
                        selectedCompanies.length === 0 || selectedCompanies.includes(company.company_id.toString()) ? 'checked' : ''
                    }>
                    ${company.company_name}
                </label>
            `
                )
                .join('')}
        `;
        metricsDiv.parentNode.insertBefore(companyDiv, metricsDiv.nextSibling);
    }

    // Filter stats based on selectedTimeScope, use CEST timezone
    const now = Date.now() - 1000 * 60 * 60 * 1; // 1 hours seems to fix it - 1 and 5 minutes should actually have few datapoints, not a whole hour.
    const timeScopeValue = timeScopes[selectedTimeScope];
    const filteredStats = stats.filter((stat) => {
        const timestamp = new Date(stat.timestamp);
        return timestamp >= new Date(now - timeScopeValue);
    });

    // Map latest stats per company
    const companyLatestStats = {};
    filteredStats.forEach((stat) => {
        const companyId = stat.company_id.toString();
        if (selectedCompanies.length > 0 && !selectedCompanies.includes(companyId)) return;
        const timestamp = new Date(stat.timestamp);
        if (!companyLatestStats[companyId] || timestamp > companyLatestStats[companyId].timestamp) {
            companyLatestStats[companyId] = {
                ...stat,
                timestamp: timestamp,
            };
        }
    });

    // Update Leaderboard with selected metrics
    const leaderboardDiv = document.getElementById('leaderboard');
    leaderboardDiv.innerHTML = companies
        .filter((company) => selectedCompanies.length === 0 || selectedCompanies.includes(company.company_id.toString()))
        .map((company) => {
            const latestStat = companyLatestStats[company.company_id] || {};
            return `
                <div class="mb-2">
                    <strong>${company.company_name}</strong> (Manager: ${company.manager})
                    <ul>
                        ${selectedMetrics
                            .map((metric) => `<li>${metric}: ${latestStat[metric] || 'N/A'}</li>`)
                            .join('')}
                    </ul>
                </div>
            `;
        })
        .join('');

    // Map company_id to company_name
    const companyMap = {};
    companies.forEach((company) => {
        companyMap[company.company_id.toString()] = company.company_name;
    });

    // Prepare data per company
    const companyData = {};
    filteredStats.forEach((stat) => {
        const companyId = stat.company_id.toString();
        if (selectedCompanies.length > 0 && !selectedCompanies.includes(companyId)) return;

        if (!companyData[companyId]) {
            companyData[companyId] = {
                company_name: companyMap[companyId] || 'Unknown',
                data: [],
            };
        }
        // Convert timestamp to Date object
        const timestamp = new Date(stat.timestamp);
        // Push data point
        companyData[companyId].data.push({
            x: timestamp,
            ...stat,
        });
    });

    // Prepare datasets for chart.js
    const datasets = [];
    Object.keys(companyData).forEach((companyId) => {
        const company = companyData[companyId];
        selectedMetrics.forEach((metric) => {
            const dataPoints = company.data
                .sort((a, b) => a.x - b.x)
                .map((d) => ({ x: d.x, y: d[metric] }));
            datasets.push({
                label: `${company.company_name} - ${metric}`,
                data: dataPoints,
                fill: false,
                tension: 0.1,
            });
        });
    });

    // If chart exists, destroy and recreate it; otherwise, create a new one
    const ctx = document.getElementById('company-stats-chart').getContext('2d');
    if (window.companyStatsChart) {
        window.companyStatsChart.destroy();
    }
    window.companyStatsChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets,
        },
        options: {
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'minute',
                    },
                    title: {
                        display: true,
                        text: 'Time',
                    },
                },
                y: {
                    title: {
                        display: true,
                        text: 'Value',
                    },
                },
            },
        },
    });
}

// Fetch and update data every 10 seconds
setInterval(updateDashboard, 10000);
updateDashboard();