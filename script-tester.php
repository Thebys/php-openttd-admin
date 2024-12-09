<?php
require 'src/bootstrap.php';

$commandTemplates = [
    'get_company_name' => [
        'name' => 'Get Company Name | GSCompany.GetName | [0]',
        'command' => '{"action": "call", "number": 123456789, "method": "GSCompany.GetName","args": [0]}'
    ],
    'pause_game' => [
        'name' => 'Pause Game | GSGame.Pause | []',
        'command' => '{"action": "call", "number": 123456789, "method": "GSGame.Pause","args": []}'
    ],
    'unpause_game' => [
        'name' => 'Unpause Game | GSGame.Unpause | []',
        'command' => '{"action": "call", "number": 123456789, "method": "GSGame.Unpause","args": []}'
    ]
];
?>
<!DOCTYPE html>
<html>

<head>
    <title>OpenTTD Admin Port Server GS Tester</title>
    <script src="https://unpkg.com/htmx.org@2.0.3"></script>
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
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
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

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>OpenTTD Admin Port Server GS Tester</h1>

        <form id="commandForm">
            <div>
                <label for="server">Select Server:</label>
                <select name="server" id="server">
                    <?php foreach ($config['servers'] as $index => $server): ?>
                        <option value="<?= $index ?>"><?= $server['host'] ?>:<?= $server['port'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="template">Select Command Template:</label>
                <select name="template" id="template" onchange="updateCommand()">
                    <option value="">-- Select Template --</option>
                    <?php foreach ($commandTemplates as $key => $template): ?>
                        <option value="<?= $key ?>"><?= $template['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="command">Command (JSON):</label>
                <textarea name="command" id="command"></textarea>
            </div>

            <div class="button-group">


            </div>
        </form>
        <button hx-post="src/GSClient.php"
            hx-target="#response"
            hx-vals='js:{"server": document.getElementById("server").value, "action": "test"}'>
            Ping Server
        </button>
        <button hx-post="src/GSClient.php"
            hx-target="#response"
            hx-vals='js:{"server": document.getElementById("server").value, "command": document.getElementById("command").value}'>
            Send Command
        </button>
        <textarea id="response" class="response" style="font-size: 10px;"></textarea>
    </div>
    <script>
        const commandTemplates = <?= json_encode($commandTemplates) ?>;
        
        function updateCommand() {
            const select = document.getElementById('template');
            const command = document.getElementById('command');
            const selectedTemplate = select.value;
            
            if (selectedTemplate && commandTemplates[selectedTemplate]) {
                command.value = commandTemplates[selectedTemplate].command;
            } else {
                command.value = '';
            }
        }
    </script>
</body>

</html>