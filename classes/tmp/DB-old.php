<?php

interface DbInterface {
    public function select($uery);
    public function exec($uery);
    public function connect();
}

class DB implements DbInterface {

    protected $pdo;
    protected $config = array();

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function connect() {

        $config = $this->config;

        $driver = $config['driver'];
        $dbname = $config['dbname'];
        $host   = $config['host'];
        $port   = $config['port'];
        $user   = $config['user'];
        $password = $config['password'];

        $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
        $dsn = "{$driver}:dbname={$dbname};host={$host};port={$port}";
        $this -> pdo = new PDO($dsn, $user, $password, $options);
    }

    public function setConfig(array $config) {
        $this->config = $config;
    }

    public function select($query) {
        $stmt = $this -> pdo -> query($query);
        if(!$stmt) $this->error($this ->pdo->errorInfo());
        $result = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }


    public function fetch($query) {
        $stmt = $this -> pdo -> query($query);
        if(!$stmt) $this->error($this ->pdo->errorInfo());
        $result = $stmt -> fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public function exec($query) {
        try {
           $result = $this -> pdo -> exec($query);
        } catch (PDOException $e) {
            // if ($e->getCode() == '2A000')
            $this->error($e->getMessage());
        }
        
        return $result;
    }

    public function getPdo() {
        return $this -> pdo;
    }

    public function getTableFields($tableName, $formatted = true) {
        $result = array();
        $sql = "SELECT column_name, column_default, data_type 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = '" .$tableName . "'; ";       
        $result = $this ->select($sql);
        
        if($formatted) {
            $result = $this->tableFieldsFormatted($result);
        }

        return $result;
    }

    public function tableFieldsFormatted($arr = array()) {
    	
        $result = array();
        
        if(empty($arr)) return array();

        foreach ($arr as $key => $value) {

            $name    = $value['column_name'];
            $type    = $value['data_type'];
            $default = $value['column_default'];

            switch ($type) {
                case 'character varying':
                case 'integer': $type = 'text';     break;
                case 'boolean': $type = 'checkbox'; break;
                case 'text'   : $type = 'textarea'; break;
            }

            $item = array(
               'row'       => 1,
               'date_type' => $value['data_type'],
               'type'      => $type,
               'label'     => $name,
            );

            $result[$name] = $item;
        }

        return $result;  
    }
    
    public function error($params = '', $trace = false) {
        $debag = '';
        if($trace) $debag = debug_backtrace();
        print_r($params);
        print_r($debag);
        die();
    }

}


