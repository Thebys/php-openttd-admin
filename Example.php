<?php
require 'vendor/autoload.php';
use Thebys\PhpOpenttdStats\OttdAdmin;

header('Content-Type: text/plain');

function convertToGameDate($openttdDate)
{
    // OpenTTD epoch starts on January 1, 1920
    $epochOffsetDays = (int)(1920 * 365.25); // Cast to int to avoid precision warning
    $daysSinceEpoch = $openttdDate - $epochOffsetDays;

    // Calculate the year and month
    $year = 1920 + (int)($daysSinceEpoch / 365.25);
    $remainingDays = $daysSinceEpoch % (int)365.25;
    $month = (int)($remainingDays / 30.4375) + 1; // Cast to int for month calculation

    return [
        'year' => $year,
        'month' => $month,
    ];
}



echo "Create: (OK)\n";
$admin = new OttdAdmin('openttd.iver.cz', 3989, 'OPENTTDADMINPWD');

// Connect to the server
echo "Connect: \n";
if (!$admin->connect()) {
    echo "Failed to connect to the server.\n";
    exit;
}
echo "Connection successful.\n\n";

// Join the server
echo "Join: \n";
$serverInfo = $admin->join();
unset($serverInfo['ADMIN_UPDATE']);
print_r($serverInfo);
echo "\n\n";


echo "Get Date: \n";
$currentDate = $admin->getDate();
$gameDate = convertToGameDate($currentDate);
echo "Current Date (Internal): $currentDate\n";
echo "Current Year: {$gameDate['year']}\n";
echo "Current Month: {$gameDate['month']}\n\n";

// Calculate the number of years in the game
$startYearInternal = $serverInfo['START_YEAR'] ?? 0;
$startYear = convertToGameDate($startYearInternal)['year'];
$currentYear = $gameDate['year'];
$yearsInGame = $currentYear - $startYear;
echo "Game Start Year: $startYear\n";
echo "Current Year: $currentYear\n";
echo "Years in Game: $yearsInGame\n\n";

$admin->sendExternalChat("PHP", "Admin", "PHP Script polling game data.");
/*for ($i = 0; $i < 17; $i++) {
    $admin->sendExternalChat("PHP", "Admin", "Colored message - " . $i, $i); // Sends a red message
}*/

// Get the list of players
echo "Get Client Info: \n";
$clients = $admin->getClientInfo();
if (empty($clients)) {
    echo "No players found.\n";
} else {
    print_r($clients);
    $numPlayers = count($clients);
    echo "Number of Players: $numPlayers\n\n";
}

// Get the list of companies
echo "Get Company Info: \n";
$companies = $admin->getCompanyInfo();
if (empty($companies)) {
    echo "No companies found.\n";
} else {
    print_r($companies);
    $numCompanies = count($companies);
    echo "Number of Companies: $numCompanies\n\n";
}

// Get company economy stats
echo "Get Company Economy: \n";
$economyStats = $admin->getCompanyEconomy();
if (empty($economyStats)) {
    echo "No economy stats received.\n";
} else {
    print_r($economyStats);
}

// Get company statistics
echo "Get Company Stats: \n";
$companyStats = $admin->getCompanyStats();
if (empty($companyStats)) {
    echo "No company stats received.\n";
} else {
    print_r($companyStats);
}

// Display a summary of company rankings by bank balance
echo "\nCompany Rankings (by Bank Balance):\n";
$rankings = [];
foreach ($economyStats as $company) {
    $companyID = $company['COMPANY_ID'];
    $balance = $company['MONEY'] ?? 0;
    $rankings[$companyID] = $balance;
}
arsort($rankings);  // Sort by bank balance in descending order

foreach ($rankings as $companyID => $balance) {
    $companyName = $companies[$companyID]['COMPANY_NAME'] ?? 'Unknown';
    echo "Company ID: $companyID, Name: $companyName, Bank Balance: $balance, Balance CZK: " . ($balance * 40) . "\n";
}

echo "\nAll data retrieved successfully!\n";
