# PHP OpenTTD Stats

> Verison 0.3.2

## About repository
Basedon PHP OpenTTD Admin. Watch companies competing in OpenTTD via web dashboard via OpenTTD admin port.

![screenshot](https://i.imgur.com/Mki3zjJ.png)

## Prequisites:
1. Have enabled admin port
    - In openttd server console: (or in settings set)
    - `setting server_admin_port 3977` *...or any port you like*
    - Don't forget to allow NAT port forwarding, or checking firewall
2. Have admin password set
    - `setting admin_password "YOURPASSWORD"`
3. If you are just enabled network administration restart OpenTTD server
4. In php, **sockets module** must be enabled, php version **7.2+** (recommended 64bit)
5. Composer must be installed, use it to install dependencies (sqlite3, illuminate/database)
6. Also your PHP server needs to have sqlite3 and pdo_sqlite extensions enabled

## Installation
1. Copy config.example.php to config.php and edit it to your needs.
2. Run install.php to create the database.
3. Visit index.php in your browser, click start fetching and enjoy!
