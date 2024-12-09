<?php
require 'src/bootstrap.php';
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
                <label for="command">Command (JSON):</label>
                <textarea name="command" id="command">{"action": "call", "number": 123456789, "method": "GSCompany.GetName","args": [0]}</textarea>
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
</body>

</html>