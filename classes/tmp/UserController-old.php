<?php

class UserController extends BaseController {

    protected $db;
    protected $username;
    protected $email;
    protected $isLogged = false;
    protected $params = array();
    protected $msg    = array();
    protected $error  = array();

    public function __construct($params = array(), $db) {

        // session_start();
        $this->db = $db;
        $this->params = $params;
        $this->db->connect();

        // print_r($this); die;

//        $this->update_messages();
//
//        if (isset($_GET['logout'])) {
//            $this->logout();
//        } elseif (isset($_COOKIE['username']) ||
//                  (!empty($_SESSION['username']) &&
//                  $_SESSION['is_logged']))  {
//
//            $this->is_logged = true;
//            $this->username = $_SESSION['username'];
//            $this->email = $_SESSION['email'];
//
//            if (isset($_POST['update'])) {
//                $this->update($this->username);
//            } elseif (isset($_POST['register'])) {
//                $this->register();
//            }
//
//        } elseif (isset($_POST['login'])) {
//            $this->login();
//        } elseif ($this->empty_db() &&
//                  isset($_POST['register'])) {
//            $this->register();
//        } else if (!$this->db_exists()) {
//            $this->create_db();
//        }
//
//        return $this;
    }

    public function createUserTable() {
         $query = "CREATE TABLE IF NOT EXISTS users (

                        user_id  serial PRIMARY KEY,
                        login    VARCHAR (250) UNIQUE NOT NULL,
                        username VARCHAR (250) UNIQUE NOT NULL,
                        password VARCHAR (250) NOT NULL,
                        email    VARCHAR (300) UNIQUE NOT NULL,
                        verify   integer   DEFAULT 0,
                        created_dt TIMESTAMP NULL,
                        update_dt  TIMESTAMP NULL,
                        last_login TIMESTAMP NULL,
                        note       TEXT  DEFAULT NULL, 
                        role       integer   DEFAULT 2,
                        active     VARCHAR (250) DEFAULT NULL, 
                        status     integer DEFAULT 0,
                        phone      VARCHAR (250) DEFAULT NULL,
                        mobile     VARCHAR (250) DEFAULT NULL,
                        sex        integer DEFAULT 1,
                        age        VARCHAR (250) DEFAULT NULL,
                        address     TEXT  DEFAULT NULL,
                        last_active TIMESTAMP NULL
     
                   );";
         $result = $this->db->exec($query);
         return array('status' =>$result);
    }


    // Register a new user
    public function register() {

         $postData = $this->postData();

         $login    = $postData['login'];
         $userName = $postData['username'];
         $password = md5($postData['password']);
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

         // lg($postData);


//        if (!empty($_POST['username']) && !empty($_POST['password']) && !empty($_POST['confirm'])) {
//            if ($_POST['password'] == $_POST['confirm']) {
//                $first_user = $this->empty_db();
//                $username = $this->db->real_escape_string($_POST['username']);
//                $password = sha1($this->db->real_escape_string($_POST['password']));
//                $email = $this->db->real_escape_string($_POST['email']);
//                $query  = 'INSERT INTO users (user, password, email) '
//                    . 'VALUES ("' . $username . '", "' . $password . '", "' . $email . '")';
//                if ($this->db->query($query)) {
//                    if ($first_user) {
//                        session_regenerate_id(true);
//                        $_SESSION['id'] = session_id();
//                        $_SESSION['username'] = $username;
//                        $_SESSION['email'] = $email;
//                        $_SESSION['is_logged'] = true;
//                        $this->is_logged = true;
//                    } else {
//                        $this->msg[] = 'User created.';
//                        $_SESSION['msg'] = $this->msg;
//                    }
//                    // To avoid resending the form on refreshing
//                    header('Location: ' . $_SERVER['REQUEST_URI']);
//                    exit();
//                } else $this->error[] = 'Username already exists.';
//            } else $this->error[] = 'Passwords don\'t match.';
//        } elseif (empty($_POST['username'])) {
//            $this->error[] = 'Username field was empty.';
//        } elseif (empty($_POST['password'])) {
//            $this->error[] = 'Password field was empty.';
//        } elseif (empty($_POST['confirm'])) {
//            $this->error[] = 'You need to confirm the password.';
//        }


    }







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
    public function login() {
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
    // Logout function
    public function logout() {
        session_unset();
        session_destroy();
        $this->is_logged = false;
        setcookie('username', '', time()-3600);
        header('Location: index.php');
        exit();
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