<?php
header('Content-Type: text/plain');
require_once "OttdAdmin.php";

echo "Create: (OK)\n";
$Test = new OttdAdmin('192.168.1.2', 3977, '1100TESTabc');  // Hostname or IP, Port, Password

echo "Connect: \n";
var_dump(
    $Test->connect()
);echo "\n\n";

echo "Join: \n";
print_r(
    $Test->join()
);echo "\n\n";

echo "Get Date: \n";
print_r(
    $Test->getDate()
);echo "\n\n";

echo "Get CMD names: \n";
print_r(
    $Test->getCmdNames()
);echo "\n\n";

echo "CMD: pause: \n";
print_r(
    $Test->console('pause')
);echo "\n\n";

echo "Get Client Info: \n";
print_r(
    $Test->getClientInfo()
);echo "\n\n";

echo "Get Company Info: \n";
print_r(
    $Test->getCompanyInfo()
);echo "\n\n";


echo "Get Company Economy: \n";
print_r(
    $Test->getCompanyEconomy()
);echo "\n\n";

echo "Get Company Stats: \n";
print_r(
    $Test->getCompanyStats()
);echo "\n\n";

