<?php


class ImageLoaderClass {

    protected $params = array();
    protected $sourceFile;
    protected $newFile;
    protected $postData = array();

    public function __construct($params = array()){
        $this->params = $params;
    }

    protected function loadFile() {
        // getResponse($_FILES);
        $this->sourceFile = $_FILES['image']['tmp_name']; // Временное расположение файла
        $this->newFile    = __DIR__. '/../uploads/' . $_FILES['image']['name']; // Конечный путь к файлу и его название
    }

    protected function save() {
        $r = move_uploaded_file( $this->sourceFile // временное расположение файла
                                ,$this->newFile   //  конечный путь к файлу и его название
                               );
        return $r;
    }

    public function imgLoader() {
        $this->postData = $this->getPostData();
        $this->loadFile();
        return $this->save();
    }

    protected function getPostData() {
        $result = (array)json_decode(file_get_contents("php://input"));
        if(empty($result))
            return array();
        return $result;
    }


}