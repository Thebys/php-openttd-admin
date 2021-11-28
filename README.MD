# PHP OpenTTD Admin

> Verison 0.1.0

## About repository
This script is in some early phase now, it can run some commands on demand, but my target is to make this `class OttdAdmin` a universal PHP controller - which can be extended to further processing requests (OpenTTD game events), and to be able to run as daemon service or on demand.
For example from web request...

## Documentation
See [OttdAdmin.php](OttdAdmin.php), better documentation will be added in future :)

## Prequisites:
1. Have enabled admin port
    - In openttd server console: (or in settings set)
    - `setting server_admin_port 3977` *...or any port you like*
    - Don't forget to allow NAT port forwarding, or checking firewall
2. Have admin password set
    - `setting admin_password "YOURPASSWORD"`
3. If you are just enabled network administration restart OpenTTD server
4. In php, sockets module must be enabled
5. Save **Example.php** and **OttdAdmin.php** to same folder...
   - Edit hostname/ip, port and password
   - In cli run `php Example.php` or in your browser navigare to your `Example.php`
   - You should get response like [HERE](example-output.txt)


-----
> Feel free to discuss, open issue and fork this repository  
> @TooMeeNoo 2021

