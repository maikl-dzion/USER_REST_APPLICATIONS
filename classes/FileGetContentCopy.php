<?php

  class FileGetContentCopy {

      protected $delimiter = "";
      protected $_path;
      protected $filePath;
      protected $newFilePath;
      protected $params = array();

      public function __construct($params = array())
      {
          $this->params = $params;
          if(!empty($this->params[0])) {
              $this->filePath = $this->params[0];
              $this->_path = $this->params[0];
          }

          if(!empty($this->params[1])) {
              $this->newFilePath = $this->params[1];
          }

      }

      protected function getContent($path)
      {
          $result = file($path);
          return $result;
      }

      protected function setDelimiter($delimiter)
      {
          $this->delimiter = $delimiter;
      }

      protected function saveContent($path, $data)
      {
          $content = $this->contentFormatted($data);
          $r = file_put_contents($path, $content);
          return $r;
      }

      protected function contentFormatted($data)
      {
          $content = "";
          $content = implode($this->delimiter, $data);
          // foreach ($data as $key => $val) {$content .= $val;}
          return $content;
      }

      /**
       * @param $filePath
       * @param $newFilePath
       * @return bool|int
       */
      protected function _copy($filePath, $newFilePath)
      {
          $result = $this->getContent($filePath);
          $r = $this->saveContent($newFilePath, $result);
          return $r;
      }

      public function getFileContent()
      {
          $path = $this->filePath;
          $result = $this->getContent($path);
          $result = $this->contentFormatted($result);
          return $result;
      }

      public function copyFileContent()
      {
          $filePath = $newFilePath = '';
          $filePath = $this->filePath;
          $newFilePath = $this->newFilePath;
          $r = $this->_copy($filePath, $newFilePath);
          return $r;
      }

      public function scandir() {
          // $path = $this->_path;
          $path = $_SERVER['DOCUMENT_ROOT'];
          if($this->_path)
              $path = '/' . implode("/", $this->params);

          $res    = scandir($path);
          $result = array();
          foreach($res as $key => $value) {
              if($value == '.' || $value == '..') continue;
              $type = '';
              $item = array(
                   'path'  => $path . '/' . $value
                  ,'value' => $value
                  ,'type'  => $type
              );
              $result[] = $item;
          }
          return $result;
      }

      public function fileRead() {
          if($this->_path)
              $path = '/' . implode("/", $this->params);

          $filename = "DownloadedFile.php";   // имя файл предложенное для сохранения в окне браузера
          $myFile   = $path; // файл на серевере

          $mm_type="application/octet-stream";

          header("Cache-Control: public, must-revalidate"); // кешировать
          header("Pragma: hack");
          header("Content-Type: " . $mm_type);
          header("Content-Length: " .(string)(filesize($myFile)) );
          header('Content-Disposition: attachment; filename="'.$filename.'"');
          header("Content-Transfer-Encoding: binary");

          return readfile($myFile); // прочитать файл и отправить в поток

      }

      public function getFile() {
          $filePath = $this->params[0];
          $path   = $_SERVER['DOCUMENT_ROOT'] . '/USER_APPLICATIONS_V1/' . $filePath;
          $result = $this->filePrint($path);
          return $result;
      }

      protected function filePrint($filePath) {
          $result = str_replace("\n","<br />\n", htmlspecialchars(implode("\n", file($filePath) )) );
          return $result;
      }

      public function getBasePath() {
          return $_SERVER['DOCUMENT_ROOT'];
      }

      protected function getPostData() {
          $result = (array)json_decode(file_get_contents("php://input"));
          if(empty($result))
              return array();
          return $result;
      }

  }

?>