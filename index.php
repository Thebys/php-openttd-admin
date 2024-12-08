<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenTTD Server Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
    <script src="openttd-dashboard.js"></script>
    <script>
        let fetchIntervalId = null;
        let servers = [];
        let selectedServerId = null;

        async function fetchServers() {
            try {
                const response = await fetch('api/getServers.php');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                servers = await response.json();
                populateServerDropdown();
            } catch (error) {
                console.error('Error fetching servers:', error);
            }
        }

        function populateServerDropdown() {
            const serverSelect = document.getElementById('server-select');
            serverSelect.innerHTML = '<option value="">Select a server (map seed)</option>';
            servers.forEach(server => {
                const option = document.createElement('option');
                option.value = server.id;
                option.textContent = `${server.server_name} - ${server.map_seed}`;
                serverSelect.appendChild(option);
            });
        }

        function handleServerChange() {
            const serverSelect = document.getElementById('server-select');
            selectedServerId = serverSelect.value;
            if (selectedServerId) {
                updateDashboardWithServerId();
            } else {
                console.warn('No server selected.');
                // Optionally, clear the dashboard view
                document.getElementById('leaderboard').innerHTML = '';
                document.getElementById('server-info').innerHTML = '';
            }
        }

        async function fetchDataPeriodically() {
            try {
                const response = await fetch('/src/fetch_data.php');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();
                console.log('Data fetched:', data);
                // You can add additional logic here to handle the fetched data
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        }

        function toggleFetching() {
            const button = document.getElementById('toggle-fetching');
            if (fetchIntervalId) {
                clearInterval(fetchIntervalId);
                fetchIntervalId = null;
                button.textContent = 'Start Fetching';
            } else {
                fetchDataPeriodically(); // Fetch immediately on start
                fetchIntervalId = setInterval(fetchDataPeriodically, 10000);
                button.textContent = 'Stop Fetching';
            }
        }

        // Rename the local function to avoid conflict
        function updateDashboardWithServerId() {
            if (typeof window.updateDashboard === 'function') {
                try {
                    window.updateDashboard(selectedServerId);
                } catch (error) {
                    console.error('Error updating dashboard:', error);
                    // Optionally, display a message to the user
                    document.getElementById('leaderboard').innerHTML = '<p>No data available for the selected server/game.</p>';
                    document.getElementById('server-info').innerHTML = '';
                }
            } else {
                console.error('updateDashboard function not found.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchServers();
            const serverSelect = document.getElementById('server-select');
            serverSelect.addEventListener('change', handleServerChange);
        });
    </script>
</head>
<body class="bg-dark text-light">
    <div class="container my-5">
        <h1 class="text-center mb-4">🚂 OpenTTD Server Dashboard 🚉</h1>
        <button id="toggle-fetching" class="btn btn-primary mb-4" onclick="toggleFetching()">Start Fetching</button>
        <div class="mb-4">
            <label for="server-select" class="form-label">Filter by Server (Map Seed):</label>
            <select id="server-select" class="form-select">
                <option value="">Select a server (map seed)</option>
            </select>
        </div>
        <h2>Leaderboard</h2>
        <div id="leaderboard" class="mb-5"></div>
        <div id="server-info" class="mb-5"></div>        
        <h2>Company Stats</h2>
        <canvas id="company-stats-chart" height="100"></canvas>
    </div>
</body>
</html>
