<?php

define('ROOT_DIR', __DIR__);
define('LOG_DIR', ROOT_DIR . '/logs');
// $dirname = dirname(__FILE__);

include_once ROOT_DIR . '/bootstrap.php';

$config =  array(
     'host'     => '185.63.191.96'
    ,'dbname'   => 'maikldb'
    ,'user'     => 'w1user'
    ,'password' => 'w1password'
    ,'driver'   => 'pgsql'
    ,'port'     => '5432'
);

$args = array();

$userVisit = printUserVisit();
// echo $userVisit;

try {

    saveUserVisit(); // отслеживаем визиты
    $db = new DB($config);   // создаем подключение к базе
    Logger::$PATH = LOG_DIR; // задаем директорию логов

    // Logger::getLogger($name, $file)->log($data);
    // saveLog($error, "API-error");

    $request = new RequestHandler($args, $db);  // Создаем обработчик HTTP-запроса
    $result  = $request->run();                 // Запускам контроллер класса

} catch (Exception $ex) {
    //Выводим сообщение об исключении.
    $errMessage = $ex->getMessage();
    $error = array('Type' => 'Try Catch',
                   'Messages' => $errMessage,
                   'Ex' => $ex);
    lg($error);
    saveLog($error, "API-error");
    exit;
}


// throw new Exception('Incorrect document id');

function except($text){
    throw new Exception($text);
}

/**
    Примеры запросов
    http://bolderfest.ru/USER_REST_APPLICATIONS/api.php/FileGetContentCopy/scandir  /получить список файлов в директории
 */

// Проверка ошибок
// ytuuu  // первая ошибка
// die('gfh  gfhyrhh'); // вторая ошибка
// $request = array('error' => 'пользовательская ошибка');  // третья ошибка

// $responseName = "result";

getResponse($result);


function lg() {

    $debugTrace = debug_backtrace();
    $args = func_get_args();

    $get = false;
    $output = $traceStr = '';

    $style = 'margin:10px; padding:10px; border:3px red solid;';

    foreach ($args as $key => $value) {
        $itemArr = array();
        $itemStr = '';
        is_array($value) ? $itemArr = $value : $itemStr = $value;
        if ($itemStr == 'get') $get = true;
        $line = print_r($value, true);
        $output .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
    }

    foreach ($debugTrace as $key => $value) {
        // if($key == 'args') continue;
        $itemArr = array();
        $itemStr = '';
        is_array($value) ? $itemArr = $value : $itemStr = $value;
        if ($itemStr == 'get') $get = true;
        $line = print_r($value, true);
        $output .= '<div style="' . $style . '" ><pre>' . $line . '</pre></div>';
    }


    if ($get)  return $output;

    print $output;
    //print '<pre>' . print_r($debug) . '</pre>';
    die ;

}


function saveLog($name, $data = array(), $file = null) {
    Logger::getLogger($name, $file)->log($data);
}


function saveUserVisit() {

    $file = LOG_DIR . "/visit.log";    //куда пишем логи
    $col_zap = 4999;                    //строк в файле не более

    if (strstr($_SERVER['HTTP_USER_AGENT'], 'YandexBot')) {$bot='YandexBot';}
    elseif (strstr($_SERVER['HTTP_USER_AGENT'], 'Googlebot')) {$bot='Googlebot';}
    else { $bot=$_SERVER['HTTP_USER_AGENT']; }

    $ip = getIpAddress();
    $date = date("H:i:s d.m.Y");        //дата события
    $home = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];    //какая страница сайта
    $lines = file($file);
    while(count($lines) > $col_zap) array_shift($lines);
    $lines[] = $date."___bot:" . $bot. "___remote-ip:" . $ip . "___home:" . $home . "___\r\n";
    file_put_contents($file, $lines);
}

function getIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))        // Определяем IP
    { $ip=$_SERVER['HTTP_CLIENT_IP']; }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))    // Если IP идёт через прокси
    { $ip=$_SERVER['HTTP_X_FORWARDED_FOR']; }
    else { $ip=$_SERVER['REMOTE_ADDR']; }
    return $ip;
}


function printUserVisit() {
    $fileName = LOG_DIR . "/visit.log";
    $file = file($fileName);
    ob_start(); ?>

        <html>
        <head>
            <style type='text/css'>
                td.zz {padding-left: 3px; font-size: 9pt; padding-top: 2px; font-family: Arial; }
            </style>
        </head>
       <body>

           <table width="680" cellspacing="1" cellpadding="1" border="0" STYLE="table-layout:fixed">
           <tr bgcolor="#eeeeee">
             <td class="zz" width="100"><b>Время, дата</b></td>
             <td class="zz" width="200"><b>Кто посещал</b></td>
             <td class="zz" width="100"><b>IP, прокси</b></td>
             <td class="zz" width="280"><b>Посещенный URL</b></td>
           </tr>

    <?php

    $count = sizeof($file);

    foreach ($file as $si => $values) {
        $string = explode("___", $file[$si]);
        $q1[$si] = $string[0]; // дата и время
        $q2[$si] = $string[1]; // имя бота
        $q3[$si] = $string[2]; // ip бота
        $q4[$si] = $string[3]; // адрес посещения

        echo "<tr bgcolor='#eeeeee' >
                  <td class='zz' >{$q1[$si]}</td>
                  <td class='zz' >{$q2[$si]}</td>
                  <td class='zz' >{$q3[$si]}</td>
                  <td class='zz' >{$q4[$si]}</td>
              </tr>";
    }

    echo '</table></body></html>';
    $result = ob_get_clean();
    return $result;
}