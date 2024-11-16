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
    </script>
</head>
<body class="bg-dark text-light">
    <div class="container my-5">
        <h1 class="text-center mb-4">🚂 OpenTTD Server Dashboard 🚉</h1>
        <button id="toggle-fetching" class="btn btn-primary mb-4" onclick="toggleFetching()">Start Fetching</button>
        <h2>Leaderboard</h2>
        <div id="leaderboard" class="mb-5"></div>
        <div id="server-info" class="mb-5"></div>        
        <h2>Company Stats</h2>
        <canvas id="company-stats-chart" height="100"></canvas>
    </div>
</body>
</html>
