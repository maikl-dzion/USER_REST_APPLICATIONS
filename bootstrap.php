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

session_start();  // Запускаем сессию

spl_autoload_register('Autoloader::autoload'); // Запускаем класс автозагрузки


/**  Список классов и функций
    class RequestHandler     / Обработка HTTP-запросов
    class Autoloader         / Автозагрузка классов (из папки 'classes')
    function getPostData()   / Получение POST-данных
    function getResponse($result, $fieldName = '')  / Возвращаем результат
 *
 */

/** Примеры запросов
    http://bolderfest.ru/USER_APPLICATIONS_V1/api.php/FileGetContentCopy/scandir  /получить список файлов в директории
 */

class RequestHandler {

    public $method;
    public $queryString;
    public $pathInfo;
    public $controllerName;
    public $actionName;
    public $args     = array();
    public $params   = array();
    public $postData = array();
    public $server   = array();
    public $db;

    public function __construct($args = array(), DbInterface $db) {
        $this->args = $args;
        $this->db   = $db;
        $this->method = $this->serverParam('REQUEST_METHOD');
        $this->queryString = $this->serverParam('QUERY_STRING');
        $this->pathInfo    = $this->serverParam('PATH_INFO');
        $this->postData    = $this->getPostData();
        $this->server      = $_SERVER;
    }

    /*
     Основная функция для выполнения запроса
     */
    public function run() {

        $this->routerInit(); // Обрабатываем запрос,формируем route

        $ControllerClass = $this->controllerName;
        $Action     = $this->actionName;

        // lg($this->params);

        // Проверяем существование класса
        if(!class_exists($ControllerClass))
            $this->getResponse('Не найден класс : ' . $ControllerClass, 'error');
        $Controller = new $ControllerClass($this->params, $this->db); // Создаем объект класса

        // Проверяем существование метода класса
        if(!method_exists($Controller, $Action))
            $this->getResponse('Не найден метод класса : ' . $Action, 'error');
        $response = $Controller->$Action($this->params); // Вызываем метод класса
        // $response = $Controller->$Action(); // Вызываем метод класса

        return $response;
    }

    /*
     Функция формирования route
     В зависимости от pathInfo формируем нужный route
     */
    protected function routerInit(){
        if(!empty($this->pathInfo)) {
            $this->pathInfoRun();
        }
        else {
            $this->queryStringRun();
        }
    }

    /*
     Функция для обработки PATH_INFO
     Пример url: /ControllerName/ActionName/p1/p2/p3
     */
    protected function pathInfoRun($pathInfo = '') {
        $url = $this->pathInfo;
        $url = trim($url, '/');
        $route = explode('/', $url);

        foreach ($route as $key => $value) {
            switch ($key) {
                case 0  : $this->controllerName = $value; break;
                case 1  : $this->actionName     = $value; break;
                default : $this->params[]       = $value; break;
            }
        }
    }

    /*
     Функция для обработки QUERY_STRING
     Пример url: ?controller=ControllerName&action=ActionName&username=Maikl&email=dz@mail.ru
     */
    protected function queryStringRun($queryString = '') {
        //$this->controllerName = $this->getParam('controller');
        //$this->actionName     = $this->getParam('action');
        foreach ($_GET as $key => $value) {
            switch ($key) {
                case 'controller'  : $this->controllerName = $value; break;
                case 'action'      : $this->actionName     = $value; break;
                default : $this->params[$key]              = $value; break;
            }
        }
    }

    protected function getParam($fieldName) {
        $param = '';
        if(isset($_GET[$fieldName])) {
            $param = $_GET[$fieldName];
        }
        return $param;
    }

    protected function serverParam($fieldName) {
        $param = '';
        if(isset($_SERVER[$fieldName])) {
            $param = $_SERVER[$fieldName];
        }
        return $param;
    }

    /*@
     Функция для получение POST данных
     */
    protected function getPostData() {
        $result = (array)json_decode(file_get_contents("php://input"));
        if(empty($result))
            return array();
        return $result;
    }

    /*@
     Функция для получение POST данных
     */
    protected function postData() {
        $result = (array)json_decode(file_get_contents("php://input"));
        if(empty($result))
            return array();
        return $result;
    }

    /*
     Функция для возврата результата
     */
    public function getResponse($result, $fieldName = '') {
        $result = array($result);
        if($fieldName)
            $result = array($fieldName => $result);
        die(json_encode($result));
    }

}


class Autoloader {

    const debug      = 0;
    const RootDir    = __DIR__;
    const ClassesDir = 'classes';

    public function __construct(){}

    /**
     * Функция прямого подключения класса или файла.<br/>
     * В случае неудачи, вызывает функцию рекурсивного поиска.
     * @param string $file имя файла(без расширения)
     * @param string $ext расширение файла(без точки)
     * @param string $dir директория для поиска(без первого и последнего слешей)
     */
    public static function autoload($file, $ext = FALSE, $dir = FALSE) {
        $file       = str_replace('\\', '/', $file);
        $rootDir    = self::RootDir;
        $classesDir = self::ClassesDir;

        // die($classesDir);

        if($ext === FALSE) {
            $path = $rootDir . '/' . $classesDir;
            $filepath = $path . '/' . $file . '.php';
        }
        else{
            $path = $rootDir . (($dir) ? '/' . $dir : '');
            $filepath = $path . '/' . $file . '.' . $ext;
        }

        // die($filepath);

        if (file_exists($filepath)){
            // die($filepath);
            if($ext === FALSE) {
                if(Autoloader::debug) Autoloader::StPutFile(('подключили ' .$filepath));
                require_once($filepath);
            }
            else{
                if(Autoloader::debug) Autoloader::StPutFile(('нашли файл в ' .$filepath));
                return $filepath;
            }
        }
        else{
            $flag = true;
            if(Autoloader::debug) Autoloader::StPutFile(('начинаем рекурсивный поиск файла <b>' . $file . '</b> в <b>' . $path . '</b>'));
            return Autoloader::recursiveAtoload($file, $path, $ext, $flag);
        }
    }

    /**
     * Функция рекурсивного подключения класса или файла.
     * @param string $file имя файла(без расширения)
     * @param string $path путь где ищем
     * @param string $ext расширение файла
     * @param string $flag необходим для прерывания поиска если искомый файл найден
     */
    public static function recursiveAtoload($file, $path, $ext, &$flag){

        $rootDir    = self::RootDir;
        $classesDir = self::ClassesDir;
        $res = false;

        if(FALSE !== ($handle = opendir($path)) && $flag) {

            while (FAlSE !== ($dir = readdir($handle)) && $flag){
                if(strpos($dir, '.') === FALSE) {
                    $path2 = $path .'/' . $dir;
                    $filepath = $path2 . '/' . $file .(($ext === FALSE) ? '.php' : '.' . $ext);
                    if(Autoloader::debug) Autoloader::StPutFile(('ищем файл <b>' .$file .'</b> in ' .$filepath));

                    if (file_exists($filepath)){
                        $flag = FALSE;
                        if($ext === FALSE){
                            if(Autoloader::debug) Autoloader::StPutFile(('подключили ' .$filepath ));
                            require_once($filepath);
                            break;
                        }
                        else{
                            if(Autoloader::debug) Autoloader::StPutFile(('нашли файл в ' .$filepath ));
                            return $filepath;
                        }
                    }

                    $res = Autoloader::recursiveAtoload($file, $path2, $ext, $flag);
                }
            }

            closedir($handle);

        }

        return $res;

    }

    /*** Функция логирования
     * @param string $data данные для записи  ***/
    private static function StPutFile($data) {
        $rootDir = self::RootDir;
        $dir  = $rootDir .'/tmp/log.html';
        $file = fopen($dir, 'a');
        flock($file, LOCK_EX);
        fwrite($file, ('¦' .$data .'=>' .date('d.m.Y H:i:s') .'<br/>¦<br/>' .PHP_EOL));
        flock($file, LOCK_UN);
        fclose ($file);
    }

}


//////////////////////////////////////////////
//////////////////////////////////////////////

function getResponse($result, $fieldName = 'result') {
    $result = array($fieldName => $result);
    die(json_encode($result));
}

function getPostData() {
    $result = (array)json_decode(file_get_contents("php://input"));
    if(empty($result))
        return array();
    return $result;
}

//function getResponse($result, $fieldName = '') {
//    if($fieldName) {
//        $result = array($fieldName => $result);
//    }
//    else {
//        $result = array($result);
//    }
//    die(json_encode($result));
//}