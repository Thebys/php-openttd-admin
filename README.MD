# PHP OpenTTD Stats

> Verison 0.2.1

## About repository
Basedon PHP OpenTTD Admin. 

## Prequisites:
1. Have enabled admin port
    - In openttd server console: (or in settings set)
    - `setting server_admin_port 3977` *...or any port you like*
    - Don't forget to allow NAT port forwarding, or checking firewall
2. Have admin password set
    - `setting admin_password "YOURPASSWORD"`
3. If you are just enabled network administration restart OpenTTD server
4. In php, **sockets module** must be enabled, php version **7.2+** (recommended 64bit)

## Installation
1. Copy config.example.php to config.php and edit it to your needs.
2. Run install.php to create the database.
3. ... TBD ...
