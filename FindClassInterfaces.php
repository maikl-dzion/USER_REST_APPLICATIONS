<?php

header('Access-Control-Allow-Credentials', true);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: X-Requested-With, X-HTTP-Method-Override, Origin, Content-Type, Cookie, Accept');

//res.header('Access-Control-Allow-Credentials', true);
//res.header('Access-Control-Allow-Origin', req.headers.origin);
//res.header('Access-Control-Allow-Methods', 'GET,PUT,POST,DELETE,UPDATE,OPTIONS');
//res.header('Access-Control-Allow-Headers', 'X-Requested-With, X-HTTP-Method-Override, Content-Type, Accept');

header('Content-Type: text/html; charset=utf-8');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// include_once ROOT_DIR . '/check_post_data.php';
// include_once ROOT_DIR . '/classAutoloader.php';

session_start();

// $dirname = __DIR__ . '/classes';
$dirname = $_SERVER['DOCUMENT_ROOT'] . '/USER_REST_APPLICATIONS';

// $response = scanClassInterface($dirname);
//$arguments = array($dirname, true);
//$render = new FindClassInterfaces($arguments);
//$response = $render->start();
//print_r($response);


$arguments = array('viewAction', $dirname);
$render = new FindFilesController($arguments);
$response = $render->start();
print_r($response);


class FindFilesController {

    public $dirname = __DIR__;
    public $findText;
    public $files     = array();
    public $dirs      = array();
    public $results   = array();
    protected $params = array();

    public function __construct($params = array()){
        $this->params = $params;
        if(!empty($params[0]))
            $this->findText = $params[0];

        if(!empty($params[1]))
            $this->dirname = $params[1];
    }

    public function start() {
        $dirname = $this->dirname;
        $this->run($dirname);
        return array(
            'results' => $this->results,
            'files'   => $this->files,
            'dirs'    => $this->dirs,
        );
    }

    protected function run($dirname) {

        if (!file_exists($dirname))
              return array();

        $result = $items = array();
        $files = scandir($dirname);
        $funcName = __FUNCTION__;

        foreach ($files as $key => $file) {
            if (!$file || $file == '.' || $file == '..') continue;

            $currentPath = $dirname . '/' . $file;
            if (is_dir($currentPath)) {
                $items = $this->$funcName($currentPath);
                $this->readDir($currentPath, $file);
            } else {
                $this->find($currentPath, $file);
            }
        }
    }

    protected function find($file, $fileName) {

        $result = array();
        $this->files[] = $file;
        $fileStrArr = file($file);
        $findText = $this->findText;

        foreach ($fileStrArr as $key => $line) {
            $text = $this->findText($line, $findText);
            if($text) {
                $result['file_path'] = $file;
                $result['file_name'] = $fileName;
                $result[]  = 'Line: ' . $key . '; Text:' . $line;
            }
        }

        if(!empty($result))
            $this->results[$fileName] = $result;
    }

    protected function findText($line, $findme) {
        $pos = strpos($line, $findme);
        if ($pos === false)
            return false;
        return $line;
    }

    protected function readDir($file, $fileName) {
        $this->dirs[$fileName] = $file;
    }

}



// This function scans the files folder recursively, and builds a large array

class FindClassInterfaces {

    public $dirname = __DIR__;
    public $items = array();
    public $listInterface = array();
    public $listFiles     = array();
    public $listDir       = array();
    protected $conformState = false; // параметр соотвествия имени файла = имя класса (Controller.php = class Controller )

    public function __construct($params = array()){

        if(!empty($params[0]))
            $this->dirname = $params[0];

        if(!empty($params[1]))
            $this->conformState = true;
    }

    public function start() {
        $dirname = $this->dirname;
        $this->find($dirname);
        return array(
            'interfaces' => $this->listInterface,
            'files' => $this->listFiles,
            'directories' => $this->listDir,
        );
    }

    protected function find($dirname) {

        if (!file_exists($dirname)) {
            return array();
        }

        $result = array();
        $files = scandir($dirname);
        $funcName = __FUNCTION__;

        foreach ($files as $key => $file) {
            if (!$file || $file == '.' || $file == '..') continue;

            if (is_dir($dirname . '/' . $file)) {
                $items = $this->$funcName($dirname . '/' . $file);
                $result[] = array(
                    "name" => $file,
                    "type" => "folder",
                    "path" => $dirname . '/' . $file,
                    "items" => $items
                );
                $this->readDir($dirname . '/' . $file, $file);
            } else {
                $result[] = array(
                    "name" => $file,
                    "type" => "file",
                    "path" => $dirname . '/' . $file,
                    "size" => filesize($dirname . '/' . $file)
                );

                $this->readFile($dirname . '/' . $file, $file);
            }
        }

        $this->items[] = $result;

        return $result;
    }

    protected function readFile($file, $fileName) {

        $f = explode('.', $fileName);
        $fName = $className = $f[0];

        $interface = array();
        $this->listFiles[] = $file;
        $fileArr = file($file);

        if($this->conformState) {
            $state = false;
            foreach ($fileArr as $key => $line) {

                $findme = 'class ' . $className;
                $text = $this->findText($line, $findme);
                if($text) $state = true;

                if(!$state)
                     continue;

                $findme = 'public function';
                $text = $this->findText($line, $findme);
                if($text) {
                    $prevLine = $fileArr[$key - 1];

                    $line = str_replace($findme, "", $line);
                    $line = str_replace("{", "", $line);

                    // $interface['comment'][] = $prevLine;
                    $interface['file_path'] = $file;
                    $interface['class_name'] = $className;
                    // $interface[]['comment'] = $prevLine;
                    // $interface[0] = '';
                    $interface[]  = $line;
                }
            }
        }

        if(!empty($interface))
          $this->listInterface[$fileName] = $interface;
        // print_r($this->lineInterface); die($fileName);
    }

    protected function findText($line, $findme) {
        $pos = strpos($line, $findme);
        if ($pos === false)
            return false;
        return $line;
    }

    protected function readDir($file, $fileName) {
        $this->listDir[$fileName] = $file;
    }

}




// Output the directory listing as JSON
//header('Content-type: application/json');
//
//echo json_encode(array(
//    "name" => "files",
//    "type" => "folder",
//    "path" => $dir,
//    "items" => $response
//));


?>