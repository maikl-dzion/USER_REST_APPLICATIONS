<?php

class UserController extends BaseController {

    protected $username;
    protected $email;
    protected $isLogged = false;
    protected $msg    = array();

    protected $db;
    protected $params = array();
    protected $error  = array();
    protected $sessionAuthName = 'userSession';
    protected $jwt;
    protected $access = false;
    protected $role = 0;

    public function __construct($params = array(), $db) {

        $this->db = $db;
        $this->params = $params;
        $this->db->connect();
        $this->jwt = new JwtAuthController();

    }

    // Create users table
    public function createUserTable() {

         $varchar = 'varchar';
         $integer = 'integer';

         $userFields = array(
            'user_id'    => array( 'type' => 'serial',      'size' =>'',    'param' => 'PRIMARY KEY'),
            'login'      => array( 'type' => $varchar,      'size' =>'250', 'param' => 'UNIQUE NOT NULL'),
            'username'   => array( 'type' => $varchar,      'size' =>'250', 'param' => 'UNIQUE NOT NULL'),
            'password'   => array( 'type' => $varchar,      'size' =>'250', 'param' => 'UNIQUE NOT NULL'),
            'email'      => array( 'type' => $varchar,      'size' =>'300', 'param' => 'UNIQUE NOT NULL'),
            'verify'     => array( 'type' => $integer,      'size' =>'',    'param' => 'DEFAULT 0'),
            'created_dt' => array( 'type' => 'TIMESTAMP', 'size' =>'',    'param' => 'NULL'),
            'update_dt'  => array( 'type' => 'TIMESTAMP', 'size' =>'',    'param' => 'NULL'),
            'last_login' => array( 'type' => 'TIMESTAMP', 'size' =>'',    'param' => 'NULL'),
            'note'       => array( 'type' => 'TEXT',      'size' =>'',    'param' => 'DEFAULT NULL'),
            'role'       => array( 'type' => $integer,    'size' =>'',    'param' => 'DEFAULT 2'),
            'active'     => array( 'type' => $varchar,    'size' =>'250', 'param' => 'DEFAULT NULL'),
            'status'     => array( 'type' => $integer,    'size' =>'',    'param' => 'DEFAULT 0'),
            'phone'      => array( 'type' => $varchar,    'size' =>'250', 'param' => 'DEFAULT NULL'),
            'mobile'     => array( 'type' => $varchar,    'size' =>'250', 'param' => 'DEFAULT NULL'),
            'sex'        => array( 'type' => $integer,    'size' =>'',    'param' => 'DEFAULT 1'),
            'age'        => array( 'type' => $varchar,    'size' =>'250', 'param' => 'DEFAULT NULL'),
            'address'     => array( 'type' => 'TEXT',     'size' =>'',    'param' => 'DEFAULT NULL'),
            'last_active' => array( 'type' => 'TIMESTAMP','size' =>'',    'param' => 'DEFAULT NULL'),
         );

         $query = "CREATE TABLE IF NOT EXISTS users (

                        user_id  serial PRIMARY KEY,
                        login       VARCHAR (250) UNIQUE NOT NULL,
                        username    VARCHAR (250) UNIQUE NOT NULL,
                        password    VARCHAR (250) NOT NULL,
                        email       VARCHAR (300) UNIQUE NOT NULL,
                        verify      integer   DEFAULT 0,
                        created_dt  TIMESTAMP NULL,
                        update_dt   TIMESTAMP NULL,
                        last_login  TIMESTAMP NULL,
                        note        TEXT  DEFAULT NULL, 
                        role        integer   DEFAULT 2,
                        active      VARCHAR (250) DEFAULT NULL, 
                        status      integer DEFAULT 0,
                        phone       VARCHAR (250) DEFAULT NULL,
                        mobile      VARCHAR (250) DEFAULT NULL,
                        sex         integer DEFAULT 1,
                        age         VARCHAR (250) DEFAULT NULL,
                        address     TEXT  DEFAULT NULL,
                        last_active TIMESTAMP NULL
     
                   );";
         $result = $this->db->exec($query);
         return array('status' =>$result);
    }

    protected function hash($value) {
        $result = md5($value);
        return $result;
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

    // Register a new user
    public function register() {

         $postData = $this->postData();

         $login    = $postData['login'];
         $userName = $postData['username'];
         $password = $this->hash($postData['password']);
         $email    = $postData['email'];
         $role     = $postData['role'];

         $query  = "INSERT INTO users (
                         login, 
                         password, 
                         username,
                         email,
                         role
                    ) 
                    VALUES (
                        '{$login}',
                        '{$password}',
                        '{$userName}',
                        '{$email}',
                        {$role}
                    )";
         // lg($query);

         $result = $this->db->exec($query);
         return array('status' => $result);
    }









    // Logout function
    //    public function logout2() {
    //        session_unset();
    //        session_destroy();
    //        $this->is_logged = false;
    //        setcookie('username', '', time()-3600);
    //        header('Location: index.php');
    //        exit();
    //    }


    public function get_username() {
        return $this->username;
    }

    public function get_email() {
        return $this->email;
    }

    public function is_logged() {
        return $this->is_logged;
    }

    public function get_info() {
        return $this->msg;
    }

    public function get_error() {
        return $this->error;
    }

    private function update_messages() {
        if (isset($_SESSION['msg']) && $_SESSION['msg'] != '') {
            $this->msg = array_merge($this->msg, $_SESSION['msg']);
            $_SESSION['msg'] = '';
        }
        if (isset($_SESSION['error']) && $_SESSION['error'] != '') {
            $this->error = array_merge($this->error, $_SESSION['error']);
            $_SESSION['error'] = '';
        }
    }

    // Login
    public function login2() {
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $this->username = $this->db->real_escape_string($_POST['username']);
            $this->password = sha1($this->db->real_escape_string($_POST['password']));
            if ($row = $this->verify_password()) {
                session_regenerate_id(true);
                $_SESSION['id'] = session_id();
                $_SESSION['username'] = $this->username;
                $_SESSION['email'] = $row->email;
                $_SESSION['is_logged'] = true;
                $this->is_logged = true;
                // Set a cookie that expires in one week
                if (isset($_POST['remember']))
                    setcookie('username', $this->username, time() + 604800);
                // To avoid resending the form on refreshing
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            } else $this->error[] = 'Wrong user or password.';
        } elseif (empty($_POST['username'])) {
            $this->error[] = 'Username field was empty.';
        } elseif (empty($_POST['password'])) {
            $this->error[] = 'Password field was empty.';
        }
    }

    // Check if username and password match
    private function verify_password() {
        $query  = 'SELECT * FROM users '
            . 'WHERE user = "' . $this->username . '" '
            . 'AND password = "' . $this->password . '"';
        return ($this->db->query($query)->fetch_object());
    }

    // Register a new user
    public function register_old() {
        if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['confirm'])) {
            if ($_POST['password'] == $_POST['confirm']) {
                $first_user = $this->empty_db();
                $username = $this->db->real_escape_string($_POST['username']);
                $password = sha1($this->db->real_escape_string($_POST['password']));
                $email = $this->db->real_escape_string($_POST['email']);
                $query  = 'INSERT INTO users (user, password, email) '
                    . 'VALUES ("' . $username . '", "' . $password . '", "' . $email . '")';
                if ($this->db->query($query)) {
                    if ($first_user) {
                        session_regenerate_id(true);
                        $_SESSION['id'] = session_id();
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['is_logged'] = true;
                        $this->is_logged = true;
                    } else {
                        $this->msg[] = 'User created.';
                        $_SESSION['msg'] = $this->msg;
                    }
                    // To avoid resending the form on refreshing
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                    exit();
                } else $this->error[] = 'Username already exists.';
            } else $this->error[] = 'Passwords don\'t match.';
        } elseif (empty($_POST['username'])) {
            $this->error[] = 'Username field was empty.';
        } elseif (empty($_POST['password'])) {
            $this->error[] = 'Password field was empty.';
        } elseif (empty($_POST['confirm'])) {
            $this->error[] = 'You need to confirm the password.';
        }
    }
    // Update an existing user's password
    public function update($username) {
        if (!empty($_POST['email']) && $_POST['email'] !== $_POST['old_email']) {
            $this->email = $this->db->real_escape_string($_POST['email']);
            $query  = 'UPDATE users '
                . 'SET email = "' . $this->email . '" '
                . 'WHERE user = "' . $username . '"';
            if ($this->db->query($query)) $this->msg[] = 'Your email has been changed successfully.';
            else $this->error[] = 'Something went wrong. Please, try again later.';
        } elseif (!empty($_POST['email'])) $this->error[] = 'You must enter an email adress.';
        if (!empty($_POST['password']) && !empty($_POST['newpassword1']) && !empty($_POST['newpassword2'])) {
            if ($_POST['newpassword1'] == $_POST['newpassword2']) {
                $this->password = sha1($this->db->real_escape_string($_POST['password']));
                if ($this->verify_password()) {
                    $this->password = sha1($this->db->real_escape_string($_POST['newpassword1']));
                    $query  = 'UPDATE users '
                        . 'SET password = "' . $this->password . '" '
                        . 'WHERE user = "' . $username . '"';
                    if ($this->db->query($query)) $this->msg[] = 'Your password has been changed successfully.';
                    else $this->error[] = 'Something went wrong. Please, try again later.';
                } else $this->error[] = 'Wrong password.';
            } else $this->error[] = 'Passwords don\'t match.';
        } elseif (empty($_POST['password']) && (!empty($_POST['newpassword1']) || !empty($_POST['newpassword2']))) {
            $this->error[] = 'Old password field was empty.';
        } elseif (!empty($_POST['password']) && empty($_POST['newpassword1'])) {
            $this->error[] = 'New password field was empty.';
        } elseif (!empty($_POST['password']) && empty($_POST['newpassword2'])) {
            $this->error[] = 'You must enter the new password again.';
        }
        // To avoid resending the form on refreshing
        $_SESSION['msg'] = $this->msg;
        $_SESSION['error'] = $this->error;
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
    // Delete an existing user
    public function delete($user) {
        $query = 'DELETE FROM users WHERE user = "' . $user . '"';
        return ($this->db->query($query));
    }
    // Get info about an user
    public function get_user_info($user) {
        $query = 'SELECT user, password, email FROM users WHERE user = "' . $user . '"';
        $result = $this->db->query($query);
        return $result->fetch_object();
    }
    // Get all the existing users
    public function get_users() {
        $query = 'SELECT user, password, email FROM users';
        return ($this->db->query($query));
    }
    // Print info messages in screen
    public function display_info() {
        foreach ($this->msg as $msg) {
            echo '<p class="msg">' . $msg . '</p>';
        }
    }
    // Print errors in screen
    public function display_errors() {
        foreach ($this->error as $error) {
            echo '<p class="error">' . $error . '</p>';
        }
    }
    // Check if the users db has been created
    public function db_exists() {
        return ($this->db->query('SELECT 1 FROM users'));
    }
    // Check if the users db has any users
    public function empty_db() {
        $query = 'SELECT * FROM users';
        $result = $this->db->query($query);
        return ($result->num_rows === 0);
    }
    // Create a new db to start with
    private function create_db() {
        $query 	= 'CREATE TABLE users ('
            . 'user VARCHAR(75) NOT NULL, '
            . 'password VARCHAR(75) NOT NULL, '
            . 'email VARCHAR(150) NULL, '
            . 'PRIMARY KEY (user) '
            . ') ENGINE=MyISAM COLLATE=utf8_general_ci';

//        CREATE TABLE IF NOT EXISTS users (
//            userid varchar(255) NOT NULL default '',
//            username varchar(255) default NULL,
//            password varchar(255) default NULL,
//            permission int(2) default NULL,
//            PRIMARY KEY  (userid)
//        )  TYPE=MyISAM;

        return ($this->db->query($query));
    }
    // Drop an existing db
    private function drop_db() {
        // CREATE DATABASE IF NOT EXISTS authclassed;
        $query 	= 'DROP TABLE IF EXISTS users ';
    }
}