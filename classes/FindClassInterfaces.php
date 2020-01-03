<?php

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