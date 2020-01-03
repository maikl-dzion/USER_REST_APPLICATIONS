<?php

class UserClass extends BaseController {

    // подключение к БД таблице "users"
    protected $db;
    protected $tableName = "users";

    // свойства объекта
    public    $userId;
    protected $params   = array();
    protected $data     = array();
    protected $jwt;
    protected $stmtLog;

    protected $access = false;
    protected $role   = 0;

    public function __construct($params = array(), $db) {
        $this->db = $db;
        $this->params = $params;
        $this->db->connect();
        $this->jwt = new JwtAuthController();
    }

//    protected function getFields(){
//        $fields = array(
//            'login'    => array('type' => 'varchar'),
//            'username' => array('type' => 'varchar'),
//            'password' => array('type' => 'varchar'),
//            'email'    => array('type' => 'varchar'),
//        );
//        return $fields;
//    }

    public function getUser($userId = '') {
        if(!$userId)
            $userId = $this->params[0];
        $query = "SELECT * FROM users WHERE user_id = {$userId}";
        return $this->db->fetch($query);
    }

    public function getUsers() {
        $query = "SELECT * FROM users";
        return $this->db->select($query);
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
            $message = $this->stmtLog->errMessage;
            http_response_code(400);
            return array("message" => "Невозможно создать пользователя ({$message})");
        }
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
            $message = $this->stmtLog->errMessage;
            http_response_code(400);
            return array("message" => "Не удалось сохранить данные ({$message})");
        }
        $user = $this->getUser($this->userId);
        return array("message" => "Данные успешно сохранены", "user" => $user);
    }

    protected function postData($cast = 'array') {
        $data = json_decode(file_get_contents("php://input"));
        switch ($cast) {
            case 'array' : $data = (array)$data; break;
        }
        $this->data = $data;
    }

    public function deleteUser() {
        $this->userId = $this->params[0];
        $result = $this->delete($this->userId);
        return $result;
    }

    // Создание нового пользователя
    protected function create() {

        $data   = $this->data;
        $fields = $this->fieldList('create_user');
        $builder  = $this->db->insertBuilderPrepare($fields, $data);

        $query  = $builder['query'];
        $values = $builder['values'];
        $query  = "INSERT INTO {$this->tableName} {$query}";
        $values['password'] = password_hash($values['password'], PASSWORD_BCRYPT);
        return $this->execute($query, $values);
    }

    // Обновить запись пользователя
    public function update($userId = ''){
        if(!$userId)
             $userId = $this->userId;
        $data   = $this->data;
        $fields = $this->fieldList('update_user');
        if(isset($data['password']))
            unset($data['password']);
        $builder  = $this->db->updateBuilderPrepare($fields, $data);

        $query  = $builder['query'];
        $values = $builder['values'];
        $values['user_id']  = $userId;
        $query  = "UPDATE {$this->tableName} SET {$query} WHERE user_id = :user_id";
        return $this->execute($query, $values);
    }

    protected function delete($userId) {
        $fieldName = "user_id";
        $alias = ":{$fieldName}";
        $query = "DELETE FROM {$this->tableName} WHERE {$fieldName} ={$alias}";
        $data = array("{$alias}" => $userId);
        $resp = $this->db->prepare($query, $data);
        $this->stmtLog = $this->db->stmtLog($resp);
        if(empty($resp['status']))
            return false;
        return true;
    }

    protected function execute($query, $values) {
        $resp = $this->db->bindExecute($query, $values);
        $this->stmtLog = $this->db->stmtLog($resp);
        if(empty($resp['status']))
            return false;
        return true;
    }


    protected function authInit($data) {
        $token = $this->jwt->encode( $data['login'],
            $data['password'],
            $data['role']);
        return $token;
    }

    protected function authClose() {
        $this->access = false;
        $this->role = 0;
    }

    protected function logout() {
        $this->authClose();
    }

    protected function jwtTokenCheck($token = '') {
        ($token) ? $jwtToken = $token :
            $jwtToken = end($this->params);
        $access = $this->jwt->decode($jwtToken);
        ($access) ? $status = true : $status = false;
        $this->access = $status;
        return $status;
    }

    public function access() {
        $accessStatus = $this->jwtTokenCheck();
        return array('access_status' => $accessStatus);
    }

    public function login() {

        $postData = $this->postData();
        $login    = $postData['login'];
        $password = $this->hash($postData['password']);
        $query = "SELECT * FROM users WHERE login='$login' AND password='$password' ";
        $result = $this->db->select($query, 0);

        $authStatus = false;
        $sessionId = $jwt = $username = '';
        if(!empty($result['user_id'])) {
            $sessionId = session_id();
            $username  = $result['username'];
            $account = array(
                'session_id' => $sessionId,
                'role'       => $result['role'],
                'user_id'    => $result['user_id'],
                'login'      => $result['login'],
                'password'   => $result['password'],
                'username'   => $username,
                'verify'     => $result['verify'],
                'status'     => $result['status'],
                'user'       => $result,
                'auth_dt'    => date('Y-m-d')
            );

            $jwt = $this->authInit($account);
            $authStatus = true;
        } else {
            $this->authClose();
        }

        return array('auth_status' => $authStatus,
            'session_id'  => $sessionId,
            'username'    => $username,
            'jwt'         => $jwt );
    }


    protected function fieldList($optional = '') {

        $fields = array(
            'user_id'    => array( 'type' => 'serial',     'size' => 0,  'param' => 'PRIMARY KEY',   'optional' => 1),
            'created_dt' => array( 'type' => 'TIMESTAMP',  'size' => 0,  'param' => 'DEFAULT NOW()', 'optional' => 1),

            'login'      => array( 'type' => 'varchar',    'size' =>250, 'param' => 'UNIQUE NOT NULL', 'optional' => 2),
            'email'      => array( 'type' => 'varchar',    'size' =>300, 'param' => 'UNIQUE NOT NULL', 'optional' => 2),
            'username'   => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL',    'optional' => 2),
            'lastname'   => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL', 'optional' => 2),
            'password'   => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL', 'optional' => 2),
            'note'       => array( 'type' => 'text',       'size' => 0,  'param' => 'DEFAULT NULL', 'optional' => 2),
            'phone'      => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL', 'optional' => 2),
            'mobile'     => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL', 'optional' => 2),
            'sex'         => array( 'type' => 'integer',   'size' => 0,  'param' => 'DEFAULT 1',    'optional' => 2),
            'age'         => array( 'type' => 'integer',   'size' => 0,  'param' => 'DEFAULT NULL', 'optional' => 2),
            'address'     => array( 'type' => 'text',      'size' => 0, 'param' => 'DEFAULT NULL',  'optional' => 2),

            'role'       => array( 'type' => 'varchar',     'size' => 0,  'param' => 'DEFAULT 2',     'optional' => 3),
            'verify'     => array( 'type' => 'varchar',     'size' => 0,  'param' => 'DEFAULT 0',     'optional' => 3),
            'update_dt'  => array( 'type' => 'TIMESTAMP',   'size' => 0,  'param' => 'DEFAULT NOW()', 'optional' => 3),

            'last_login' => array( 'type' => 'TIMESTAMP',   'size' => 0,  'param' => 'NULL',          'optional' => 4),
            'active'      => array( 'type' => 'varchar',    'size' =>250, 'param' => 'DEFAULT NULL',  'optional' => 4),
            'status'      => array( 'type' => 'varchar',    'size' => 0,  'param' => 'DEFAULT 0'   ,  'optional' => 4),
            'last_active' => array( 'type' => 'TIMESTAMP',  'size' => 0,  'param' => 'DEFAULT NULL',  'optional' => 4),
        );

        $result = array();

        switch($optional) {
            case 'create_user' :
            case 'update_user' :
                 foreach ($fields as $fieldName => $values) {
                     if($values['optional'] == 2)
                         $result[$fieldName] = $values;
                 }
                 break;

            default : $result = $fields; break;
        }
        return $result;
    }

    public function createTable() {

        $this->db->deleteTable($this->tableName);

        $fields = $this->fieldList();
        $_fields = array();
        foreach ($fields as $fieldName => $values) {
            $type = $values['type'];
            $size = $values['size'];
            $param = $values['param'];
            switch($type) {
                case 'varchar' :
                    if(!$size)  $size = 250;
                    $_fields[] = " {$fieldName} {$type}({$size}) {$param} ";
                    break;

                default :
                    $_fields[] = " {$fieldName}  {$type}  {$param} ";
                    break;
            }
        }

        $queryFields = implode(',', $_fields);
        $result = $this->db->createTable($this->tableName, $queryFields);
        return array('status' => $result);
    }

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

}