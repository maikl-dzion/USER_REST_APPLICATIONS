<?php

class UserClass {

    // подключение к БД таблице "users"
    private $db;
    private $tableName = "users";

    // свойства объекта
    public $userId;
    protected $params   = array();
    protected $data     = array();
    protected $saveInfo;

    // Конструктор класса
    public function __construct($params = array(), $db = null) {
        $this->params = $params;
        $this->connect();
    }

    public function getUser() {
        $this->userId = $this->params[0];
        return $this->fetch('user_id', $this->userId, '*');
    }

    public function getUsers() {
        return $this->fetch();
    }

    protected function fetch($fieldName = '', $value = '', $selFields = '*') {
        $where = '';
        if($fieldName && $value) {
            $where = " WHERE {$fieldName} = :{$fieldName}";
        }

        $query = "SELECT {$selFields} FROM {$this->tableName} " . $where;
        $stmt = $this->db->prepare($query);
        if($fieldName && $value)
          $stmt->bindParam(":{$fieldName}", $value);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row;

    }

    public function createUser() {

        $this->postData();

        // Проверка на обязательные поля
        if( empty($this->data['login'])    ||
            empty($this->data['password']) ||
            empty($this->data['username']) ||
            empty($this->data['email'])) {

            http_response_code(400);
            return array("message" => "Невозможно создать пользователя, отсутствуют обязательные поля");
        }

        $status = $this->create();

        if(!$status) {
            http_response_code(400);
            return array("message" => "Невозможно создать пользователя ({$this->saveInfo->errMessage})");
        }

        // http_response_code(200);
        return array("message" => "Пользователь успешно создан");
    }

    public function updateUser() {

        $this->postData();
        $this->userId = $this->params[0];

        // Проверка на обязательные поля
        if( empty($this->data['login'])    ||
            // empty($this->data['password']) ||
            empty($this->data['username']) ||
            empty($this->data['email'])) {

            http_response_code(400);
            return array("message" => "Отсутствуют обязательные поля");
        }

        $status = $this->update($this->userId);

        if(!$status) {
            http_response_code(400);
            return array("message" => "Не удалось сохранить данные ({$this->saveInfo->errMessage})");
        }

        $user = $this->fetch('user_id', $this->userId, '*');
        return array("message" => "Данные успешно сохранены", "user" => $user);

    }

    protected function postData($cast = 'array') {
        $data = json_decode(file_get_contents("php://input"));
        switch ($cast) {
            case 'array' :
                $data = (array)$data; break;
        }
        $this->data = $data;
    }

    // Создание нового пользователя
    protected function create() {
        // $this->truncateTable("users");
        $data   = $this->data;
        $fields = $this->getFields();
        $builder  = $this->insertBuilder($fields, $data);

        $sql = $builder['query'];
        $values = $builder['values'];
        $query  = "INSERT INTO {$this->tableName} {$sql}";
        $values[':password'] = password_hash($values[':password'], PASSWORD_BCRYPT);

        $resp = $this->bindExecute($query, $values);
        $this->saveInfo($resp);
        if(empty($resp['status']))
           return false;
        return true;
    }

    // Обновить запись пользователя
    public function update($userId = ''){
        if(!$userId)
             $userId = $this->userId;
        $data   = $this->data;
        $fields = $this->getFields();
        if(isset($data['password']))
            unset($data['password']);
        $builder  = $this->updateBuilder($fields, $data);

        $sql = $builder['query'];
        $values = $builder['values'];
        $values[':user_id']  = $userId;
        $query  = "UPDATE {$this->tableName} SET {$sql} WHERE user_id = :user_id";

        $resp = $this->bindExecute($query, $values);
        $this->saveInfo($resp);
        if(empty($resp['status']))
            return false;
        return true;
    }


    protected function getFields(){
        $fields = array(
            'login'    => '',
            'username' => '',
            'password' => '',
            'email'    => '',
        );
        return $fields;
    }


    // Получаем соединение с базой данных
    protected function connect() {
        $this->db = null;
        $conf = $this->config();
        $dsn = "{$conf->driver}:host={$conf->host};dbname={$conf->dbname};port={$conf->port}";
        try {
            $this->db = new PDO($dsn, $conf->user, $conf->password);
        } catch(PDOException $exception) {
            echo "DB Connection error: " . $exception->getMessage();
        }
        return $this->db;
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

    protected function insertBuilder($fields, $data) {
         $_fields = $_alias = $_values = array();

         foreach ($fields as $fieldName => $value) {
             if(!empty($data[$fieldName])) {

                 $value = $data[$fieldName];
                 $alias = ":" . $fieldName;

                 $_fields[] = $fieldName;
                 $_alias[]  = $alias;
                 $_values[$alias] = $value;
             }
         }

         $fieldsStr = implode(',', $_fields);
         $aliasStr = implode(',' , $_alias);
         $query    = "({$fieldsStr})  VALUES({$aliasStr})";
         return array( 'query' => $query, 'values' => $_values);
    }

    protected function bindExecute($query, $data, $debugShow = false) {
        $stmt = $this->db->prepare($query);
        foreach ($data as $fieldName => $value) {
            // $value = htmlspecialchars(strip_tags($value));
            $stmt->bindValue("{$fieldName}" , $value);
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

    protected function truncateTable($tableName) {
        $stmt = $this->db->prepare("TRUNCATE TABLE  {$tableName}");
        $result = $stmt->execute();
        return $result;
    }

    protected function saveInfo($resp) {

        $info = new stdClass();
        $info->status = $resp['status'];
        $info->error  = $resp['error'];
        $info->stmt   = $resp['stmt'];
        $info->debug  = $resp['debug'];

        $info->errMessage = '';
        $info->query      = '';
        $info->errCode    = '';
        $info->errCodeNum = '';

        if(!empty($resp['error'][0]))
            $info->errCode = $resp['error'][0];

        if(!empty($resp['error'][1]))
            $info->errCodeNum = $resp['error'][1];

        if(!empty($resp['error'][2]))
            $info->errMessage = $resp['error'][2];

        if(!empty($resp['stmt']->queryString))
            $info->query = $resp['stmt']->queryString;

        $this->saveInfo = $info;

        //  [0] => 23505
        //  [1] => 7
        //  [2] => ERROR:  duplicate key value violates unique constraint "users_login_key"
    }


    //    protected function sqlInsertBuilder($fields, $data){
//        $sqlFields = $sqlValues = array();
//        foreach($fields as $fieldName => $value) {
//            if(!empty($data[$fieldName])) {
//                $sqlFields[] = $fieldName;
//                $sqlValues[] = ':' . $fieldName;
//            }
//        }
//        $_fields = implode(',', $sqlFields);
//        $_values = implode(',', $sqlValues);
//        $query   = "({$_fields}) VALUES ({$_values})";
//        return $query;
//    }
//
//    protected function sqlUpdateBuilder($fields, $data){
//        $sqlValues = array();
//        foreach($fields as $fieldName => $value) {
//            if(!empty($data[$fieldName])) {
//                $sqlValues[] = "{$fieldName} = :{$fieldName}";
//            }
//        }
//
//        $query = implode(',', $sqlValues);
//        return $query;
//    }
//
//    protected function bind($fields, $data, $query, $debugShow = false) {
//        $debug = $tmp = array();
//        $stmt = $this->db->prepare($query);
//        foreach($fields as $fieldName => $value) {
//            if(!empty($data[$fieldName])) {
//                $value = $data[$fieldName];
//                // $value = htmlspecialchars(strip_tags($data[$fieldName]));
//                $stmt->bindParam(":{$fieldName}", $value, PDO::PARAM_STR);
//                $tmp[] = "$fieldName=$value";
//            }
//        }
//
//        // lg($fields, $tmp, $stmt);
//
//        $status = $stmt->execute();
//        $error  = $stmt->errorInfo();
//        if($debugShow)
//          $debug  = $stmt->debugDumpParams();
//
//        return array(
//            'status' => $status,
//            'error'  => $error,
//            'stmt'   => $stmt,
//            'debug'  => $debug
//        );
//    }



//    // Проверка, существует ли электронная почта в нашей базе данных
//    protected function emailExists(){
//
//        // запрос, чтобы проверить, существует ли электронная почта
//        $query = "SELECT id, firstname, lastname, password
//                  FROM " . $this->tableName . "
//                  WHERE email = ?
//                  LIMIT 0,1";
//
//        // подготовка запроса
//        $stmt = $this->db->prepare( $query );
//
//        // инъекция
//        $this->email=htmlspecialchars(strip_tags($this->email));
//
//        // привязываем значение e-mail
//        $stmt->bindParam(1, $this->email);
//
//        // выполняем запрос
//        $stmt->execute();
//
//        // получаем количество строк
//        $num = $stmt->rowCount();
//
//        // если электронная почта существует,
//        // присвоим значения свойствам объекта для легкого доступа и использования для php сессий
//        if($num>0) {
//
//            // получаем значения
//            $row = $stmt->fetch(PDO::FETCH_ASSOC);
//
//            // присвоим значения свойствам объекта
//            $this->id = $row['id'];
//            $this->firstname = $row['firstname'];
//            $this->lastname = $row['lastname'];
//            $this->password = $row['password'];
//
//            // вернём 'true', потому что в базе данных существует электронная почта
//            return true;
//        }
//
//        // вернём 'false', если адрес электронной почты не существует в базе данных
//        return false;
//    }

//    // обновить запись пользователя
//    public function update1(){
//
//        // Если в HTML-форме был введен пароль (необходимо обновить пароль)
//        $password_set=!empty($this->password) ? ", password = :password" : "";
//
//        // если не введен пароль - не обновлять пароль
//        $query = "UPDATE " . $this->tableName . " SET
//                firstname = :firstname,
//                lastname = :lastname,
//                email = :email
//                {$password_set}
//            WHERE id = :id";
//
//        // подготовка запроса
//        $stmt = $this->db->prepare($query);
//
//        // инъекция (очистка)
//        $this->firstname=htmlspecialchars(strip_tags($this->firstname));
//        $this->lastname=htmlspecialchars(strip_tags($this->lastname));
//        $this->email=htmlspecialchars(strip_tags($this->email));
//
//        // привязываем значения с HTML формы
//        $stmt->bindParam(':firstname', $this->firstname);
//        $stmt->bindParam(':lastname', $this->lastname);
//        $stmt->bindParam(':email', $this->email);
//
//        // метод password_hash () для защиты пароля пользователя в базе данных
//        if(!empty($this->password)){
//            $this->password=htmlspecialchars(strip_tags($this->password));
//            $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
//            $stmt->bindParam(':password', $password_hash);
//        }
//
//        // уникальный идентификатор записи для редактирования
//        $stmt->bindParam(':id', $this->user_id);
//
//        // Если выполнение успешно, то информация о пользователе будет сохранена в базе данных
//        if($stmt->execute()) {
//            return true;
//        }
//
//        return false;
//    }

    public function testFunc() {

        $fields = array(
            'login'     => '',
            'username'  => '',
            'password'  => '',
            'email'     => ''
        );

        $data = array(
            'login'     => 'u_3',
            'username'  => 'maikl3',
            'password'  => '1234',
            'email'     => 'dzion673@mail.ru'
        );

        $builder = $this->insertBuilder($fields, $data);
        $query = "INSERT INTO users " . $builder['query'];
        $stmt = $this->bindExecute($query, $builder['values']);
    }

    protected function updateBuilder($fields, $data){
        $queryArray = $_values = array();
        foreach($fields as $fieldName => $value) {
            if(!empty($data[$fieldName])) {
                $alias = ":{$fieldName}";
                $value = $data[$fieldName];
                $queryArray[] = "{$fieldName} = {$alias}";
                $_values[$alias]  = $value;
            }
        }

        $query = implode(',', $queryArray);
        return array( 'query' => $query, 'values' => $_values);
    }




}