<?php

class FtpController
{

    private $connect;
    private $status;
    protected $remoteDir;
    protected $localDir;
    public $remoteRootDir;
    private $config = array();
    public $logs = array();
    public $level = 0;

    public function __construct($config, $remoteDir, $localDir = __DIR__)
    {
        $this->config = $config;
        $this->remoteDir = $remoteDir;
        $this->localDir = $localDir;
        $this->auth();
        $this->remoteRootDir = $this->getDirName();
        // lg($this);
    }

    // ################################
    // ######  PUBLIC  METHODS    #####
    // -- Публичные методы (интерфейсы)

    // Загрузить файлы из текущей директории на удаленный сервер
    public function moveFilesToRemote()
    {
        $this->level = 0;
        $remoteDir = $this->remoteDir;
        $localDir = $this->localDir;
        $this->scan($remoteDir, $localDir);
        //$this->close();
    }


    public function getRemoteDirFiles($dirName = '', $recursive = true)
    {
        // lg($dirName);
        $dirName = (!$dirName) ? $this->remoteDir : $dirName;
        $result = $this->remoteDirScan($dirName, $recursive);
        //$this->close();
        return $result;
    }

    public function getLocalDirFiles($dirName = '', $recursive = true)
    {
        $dirName = (!$dirName) ? $this->localDir : $dirName;
        $result = $this->localDirScan($dirName, $recursive);
        return $result;
    }

    // ##########################
    // ### PROTECTED METHODS ####
    // --  Защищенные методы ----

    protected function auth()
    {
        $config = $this->config;

        $host = $config['host'];
        $user = $config['user'];
        $password = $config['password'];

        $connect = ftp_connect($host);
        $status = ftp_login($connect, $user, $password);

        $this->connect = $connect;
        $this->status = $status;

        ftp_pasv($this->connect, true);
    }


    protected function remoteDirScan($dirName, $recursive = true)
    {
        $connect = $this->connect;
        $result = ftp_rawlist($connect, $dirName, $recursive);
        // lg($result);
        $result = $this->listRender($result);
        return $result;
    }

    protected function listRender($list)
    {
        $result = array();
        $dirName = '';

        foreach ($list as $key => $value) {
            $item = explode(" ", $value);
            $count = count($item);
            if ($count == 1) {
                if (empty($item[0])) {
                    $dirName = '';
                    continue;
                }
                $dirName = str_replace(':', '', $item[0]);
                // $dirName = $item[0];
            } else {
                $info = $vinfo = array();
                foreach ($item as $i => $v) {
                    if ($v) {
                        $vinfo[] = $v;
                    }
                }

                $info['dirname'] = $dirName;
                $info['rwx'] = $this->_is(0, $vinfo);
                $info['size1'] = $this->_is(2, $vinfo);
                $info['num'] = $this->_is(3, $vinfo);
                $info['size2'] = $this->_is(4, $vinfo);

                $info['month'] = $this->_is(5, $vinfo);
                $info['day'] = $this->_is(6, $vinfo);
                $info['time'] = $this->_is(7, $vinfo);
                $info['name'] = end($vinfo);

                // if($dirName == 'tmp/load/error_log:') lg($item, $list);
                $result[] = $info;
            }

        }

        return $result;
    }

    protected function _is($arr, $index)
    {
        $result = '';
        if (!empty($arr[$index])) {
            $result = $arr[$index];
        }
        return $result;
    }

    protected function localDirScan($dirName = __DIR__, $recursive = true)
    {
        $connect = $this->connect;
        $files = scandir($dirName);
        $result = $files;
        return $result;
    }

    protected function scan($remoteDir, $localDir)
    {
        $this->level++;
        $connect = $this->connect;
        $funcName = __FUNCTION__;
        $files = scandir($localDir);
        // lg($funcName);
        foreach ($files as $key => $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $remoteFile = $remoteDir . '/' . $file;
            $localFile = $localDir . '/' . $file;

            // if($this->level == 2) lg($remoteFile, $localFile);

            if (is_dir($localFile)) {
                ftp_mkdir($connect, $remoteFile);
                $this->$funcName($remoteFile, $localFile);
            } else {
                $this->sendToRemote($remoteFile, $localFile);
            }
        }
    }

    protected function sendToRemote($remoteFile, $localFile)
    {
        $connect = $this->connect;
        $status = ftp_put($connect, $remoteFile, $localFile, FTP_ASCII);

        if ($status) {
            $message = "$localFile($remoteFile) успешно загружен на сервер\n";
        } else {
            $message = "Не удалось загрузить $localFile($remoteFile)  на сервер\n";
        }

        $this->logs[] = $message;
    }

    protected function close()
    {
        ftp_close($this->connect);
    }

    protected function getDirName()
    {
        return ftp_pwd($this->connect);
    }
}


//function lg()
//{
//    $debugTrace = debug_backtrace();
//    $args = func_get_args();
//    $get = false;
//    $output = $traceStr = '';
//    $style = 'margin:10px; padding:10px; border:3px red solid;';
//
//    foreach ($args as $key => $value) {
//        $itemArr = array();
//        $itemStr = '';
//        is_array($value) ? $itemArr = $value : $itemStr = $value;
//        if ($itemStr == 'get') {
//            $get = true;
//        }
//        $line = print_r($value, true);
//        $output .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
//    }
//
//    foreach ($debugTrace as $key => $value) {
//        // if($key == 'args') continue;
//        $itemArr = array();
//        $itemStr = '';
//        is_array($value) ? $itemArr = $value : $itemStr = $value;
//        if ($itemStr == 'get') {
//            $get = true;
//        }
//        $line = print_r($value, true);
//        $output .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
//    }
//
//    if ($get) {
//        return $output;
//    }
//    print $output;
//    //print '<pre>' . print_r($debug) . '</pre>';
//    die;
//}

?>