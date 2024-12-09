<?php

require 'bootstrap.php';

class GSClient
{
    private $admin;

    public function __construct($server)
    {
        if (!isset($server['host']) || !isset($server['port']) || !isset($server['password'])) {
            throw new Exception("Server configuration incomplete");
        }

        $this->admin = new Thebys\PhpOpenttdStats\OttdAdmin(
            $server['host'],
            $server['port'],
            $server['password']
        );
    }

    public function connect()
    {
        if (!$this->admin->connect()) {
            throw new Exception("Failed to connect to OpenTTD server");
        }
        $this->admin->join();
        return true;
    }

    public function executeCommand($method, array $args = [], $companymode = null, $number = null, $waitForResponse = false)
    {
        // Send the command
        $this->admin->sendGameScript($method, $args, $companymode, $number, $waitForResponse);
    }

    public function testGameScript()
    {
        $this->admin->enableGameScriptUpdates();
        $this->admin->testSendGameScript();
    }

    public function getLogger()
    {
        return $this->admin->getLogger();
    }
}

$config = require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serverId = $_POST['server'] ?? 0;
        $command = $_POST['command'] ?? '';
        $action = $_POST['action'] ?? 'call';

        $client = new GSClient($config['servers'][$serverId]);
        $client->connect();

        if ($action === 'test') {
            $client->testGameScript();
        } else {
            if (empty($command)) {
                throw new Exception("Command cannot be empty");
            }
            $command = json_decode($command, true);
            $client->executeCommand($command['method'], $command['args'] ?? [], null, $command['number'], true);
        }

        // Get formatted messages as array instead of JSON string
        $messages = $client->getLogger()->getFormattedMessages();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'result' => $messages  // Pass the array directly
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
