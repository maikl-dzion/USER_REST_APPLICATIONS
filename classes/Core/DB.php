<?php


class DB implements DbInterface {

    protected $pdo;
    protected $config = array();

    public function __construct($config = array()) {
        $this->config = $config;
    }

    public function connect($config = array()) {
        if(empty($config))
           $config = $this->config;

        $driver = $config['driver'];
        $dbname = $config['dbname'];
        $host   = $config['host'];
        $port   = $config['port'];
        $user   = $config['user'];
        $password = $config['password'];

        try {
            $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
            $dsn = "{$driver}:dbname={$dbname};host={$host};port={$port}";
            $this -> pdo = new PDO($dsn, $user, $password, $options);

        } catch (PDOException $e) {

            $path = $e->getFile();// Полный путь к файлу, где возникло исключение
            $line = $e->getLine();// Номер строки, где возникло исключение
            $message = $e->getMessage();// Текстовое сообщение исключения
            $code    = $e->getCode();

            $letter = __CLASS__ . '_' .__FUNCTION__ . '_' . __LINE__;
            $errorArray = array($path, $line, $message, $code, $letter);
            $this->log($errorArray, 'DB-connect');
        }

        return $this -> pdo;
    }

    public function setConfig(array $config) {
        $this->config = $config;
    }

    public function select($query, $index = false) {
        $stmt = $this -> pdo -> query($query);
        if(!$stmt) $this->error($this ->pdo->errorInfo());
        $result = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        if(!empty($result[$index]))
            $result = $result[$index];
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
            $path = $e->getFile();// Полный путь к файлу, где возникло исключение
            $line = $e->getLine();// Номер строки, где возникло исключение
            $message = $e->getMessage();// Текстовое сообщение исключения
            $code    = $e->getCode();

            $logName = __FILE__ . '_' .__FUNCTION__ . '_' . __LINE__;
            $errorArray = array($path, $line, $message, $code, $logName);
            $this->log($errorArray, 'DB-exec');
            $this->error($errorArray);
        }
        return $result;
    }

    public function getPdoLink() {
        return $this -> pdo;
    }

    public function prepare($query, $data = array()) {
        $stmt = $this->pdo->prepare($query);
        if(!empty($data)) {
            $status = $stmt->execute($data);
            $error  = $stmt->errorInfo();
            $debug = array();
            // $debug  = $stmt->debugDumpParams();
            return array(
                'status' => $status,
                'error'  => $error,
                'stmt'   => $stmt,
                'debug'  => $debug
            );
        }
        return $stmt;
    }

    public function insertBuilderPrepare($fields, $data) {
        $_fields = $_alias = $_values = array();

        foreach ($fields as $fieldName => $fieldValue) {
            if(!empty($data[$fieldName])) {

                $value = $data[$fieldName];
                $alias = ":{$fieldName}";

                $_fields[] = $fieldName;
                $_alias[]  = $alias;
                $_values[$fieldName] = $value;
            }
        }

        $fieldsStr = implode(',', $_fields);
        $aliasStr = implode(',' , $_alias);
        $query    = "({$fieldsStr})  VALUES({$aliasStr})";
        return array( 'query' => $query, 'values' => $_values);
    }

    public function updateBuilderPrepare($fields, $data){
        $queryArray = $_values = array();
        foreach($fields as $fieldName => $fieldValue) {
            if(!empty($data[$fieldName])) {
                $alias = ":{$fieldName}";
                $value = $data[$fieldName];
                $queryArray[] = "{$fieldName} = {$alias}";
                $_values[$fieldName]  = $value;
            }
        }

        $query = implode(',', $queryArray);
        return array( 'query' => $query, 'values' => $_values);
    }

    public function bindExecute($query, $data, $debugShow = false) {
        $stmt = $this->pdo->prepare($query);
        foreach ($data as $fieldName => $value) {
            // $value = htmlspecialchars(strip_tags($value));
            $stmt->bindValue(":{$fieldName}" , $value);
        }
        $debug  = $error = array();
        $status = $stmt->execute();
        $error  = $stmt->errorInfo();
        if($debugShow)
            $debug  = $stmt->debugDumpParams();
        return array(
            'status' => $status,
            'error'  => $error,
            'stmt'   => $stmt,
            'debug'  => $debug
        );
    }

    public function getTableFields($tableName, $formatted = true) {
        $sql = "SELECT column_name, column_default, data_type 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE table_name = '" .$tableName . "'; ";       
        $result = $this ->select($sql);
        if($formatted)
            $result = $this->tableFieldsFormatted($result);
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

    public function stmtLog($resp) {

        $info = new stdClass();
        $info->status = $resp['status'];
        $info->error  = $resp['error'];
        $info->stmt   = $resp['stmt'];
        $info->debug  = $resp['debug'];

        $info->query      = '';
        $info->errCode    = '';
        $info->errCodeNum = '';
        $info->errMessage = '';

        if(!empty($resp['error'][0]))
            $info->errCode = $resp['error'][0];

        if(!empty($resp['error'][1]))
            $info->errCodeNum = $resp['error'][1];

        if(!empty($resp['error'][2]))
            $info->errMessage = $resp['error'][2];

        if(!empty($resp['stmt']->queryString))
            $info->query = $resp['stmt']->queryString;

        return $info;

        //  [0] => 23505
        //  [1] => 7
        //  [2] => ERROR:  duplicate key value violates unique constraint "users_login_key"
    }

    public function error($params = '', $trace = false) {
        $debag = '';
        if($trace) $debag = debug_backtrace();
        print_r($params);
        print_r($debag);
        die();
    }

    public function truncateTable($tableName) {
        $stmt = $this->pdo->prepare("TRUNCATE TABLE  {$tableName}");
        $result = $stmt->execute();
        return $result;
    }

    public function createTable($tableName, $fileds) {
        $query = "CREATE TABLE IF NOT EXISTS {$tableName} ({$fileds})";
        $result = $this->pdo->exec($query);
        return $result;
    }

    public function deleteTable($tableName) {
        $query = "DROP TABLE {$tableName}";
        $result = $this->pdo->exec($query);
        return $result;
    }

    protected function config() {
        $conf = new stdClass();
        $conf->host     = '185.63.191.96';
        $conf->dbname   = 'maikldb';
        $conf->user     = 'w1user';
        $conf->password = 'w1password';
        $conf->driver   = 'pgsql';
        $conf->port     = 5432;
        return $conf;
    }

    public function log($data, $name = "DB-logger", $file = null) {
        saveLog($name, $data, $file);
    }
}


