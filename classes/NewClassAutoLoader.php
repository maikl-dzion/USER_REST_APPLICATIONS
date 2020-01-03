<?php


namespace maiklNamespace {

    /**
     * Усовершенствованный класс автозагрузчик
     *
     * Умеет загружать как недостающие классы, так и указанные файлы.<br/>
     * Больше нет необходимости писать инклюды, достаточно вызвать<br/>
     * функцию автозагрузки и сказать ей имя и расширене файла,<br/>
     * а можно еще и дирректорию уточнить(иначе ищем из корня), и будет счастье.
     *
     * @author Автор: Барулин Максим
     * @version 1.0
     * @copyright: Барулин Максим
     */
    class Autoloader
    {
        /**
         * переменная, отвечающая за отладку
         *
         * @var type const
         *
         */
        const debug = 0;

        public function __construct()
        {
        }

        /**
         * Функция прямого подключения класса или файла.<br/>
         * В случае неудачи, вызывает функцию рекурсивного поиска.
         *
         * @param string $file имя файла(без расширения)
         * @param string $ext расширение файла(без точки)
         * @param string $dir директория для поиска(без первого и последнего слешей)
         *
         * @return string
         * @return false
         *
         */
        public static function autoload($file, $ext = false, $dir = false)
        {
            $file = str_replace('\\', '/', $file);

            if ($ext === false) {
                $path = $_SERVER['DOCUMENT_ROOT'] . '/classes';
                $filepath = $_SERVER['DOCUMENT_ROOT'] . '/classes/' . $file . '.php';
            } else {
                $path = $_SERVER['DOCUMENT_ROOT'] . (($dir) ? '/' . $dir : '');
                $filepath = $path . '/' . $file . '.' . $ext;
            }

            if (file_exists($filepath)) {
                if ($ext === false) {
                    if (Autoloader::debug) {
                        Autoloader::StPutFile(('подключили ' . $filepath));
                    }
                    require_once($filepath);
                } else {
                    if (Autoloader::debug) {
                        Autoloader::StPutFile(('нашли файл в ' . $filepath));
                    }
                    return $filepath;
                }
            } else {
                $flag = true;
                if (Autoloader::debug) {
                    Autoloader::StPutFile(('начинаем рекурсивный поиск файла <b>' . $file . '</b> в <b>' . $path . '</b>'));
                }
                return Autoloader::recursive_autoload($file, $path, $ext, $flag);
            }
        }

        /**
         * Функция рекурсивного подключения класса или файла.
         *
         * @param string $file имя файла(без расширения)
         * @param string $path путь где ищем
         * @param string $ext расширение файла
         * @param string $flag необходим для прерывания поиска если искомый файл найден
         *
         * @return string
         * @return bool
         *
         */
        public static function recursive_autoload($file, $path, $ext, &$flag)
        {
            if (false !== ($handle = opendir($path)) && $flag) {
                while (false !== ($dir = readdir($handle)) && $flag) {

                    if (strpos($dir, '.') === false) {
                        $path2 = $path . '/' . $dir;
                        $filepath = $path2 . '/' . $file . (($ext === false) ? '.php' : '.' . $ext);
                        if (Autoloader::debug) {
                            Autoloader::StPutFile(('ищем файл <b>' . $file . '</b> in ' . $filepath));
                        }

                        if (file_exists($filepath)) {
                            $flag = false;
                            if ($ext === false) {
                                if (Autoloader::debug) {
                                    Autoloader::StPutFile(('подключили ' . $filepath));
                                }
                                require_once($filepath);
                                break;
                            } else {
                                if (Autoloader::debug) {
                                    Autoloader::StPutFile(('нашли файл в ' . $filepath));
                                }
                                return $filepath;
                            }
                        }
                        $res = Autoloader::recursive_autoload($file, $path2, $ext, $flag);
                    }
                }
                closedir($handle);
            }
            return $res;
        }

        /**
         * Функция логирования
         *
         * @param string $data данные для записи
         *
         * @return void
         *
         */
        private static function StPutFile($data)
        {
            $dir = $_SERVER['DOCUMENT_ROOT'] . '/Log/Log.html';
            $file = fopen($dir, 'a');
            flock($file, LOCK_EX);
            fwrite($file, ('║' . $data . '=>' . date('d.m.Y H:i:s') . '<br/>║<br/>' . PHP_EOL));
            flock($file, LOCK_UN);
            fclose($file);
        }
    }

    \spl_autoload_register('maiklNamespace\Autoloader::autoload');
}