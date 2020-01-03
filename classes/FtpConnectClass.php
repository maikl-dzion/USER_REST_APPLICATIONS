<?php

class FtpConnectClass {
    private $connect;
    private $messages;
    public function __construct($host, $user, $pwd)
    {
        $this->init($host, $user, $pwd);
    }

    public function init($host, $user, $pwd, $type = true)
    {
        $this->connect = ftp_connect($host);
        if($this->connect) {
            $ftpAuth = ftp_login($this->connect, $user, $pwd);
            if(!$ftpAuth)
                die('Неправильный логин - "' . $user . '" или пароль - "' . $pwd . '"');
        } else {
            die('Нет соединения с сервером - "' . $host . '"');
        }

        ftp_pasv($this->connect, $type);
        return true;
    }

    public function dirList($dirName = "") {
        $result = ftp_nlist($this->connect, $dirName);
        return $result;
    }
    
    public function log($message = false)
    {
        if ($message == false) return $this->messages;
        $this->messages[] = $message;
    }
}