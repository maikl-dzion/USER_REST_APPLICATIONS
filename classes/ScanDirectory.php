<?php

// declare(strict_types = 1);

// Пример запроса
// http://bolderfest.ru/USER_REST_APPLICATIONS/api.php/ScanDirectory/run/home/b/bolderp5/shop1.bolderp5.bget.ru/public_html/CONTROL_DB_INTERFACE

class ScanDirectory  {

    protected $params = array();
    protected $placeholders = array();

    public function __construct($params = array()){
        $this->params = $params;
    }

    public function run($dirname = ''){
        $result = $this->make($dirname);
        return $result;
    }

    public function getRootDir(){
        $result = $_SERVER['DOCUMENT_ROOT'];
        return $result;
    }

    protected function setPath($path = '') {
        if(!$path)
            $path = $_SERVER['DOCUMENT_ROOT'];

        if(!empty($this->params))
            $path = '/' . implode("/", $this->params);
        return $path;
    }

    protected function make($path = '') {
        $result = array();
        $path = $this->setPath($path);
        if(is_dir($path)) {
            $result = $this->scan($path);
        } else {
            if (file_exists($path)) {
                $result= $this->filePrint($path);
                // $result = file($path);
            }
        }
        // print_r($result); die;
        return $result;
    }

    protected function filePrint($path) {
        // $result = str_replace("\n","<br />\n", htmlspecialchars(implode("\n", file($path) )) );
        $r = file($path);
        $result = array();
        $strLine = '';
        foreach($r as $key => $line) {
            $text = $this->preLine($line);
            $result[] = $text;
            $strLine .= $text;
        }
        return $result;
    }

    protected function scan($path = '') {

        $result = array();
        $files = scandir($path);

        foreach($files as $key => $file) {

            if($file == '.' || $file == '..') continue;

            $filePath   = $path .'/'. $file;
            $type       = @filetype($filePath);
            $updateTime = date('d.m.Y H:i:s', @filemtime($filePath));
            $access     = date('d.m.Y H:i:s', @fileatime($filePath));

            $info = array(
                'name'     => $file,
                'filepath' => $filePath,
                'type'     => $type,
                'size'     => @filesize($filePath),

                'update_time' => $updateTime,
                'access'      => $access,
                'create_time' => date('d.m.Y H:i:s', @filectime($filePath)),

                'is_read'  => @is_readable($filePath),
                'is_write' => @is_writable($filePath),
                'is_exe'   => @is_executable($filePath),

                'pathinfo' => @pathinfo($filePath),
            );

            $result[] = $info;
        }

        return $result;
    }

    protected function preLine($line) {
        $text = $this->charReplace($line);
        $text = $this->textReplace($text);
        $text = "<div>" . $text . "</div>";
        return $text;
    }

    protected function charReplace($line) {
        $res = array();
        $arr = str_split($line);
        foreach($arr as $k => $char) {
            switch($char) {
                case ' ' :
                    $char = '&nbsp;';
                    break;

                case "\n" :
                    $char = '<br>';
                    break;

                case "\t" :
                    $char = '&nbsp;&nbsp;&nbsp;&nbsp;';
                    break;

                case "<" :
                    $char = '&lt;';
                    break;

                case ">" :
                    $char = '&gt;';
                    break;
            }
            $res[] = $char;
        }

        $text = implode("", $res);
        return $text;
    }

    protected function textReplace($line) {

        $replace = $text = array();
        $className = 'wrap-lang-control-operators';
        $placeholder = array(
            // array('class' , 'cornflowerblue'),
            array("="     , 'cornflowerblue'),

            array('$'     , 'red'),
            array('function', 'green'),
            array('if'      , 'blue'),
            array('else'    , 'blue'),
            array('{'       , 'darkgoldenrod'),
            array('}'       , 'darkgoldenrod'),
            array('('       , 'darkturquoise'),
            array(')'       , 'darkturquoise'),
            array('return'  , 'magenta' ),
            array('define'  , 'cornflowerblue'),
            array('true'    , 'cornflowerblue'),
            array('false'   , 'cornflowerblue'),
            array('array'   , 'cornflowerblue'),
            array('public'  , 'cornflowerblue'),
            array('private' , 'cornflowerblue'),
            array('protected', 'cornflowerblue'),
        );

        foreach ($placeholder as $key => $arr) {
            $replace[] = $arr[0];
            $text[]    = '<span class="' .$className. '" style="color:'.$arr[1].';" >' .$arr[0]. '</span>';
        }

        $result = str_replace($replace, $text, $line);
        // $result = str_replace('\=', $text, $result);

        return $result;
    }

//    protected function textReplace2($line) {
//        // ЧТО будем заменять
//        $replace = array(
//             0 => 'function'
//            ,1 => 'if'
//            ,2 => '{'
//            ,3 => '}'
//            ,4 => '('
//            ,5 => ')'
//            ,6 => 'return'
//            ,7 => '\='
//        );
//
//        // НА ЧТО будем заменять
//        $text = array(
//             0 => '<span class="control-operators" style="color:green;" >function</span>'
//            ,1 => '<span class="control-operators" style="color:blue;"  >if</span>'
//            ,2 => '<span class="control-operators" style="color:darkgoldenrod;"  >{</span>'
//            ,3 => '<span class="control-operators" style="color:darkgoldenrod;"  >}</span>'
//            ,4 => '<span class="control-operators" style="color:darkturquoise;"  >(</span>'
//            ,5 => '<span class="control-operators" style="color:darkturquoise;"  >)</span>'
//            ,6 => '<span class="control-operators" style="color:magenta;"  >return</span>'
//            ,7 => '<span class="control-operators" style="color:cornflowerblue;"  >fx</span>'
//        );
//
//        $result = str_replace($replace, $text, $line);
//
//        return $result;
//    }
//
//    protected function textReplace3($line) {
//
//        $this->setText('function', 'green');
//        $this->setText('if'      , 'blue');
//        $this->setText('{'       , 'darkgoldenrod');
//        $this->setText('}'       , 'darkgoldenrod');
//        $this->setText('('       , 'darkturquoise');
//        $this->setText(')'       , 'darkturquoise');
//        $this->setText('return'  , 'magenta');
//        $this->setText('\='      , 'cornflowerblue');
//
//        $replace = $this->placeholders['replace'];
//        $span    = $this->placeholders['text'];
//        $text = str_replace($replace, $span, $line);
//
//        return $text;
//    }
//
//    protected function setText($value, $color, $className = 'control-operators') {
//        $span =  '<span class="' .$className. '" style="color:'.$color.';" >' .$value. '</span>';
//        $this->placeholders['replace'][] = $value;
//        $this->placeholders['text'][]  = $span;
//    }

//    public function fileReader($path) {
//
//        $filename = "DownloadedFile.php";   // имя файл предложенное для сохранения в окне браузера
//        $myFile   = $path; // файл на серевере
//
//        $mm_type="application/octet-stream";
//
//        header("Cache-Control: public, must-revalidate"); // кешировать
//        header("Pragma: hack");
//        header("Content-Type: " . $mm_type);
//        header("Content-Length: " .(string)(filesize($myFile)) );
//        header('Content-Disposition: attachment; filename="'.$filename.'"');
//        header("Content-Transfer-Encoding: binary");
//
//        return readfile($myFile); // прочитать файл и отправить в поток
//    }
}