<?php

class OttdAdmin{

    const USER_NAME_DEFAULT = "PHP OttdAdmin";
    const VERSION = '0.1.0';
    const RECIVE_LOOP_TIMING = 1;

    const ADMIN_PACKET_ADMIN_JOIN = 0;
    const ADMIN_PACKET_ADMIN_QUIT = 1;
    const ADMIN_PACKET_ADMIN_UPDATE_FREQUENCY = 2;
    const ADMIN_PACKET_ADMIN_POLL = 3;
    const ADMIN_PACKET_ADMIN_CHAT = 4;
    const ADMIN_PACKET_ADMIN_RCON = 5;
    const ADMIN_PACKET_ADMIN_GAMESCRIPT = 6;
    const ADMIN_PACKET_ADMIN_PING = 7;

    const ADMIN_PACKET_SERVER_FULL = 100;             
    const ADMIN_PACKET_SERVER_BANNED = 101;           
    const ADMIN_PACKET_SERVER_ERROR = 102;            
    const ADMIN_PACKET_SERVER_PROTOCOL = 103;         
    const ADMIN_PACKET_SERVER_WELCOME = 104;          
    const ADMIN_PACKET_SERVER_NEWGAME = 105;          
    const ADMIN_PACKET_SERVER_SHUTDOWN = 106;         
    const ADMIN_PACKET_SERVER_DATE = 107;             

    const ADMIN_PACKET_SERVER_CLIENT_JOIN = 108;       
    const ADMIN_PACKET_SERVER_CLIENT_INFO = 109;       
    const ADMIN_PACKET_SERVER_CLIENT_UPDATE = 110;     
    const ADMIN_PACKET_SERVER_CLIENT_QUIT = 111;       
    const ADMIN_PACKET_SERVER_CLIENT_ERROR = 112;      

    const ADMIN_PACKET_SERVER_COMPANY_NEW = 113;       
    const ADMIN_PACKET_SERVER_COMPANY_INFO = 114;      
    const ADMIN_PACKET_SERVER_COMPANY_UPDATE = 115;    
    const ADMIN_PACKET_SERVER_COMPANY_REMOVE = 116;    
    const ADMIN_PACKET_SERVER_COMPANY_ECONOMY = 117;   
    const ADMIN_PACKET_SERVER_COMPANY_STATS = 118;     

    const ADMIN_PACKET_SERVER_CHAT = 119;              
    const ADMIN_PACKET_SERVER_RCON = 120;              
    const ADMIN_PACKET_SERVER_CONSOLE = 121;           
    const ADMIN_PACKET_SERVER_CMD_NAMES = 122;         
    const ADMIN_PACKET_SERVER_CMD_LOGGING = 123;       
    const ADMIN_PACKET_SERVER_GAMESCRIPT = 124;        
    const ADMIN_PACKET_SERVER_RCON_END = 125;          
    const ADMIN_PACKET_SERVER_PONG = 126;              

    const INVALID_ADMIN_PACKET = 255;

    const ADMIN_REQESTS = [
        'ADMIN_UPDATE_DATE',            // < Updates about the date of the game.
        'ADMIN_UPDATE_CLIENT_INFO',     // < Updates about the information of clients.
        'ADMIN_UPDATE_COMPANY_INFO',    // < Updates about the generic information of companies.
        'ADMIN_UPDATE_COMPANY_ECONOMY', // < Updates about the economy of companies.
        'ADMIN_UPDATE_COMPANY_STATS',   // < Updates about the statistics of companies.
        'ADMIN_UPDATE_CHAT',            // < The admin would like to have chat messages.
        'ADMIN_UPDATE_CONSOLE',         // < The admin would like to have console messages.
        'ADMIN_UPDATE_CMD_NAMES',       // < The admin would like a list of all DoCommand names.
        'ADMIN_UPDATE_CMD_LOGGING',     // < The admin would like to have DoCommand information.
        'ADMIN_UPDATE_GAMESCRIPT',      // < The admin would like to have gamescript messages.
        'ADMIN_UPDATE_END',             // < Must ALWAYS be on the end of this list!! (period)
    ];

    private $password;
    private $ip;
    private $port;
    private $sock;
    private $server  = [];

    public function __construct($ip = "127.0.0.1", $port = 3977, $password = null) {
        $this->password = $password;
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * Returns info about connected server
     * @return array
     */
    public function getServerInfo()
    {
        return $this->server;
    }

    /**
     * Connect to server
     * @param string $ip (optional if is set in constructor)
     * @param int $port Admin port (optional if is set in constructor)
     * @return bool Connection was successfull
     */
    public function connect($ip = null, $port = null) {
        if(is_null($ip))
            $ip = $this->ip;
        if(is_null($port))
            $port = $this->port;

        $this->server = [];

        $this->sock = socket_create(AF_INET, SOCK_STREAM ,SOL_TCP);
        $connected = socket_connect($this->sock, $ip, $port);
        if(!$connected) return false;
        socket_set_nonblock($this->sock);
        return true;
    }

    /**
     * Render recived data as readable string, for debugging only
     * ASCII Characters, not between 32 - 122 will be displayed in curly parentheses,
     * in decimal format, zero byte will be displayed as ~
     * @param string $str raw (binary) recived or sent data
     * @internal
     * @return string display-able string
     */
    private function debug_datarender($str){
        $r = "";
        foreach (str_split($str) as $sym) {
            if(ord($sym) > 122 || ord($sym) < 32){
                if(ord($sym) == 0){
                    $r .= "~";
                }else{
                    $r .= "{".(ord($sym))."}";
                }
            }else{
                $r .= $sym;
            }
        }
        return $r;
    }

    /**
     * Helper for dataformat translation
     * from openttd cpp doc types, to php pack/unpack
     * and inbuild unpackPro
     * for int64 php machine must use same byte order as OpenTTD server
     * @param string $type to be converted
     * @param bool $bool_as_char - translate bool type to char instead of bool
     * @return string single char string for use with pack/unpack + T for string
     */
    private function packTypeHelper($type, bool $bool_as_char = false)
    {
        $readableTypes = [
            'bool' => 'B',
            'uint8' => 'C',
            'uint16' => 'v',
            'uint32' => 'V',
            'uint64' => 'P',
            'int64'  => 'q', //Warning !!! Php machine must use same byte order as OpenTTD server!
            'string' => 'T',
        ];
        if(strlen($type) > 1){
            if(array_key_exists(strtolower($type), $readableTypes)){
                $type = $readableTypes[strtolower($type)];
            }else{
                throw new Exception("Unknown pack type: $type", 640);
            }
        }
        if($bool_as_char && $type == 'B')
            return 'C';
        return $type;
    }

    /**
     * Build a packet formated for OTTD Admin Interface
     * @param int $packetMode Packet Format (see class's constants)
     * @param array $data data to send (primitive or [pack,data])
     * @return int number of setn bytes
     */
    private function sendAsPacket(int $packetMode, array $data)
    {
        $packet = chr($packetMode);
        foreach($data as $item){
            $part = '';
            if(is_array($item)){
                $part = pack($this->packTypeHelper($item[0], true), $item[1]);
            }elseif(is_int($item)){
                $part .= chr($item);
            }elseif(is_string($item)){
                $part = $item.chr(0);
            }
            $packet .= $part;
        }
        $packet = chr(strlen($packet)+2).chr(0).$packet;
        $sent = socket_write($this->sock, $packet, strlen($packet));
        //echo $sent."B >> ".$this->debug_datarender($packet)."\n";
        return $sent;
    }

    /**
     * Wait for specific packet type
     * @param null|int|array $packetMode - null for any, array of integers, or single integer (see class's constants: ADMIN_PACKET_SERVER_*)
     * @param null|int $sleepMicrotime - ms to wait between reciving data packet, null for default RECIVE_LOOP_TIMING
     * @param int $limitMicrotime - to wait only limited time (ms) until returning null (no result) - zero or les for infinity waiting
     * @return mixed ...
     *      null - limit exceeded, no data recived
     *      string (binary) - data of recived packet of selected type;
     *      object (mode, data) match one of wanted types, or any type when all wanted
     */
    private function awaitPacket($packetMode = null, int $sleepMicrotime = null, int $limitMicrotime = 0)
    {
        if(is_null($sleepMicrotime))
            $sleepMicrotime = self::RECIVE_LOOP_TIMING;

        $start = microtime(true); 
        while(!($len = socket_read($this->sock, 2))){
            usleep(max(1, $sleepMicrotime) * 1000);
            if($limitMicrotime > 0  && microtime(true) > $start+(0.001*$limitMicrotime)){
                //Wait timeout exceeded
                return null;
            }
        }

        $read = unpack('v', $len);
        $read = $read[1];
        //echo "To read: $read\n";
        $raw = socket_read($this->sock, $read-2);
        //$rawResponse = ($read+2)."B << ".$this->debug_datarender($len.$raw)."\n";
        $recivedMode = ord($raw[0]);

        if(!is_null($packetMode) && $recivedMode == $packetMode){
            return substr($raw,1);
        }elseif(is_array($packetMode) && in_array($recivedMode, $packetMode)){
            return (object) [
                "mode" => $recivedMode,
                "data" => substr($raw,1)
            ];
        }else{
            $this->onRecive($recivedMode, substr($raw,1));
            if(is_null($packetMode)){
                return (object) [
                    "mode" => $recivedMode,
                    "data" => substr($raw,1)
                ];
            }
            return $this->awaitPacket($packetMode, $sleepMicrotime, max(1, $start+(0.001*$limitMicrotime) - microtime(true)));
        }
    }

    /**
     * Unpack packet to specific data format
     * @param string $data - raw binaray recived string
     * @param array $format - ordered associative array of types (keyname => unpackPro Type)
     * @param string &$consumed - returns remaining - not used data 
     * @return array associative array with filled values from packet
     */
    private function unpackPro(string $data, array $format, &$consumed = null)
    {
        $result = [];
        $sizes = [
            'C' => 1,
            'v' => 2,
            'V' => 4,
            'P' => 8,
            'q' => 8
        ];
        foreach ($format as $name => $type) {
            $type = $this->packTypeHelper($type);
            if($type == 'T'){
                $text = "";
                $i = 0;
                while($data[$i] != chr(0)){
                    $text .= $data[$i];
                    ++$i;
                }
                $result[$name] = $text;
                $data = substr($data, $i+1);
            }elseif($type == 'B'){
                $value = unpack('C', $data);
                $data = substr($data, 1);
                $result[$name] = boolval($value[1]);
            }else{
                $value = unpack($type, $data);
                $data = substr($data, $sizes[$type]);
                $result[$name] = $value[1];
            }
        }
        $consumed = $data;
        return $result;
    }

    /**
     * Join server as adminstrator
     * @param string $password (optional if set in construstor)
     * @param string $name - Administration system name (will be visible in server's console)
     * @return array Server info - as recived after joining server
     */
    public function join($password = null, $name = null)
    {
        if(is_null($password))
            $password = $this->password;
        if(is_null($name))
            $name = self::USER_NAME_DEFAULT;

        $this->sendAsPacket(self::ADMIN_PACKET_ADMIN_JOIN,[
            $password,    
            $name,        
            self::VERSION
        ]);
    
        $data = $this->awaitPacket(self::ADMIN_PACKET_SERVER_PROTOCOL);
        $temp = unpack('Ca/Cb', $data);
        $data = substr($data, 2);
        $serverInfo = ["NETWORK_GAME_ADMIN_VERSION" => $temp['a'], "ADMIN_UPDATE" => []];
        $updates = boolval($temp['b']);

        while($updates){
            $temp = unpack('va/vb/Cc', $data);
            $bitset = [
                'ADMIN_FREQUENCY_POLL'      => boolval($temp['b'] & 0x01),
                'ADMIN_FREQUENCY_DAILY'     => boolval($temp['b'] & 0x02),
                'ADMIN_FREQUENCY_WEEKLY'    => boolval($temp['b'] & 0x04),
                'ADMIN_FREQUENCY_MONTHLY'   => boolval($temp['b'] & 0x08),
                'ADMIN_FREQUENCY_QUARTERLY' => boolval($temp['b'] & 0x10),
                'ADMIN_FREQUENCY_ANUALLY'   => boolval($temp['b'] & 0x20),
                'ADMIN_FREQUENCY_AUTOMATIC' => boolval($temp['b'] & 0x40),
            ];

            $serverInfo["ADMIN_UPDATE"][self::ADMIN_REQESTS[$temp['a']]] = $bitset;
            $updates = boolval($temp['c']);
            if($updates)
                $data = substr($data, 5);
        }

        $this->server = $serverInfo;

        $data = $this->awaitPacket(self::ADMIN_PACKET_SERVER_WELCOME);
        $format = [
            'SERVER_NAME'    => 'string',
            'SERVER_VERSION' => 'string',
            'IS_DEDICATED'   => 'bool',
            'MAP_NAME'       => 'string',
            'MAP_SEED'       => 'uint32',
            'LANDSCAPE_TYPE' => 'uint8',
            'START_YEAR'     => 'uint32',
            'MAP_X'          => 'uint16',
            'MAP_Y'          => 'uint16',
        ];
        $this->server = array_merge($this->server, $this->unpackPro($data, $format));
        
        return $this->server;
    }

    /**
     * Will be triggered upon reciving "not wanted" packet
     * @param int $event - event id (see class's constants: ADMIN_PACKET_SERVER_*)
     * @param string $data - binary string of raw recived data
     */
    private function onRecive($event, $data)
    {
        $recivableEvents = [
            100 => 'ADMIN_PACKET_SERVER_FULL',
            101 => 'ADMIN_PACKET_SERVER_BANNED',
            102 => 'ADMIN_PACKET_SERVER_ERROR',
            103 => 'ADMIN_PACKET_SERVER_PROTOCOL',
            104 => 'ADMIN_PACKET_SERVER_WELCOME',
            105 => 'ADMIN_PACKET_SERVER_NEWGAME',
            106 => 'ADMIN_PACKET_SERVER_SHUTDOWN',
            107 => 'ADMIN_PACKET_SERVER_DATE',
            108 => 'ADMIN_PACKET_SERVER_CLIENT_JOIN',
            109 => 'ADMIN_PACKET_SERVER_CLIENT_INFO',
            110 => 'ADMIN_PACKET_SERVER_CLIENT_UPDATE',
            111 => 'ADMIN_PACKET_SERVER_CLIENT_QUIT',
            112 => 'ADMIN_PACKET_SERVER_CLIENT_ERROR',
            113 => 'ADMIN_PACKET_SERVER_COMPANY_NEW',
            114 => 'ADMIN_PACKET_SERVER_COMPANY_INFO',
            115 => 'ADMIN_PACKET_SERVER_COMPANY_UPDATE',
            116 => 'ADMIN_PACKET_SERVER_COMPANY_REMOVE',
            117 => 'ADMIN_PACKET_SERVER_COMPANY_ECONOMY',
            118 => 'ADMIN_PACKET_SERVER_COMPANY_STATS',
            119 => 'ADMIN_PACKET_SERVER_CHAT',
            120 => 'ADMIN_PACKET_SERVER_RCON',
            121 => 'ADMIN_PACKET_SERVER_CONSOLE',
            122 => 'ADMIN_PACKET_SERVER_CMD_NAMES',
            123 => 'ADMIN_PACKET_SERVER_CMD_LOGGING',
            124 => 'ADMIN_PACKET_SERVER_GAMESCRIPT',
            125 => 'ADMIN_PACKET_SERVER_RCON_END',
            126 => 'ADMIN_PACKET_SERVER_PONG',
            255 => 'INVALID_ADMIN_PACKET',
        ];
        if(array_key_exists($event, $recivableEvents)){
            echo "Recived message: '".$recivableEvents[$event]."'...\n";
        }else{
            echo "Recived message: UNKNOWN=".$event." ...\n";
        }
        echo "<< ".$this->debug_datarender($data)."\n";
    }

    /**
     * Run loop and listen for all packets
     * @param int $looptime_ms as in awaitPacket
     * @param int $timeout_ms as in awaitPacket
     */
    public function processRequests(int $looptime_ms = null, int $timeout_ms = 0)
    {
        $r = true;
        while (!is_null($r)) {
            $r = $this->awaitPacket(
                null,
                $looptime_ms,
                $timeout_ms
            );
        }
    }

    /**
     * Send admin - poll packet, with parameter
     * @param string $command One of commands defined in ADMIN_REQESTS
     * @param int $param poll parameter (id of required item, or 0xFFFFFFFF for all)
     * @return int number of setn bytes
     */
    protected function poll(string $command, int $param = 0xFFFFFFFF)
    {
        $command = strtoupper($command);
        if(!in_array($command, self::ADMIN_REQESTS)){
            throw new Exception("Unknown request!", 54);
        }
        if(!$this->server["ADMIN_UPDATE"][$command]["ADMIN_FREQUENCY_POLL"]){
            throw new Exception("Unable to poll '$command' manualy!", 55);
        }
        $cmdId = array_search($command, self::ADMIN_REQESTS);
        return $this->sendAsPacket(self::ADMIN_PACKET_ADMIN_POLL, [
            $cmdId,    
            ['V',$param]
        ]);
    }

    /**
     * Get date
     * @return int date in mysterious OpenTTD format
     */
    public function getDate()
    {
        $this->poll('ADMIN_UPDATE_DATE');
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_DATE);
        $response = $this->unpackPro($response, ["date"=>"uint32"] /*["M"=>"C","D"=>"C","Y"=>"v"]*/);
        //727337 = 1991-05-20
        //727397 = 1991-07-15
        //727398 = 1991-07-20 
        // ?????
        return $response["date"];
    }

    /**
     * Get Client Info
     * @param int client id (0xFFFFFFFF for all clients)
     * @return array (array of - if all queried) associative array with client information
     */
    public function getClientInfo(int $clientId = 0xFFFFFFFF)
    {
        $this->poll('ADMIN_UPDATE_CLIENT_INFO', $clientId);
        $clients = [];
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_CLIENT_INFO, self::RECIVE_LOOP_TIMING, 500);
        while(!is_null($response)){
            $response = $this->unpackPro($response, [
                "CLIENT_ID" =>      "uint32",
                "CLIENT_HOSTNAME"=> "string",
                "CLIENT_NAME" =>    "string",
                "CLIENT_LANG" =>    "uint8",
                "JOIN_DATE" =>      "uint32",
                "PLAY_AS"   =>      "uint8",
            ]);
            if($clientId != 0xFFFFFFFF){
                return $response;
            }
            $clients[] = $response;
            $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_CLIENT_INFO, self::RECIVE_LOOP_TIMING, 500);
        }
        return $clients;
    }

    /**
     * Get Company Info
     * @param int company id (0xFFFFFFFF for all companies)
     * @return array (array of - if all queried) associative array with company information
     */
    public function getCompanyInfo(int $companyId = 0xFFFFFFFF)
    {
        $this->poll('ADMIN_UPDATE_COMPANY_INFO', $companyId);
        $companies = [];
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_INFO, self::RECIVE_LOOP_TIMING, 500);
        while(!is_null($response)){
            $response = $this->unpackPro($response, [
                "COMPANY_ID"    => 'uint8',   // ID of the company.
                "COMPANY_NAME"  => 'string',  // Name of the company.
                "MANAGER"       => 'string',  // Name of the companies manager.
                "COLOR"         => 'uint8',   // Main company colour.
                "HAVE_PASSWORD" => 'bool',    // Company is password protected.
                "START_DATE"    => 'uint32',  // Year the company was inaugurated.
                "IS_AI"         => 'bool',    // Company is an AI.
            ]);
            if($companyId != 0xFFFFFFFF){
                return $response;
            }
            $companies[] = $response;
            $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_INFO, self::RECIVE_LOOP_TIMING, 500);
        }
        return $companies;
    }

    /**
     * Get Company Economy
     * @param int client id (0xFFFFFFFF for all companies)
     * @return array (array of - if all queried) associative array with company economy information
     */
    public function getCompanyEconomy(int $companyId = 0xFFFFFFFF)
    {
        $this->poll('ADMIN_UPDATE_COMPANY_ECONOMY', $companyId);
        $companies = [];
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_ECONOMY, self::RECIVE_LOOP_TIMING, 500);
        while(!is_null($response)){
            $response = $this->unpackPro($response, [
                "COMPANY_ID"    =>'uint8',   //ID of the company.
                "MONEY"         =>'uint64',  //Money.
                "LOAN"          =>'uint64',  //Loan.
                "INCOME"        =>'int64',   //Income.
                "DELIVER_THISQ" =>'uint16',  //Delivered cargo (this quarter).
                "VALUE_LASTQ"   =>'uint64',  //Company value (last quarter).
                "PERF_LASTQ"    =>'uint16',  //Performance (last quarter).
                "DELIVER_LASTQ" =>'uint16',  //Delivered cargo (last quarter).
                "VALUE_PREVQ"   =>'uint64',  //Company value (previous quarter).
                "PERF_PREVQ"    =>'uint16',  //Performance (previous quarter).
                "DELIVER_PREVQ" =>'uint16',  //Delivered cargo (previous quarter).
            ]);
            if($companyId != 0xFFFFFFFF){
                return $response;
            }
            $companies[] = $response;
            $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_ECONOMY, self::RECIVE_LOOP_TIMING, 500);
        }
        return $companies;
    }

    /**
     * Get Company Stats
     * @param int client id (0xFFFFFFFF for all companies)
     * @return array (array of - if all queried) associative array with company statistics
     */
    public function getCompanyStats(int $companyId = 0xFFFFFFFF)
    {
        $this->poll('ADMIN_UPDATE_COMPANY_STATS', $companyId);
        $companies = [];
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_STATS, self::RECIVE_LOOP_TIMING, 500);
        while(!is_null($response)){
            $response = $this->unpackPro($response, [
                "COMPANY_ID"           =>'uint8',    //ID of the company.
                "TRAINS_COUNT"         => 'uint16',  //Number of trains.
                "LORRIES_COUNT"        => 'uint16',  //Number of lorries.
                "BUSSES_COUNT"         => 'uint16',  //Number of busses.
                "PLANES_COUNT"         => 'uint16',  //Number of planes.
                "SHIPS_COUNT"          => 'uint16',  //Number of ships.
                "TRAIN_STATIONS_COUNT" => 'uint16',  //Number of train stations.
                "LORRY_STATIONS_COUNT" => 'uint16',  //Number of lorry stations.
                "BUSS_STOPS_COUNT"     => 'uint16',  //Number of bus stops.
                "AIRPORTS_COUNT"       => 'uint16',  //Number of airports and heliports.
                "HARBOURS_COUNT"       => 'uint16',  //Number of harbours.
            ]);
            if($companyId != 0xFFFFFFFF){
                return $response;
            }
            $companies[] = $response;
            $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_COMPANY_STATS, self::RECIVE_LOOP_TIMING, 500);
        }
        return $companies;

    }

    /**
     * Get Cmd names
     * @param int cmdlet id (0xFFFFFFFF for all)
     * @return array (array of - if all queried) numeric - id based - array with cmdlets
     */
    public function getCmdNames(Type $var = null)
    {
        $this->poll('ADMIN_UPDATE_CMD_NAMES');
        $cmds = [];
        $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_CMD_NAMES, self::RECIVE_LOOP_TIMING, 500);
        while(!is_null($response) && strlen($response)){
            while(ord($response[0])){
                $response = substr($response,1);
                $tmp = $this->unpackPro($response, [
                        "cmdid"=>"uint16",
                        'command'=>"string"
                    ], $cut);
                $response = $cut;
                $cmds[$tmp["cmdid"]] = $tmp["command"];

            }
            $response = $this->awaitPacket(self::ADMIN_PACKET_SERVER_CMD_NAMES, self::RECIVE_LOOP_TIMING, 500);
        }
        return $cmds;
    }

    /**
     * Send admin command
     * @param string $command to be executed
     * @param bool $simpleOutput - include colors and command in output
     * @return array lines of text returned by server
     */
    public function console(string $command, bool $simpleOutput = true)
    {
        $this->sendAsPacket(self::ADMIN_PACKET_ADMIN_RCON, [
            $command,
        ]);
        $output = [];
        while(1){
            $response = $this->awaitPacket([self::ADMIN_PACKET_SERVER_RCON,self::ADMIN_PACKET_SERVER_RCON_END]);
            if($response->mode == self::ADMIN_PACKET_SERVER_RCON){
                $output[] = $this->unpackPro($response->data, [
                    "COLOR"=>"uint16",
                    "TEXT"=>"string"
                ]);
            }elseif($response->mode == self::ADMIN_PACKET_SERVER_RCON_END){
                $output[] = $this->unpackPro($response->data, [
                    "COMMAND"=>"string"
                ]);
                break;
            }else{
                throw new Exception("Program error", 990);
            }
        }
        if($simpleOutput){
            return join("\n",array_column($output, "TEXT"));
        }
        return $output;
    }

}
