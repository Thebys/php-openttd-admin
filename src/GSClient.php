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

    public function executeCommand($method, array $args = [], $companymode = null)
    {
        // Send the command and get response
        $this->admin->sendGameScript($method, $args, $companymode);
    }

    public function testGameScript()
    {
        $this->admin->enableGameScriptUpdates();
        $this->admin->testSendGameScript();
    }
}

$config = require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $serverId = $_POST['server'] ?? 0;
        $command = $_POST['command'] ?? '';
        $action = $_POST['action'] ?? 'execute';

        $client = new GSClient($config['servers'][$serverId]);
        $client->connect();

        if ($action === 'test') {
            $result = $client->testGameScript();
        } else {
            if (empty($command)) {
                throw new Exception("Command cannot be empty");
            }
            $result = $client->executeCommand($command);
        }        
        exit;
    } catch (Exception $e) {        
        var_dump(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Display HTML interface
?>
<!DOCTYPE html>
<html>

<head>
    <title>OpenTTD GameScript Tester</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        select,
        textarea {
            width: 100%;
            margin: 10px 0;
        }

        textarea {
            height: 150px;
        }

        .response {
            margin-top: 20px;
            white-space: pre-wrap;
        }

        .button-group {
            margin: 10px 0;
        }

        .button-group button {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>OpenTTD GameScript Tester</h1>

        <form id="commandForm" method="post">
            <div>
                <label for="server">Select Server:</label>
                <select name="server" id="server">
                    <?php foreach ($config['servers'] as $index => $server): ?>
                        <option value="<?= $index ?>"><?= $server['host'] ?>:<?= $server['port'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="command">Command (JSON):</label>
                <textarea name="command" id="command">{
    "action": "call",
    "method": "GSSign.BuildSign",
    "args": [0, "Test Sign"]
}</textarea>
            </div>

            <div class="button-group">
                <button type="submit" name="action" value="execute">Send Command</button>
                <button type="submit" name="action" value="test">Test GameScript</button>
            </div>
        </form>

        <div id="response" class="response"></div>
    </div>
</body>

</html>