<?php


class BaseController {

    protected $params = array();
    protected $db = array();

    public function __construct($params = array(), $db = false){
          $this->params = $params;
          $this->db = $db;
    }

    /*@
     Функция для получение POST данных
     */
    protected function postData($cast = 'array') {
        $data = json_decode(file_get_contents("php://input"));
        switch ($cast) {
            case 'array' :
                $data = (array)$data; break;
        }
        return $data;
    }

    /*@
     Функция для получение POST данных
     */
    protected function getPostData() {
        return this.postData();
    }

    /*@
     Функция для
     */
    protected function getParam($index) {
        $result = '';
        if(!empty($this->params[$index])) {
            $result = $this->params[$index];
        }
        return $result;
    }

    protected function responseCode($code) {
        http_response_code($code);
    }

    protected function log($name, $data = array(), $file = null) {
        Logger::getLogger($name, $file)->log($data);
    }

}