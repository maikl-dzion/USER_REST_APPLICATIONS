<?php

trait QueryCRUDActionsTrait {

     protected function insertBuilderPrepare($fields, $data) {
         $_fields = $_alias = $_values = array();

         foreach ($fields as $fieldName => $value) {
             if(!empty($data[$fieldName])) {

                 $value = $data[$fieldName];
                 $alias = ":" . $fieldName;

                 $_fields[] = $fieldName;
                 $_alias[]  = $alias;
                 $_values[$alias] = $value;
             }
         }

         $fieldsStr = implode(',', $_fields);
         $aliasStr = implode(',' , $_alias);
         $query    = "({$fieldsStr})  VALUES({$aliasStr})";
         return array( 'query' => $query, 'values' => $_values);
    }

    protected function updateBuilderPrepare($fields, $data){
        $queryArray = $_values = array();
        foreach($fields as $fieldName => $value) {
            if(!empty($data[$fieldName])) {
                $alias = ":{$fieldName}";
                $value = $data[$fieldName];
                $queryArray[] = "{$fieldName} = {$alias}";
                $_values[$alias]  = $value;
            }
        }

        $query = implode(',', $queryArray);
        return array( 'query' => $query, 'values' => $_values);
    }

    protected function bindExecute($query, $data, $debugShow = false) {
        $stmt = $this->db->prepare($query);
        foreach ($data as $fieldName => $value) {
            // $value = htmlspecialchars(strip_tags($value));
            $stmt->bindValue("{$fieldName}" , $value);
        }
        $debug  = $error = array();
        $status = $stmt->execute();
        $error  = $stmt->errorInfo();
        if($debugShow)
           $debug  = $stmt->debugDumpParams();
        return array(
            'status' => $status,
            'error'  => $error,
            'stmt'   => $stmt,
            'debug'  => $debug
        );
    }

    protected function truncateTable($tableName) {
        $stmt = $this->db->prepare("TRUNCATE TABLE  {$tableName}");
        $result = $stmt->execute();
        return $result;
    }

    protected function saveStmtLog($resp) {

        $info = new stdClass();
        $info->status = $resp['status'];
        $info->error  = $resp['error'];
        $info->stmt   = $resp['stmt'];
        $info->debug  = $resp['debug'];

        $info->query      = '';
        $info->errCode    = '';
        $info->errCodeNum = '';
        $info->errMessage = '';

        if(!empty($resp['error'][0]))
            $info->errCode = $resp['error'][0];

        if(!empty($resp['error'][1]))
            $info->errCodeNum = $resp['error'][1];

        if(!empty($resp['error'][2]))
            $info->errMessage = $resp['error'][2];

        if(!empty($resp['stmt']->queryString))
            $info->query = $resp['stmt']->queryString;

        return $info;

        //  [0] => 23505
        //  [1] => 7
        //  [2] => ERROR:  duplicate key value violates unique constraint "users_login_key"
    }

}