<?php
namespace DBLib;

class DBMysqli {

    private $mysqli;
    private $debugflg = false;
    private $logtype = 0;

    public function __construct($host, $username, $password, $dbname) {
        $this->mysqli = new \mysqli($host, $username, $password, $dbname);
        if($this->mysqli->connect_error) {
            error_log($this->mysqli->connect_error , $this->logtype);
            exit;
        }
        $this->mysqli->query("SET NAMES utf8"); 
    }

    public function selectPrepare($table, $column = '*', $where, $values){
      $this->emptyValidate(array('table' => $table, 'where' => $where));

      $sql = "SELECT {$column} FROM {$table} WHERE {$where} ";

      if($stmt = $this->mysqli->prepare($sql)){
        $params = array();
        foreach($values as $val) {
          $params[0] .= 's';
          $params[] = &$val;
        }

        \call_user_func_array(array($stmt, 'bind_param'), $params); 

        $stmt->execute();
        $result = $stmt->get_result();
        $datas = array();
        while($ret = $result->fetch_assoc()){
          array_push($datas, $ret);
        }

        $stmt->close();

        return $datas;
      }

      return false;
    }

    public function select($table, $column = '*', $where = '', $option = MYSQLI_ASSOC) {
      $this->emptyValidate(array('table' => $table));

      $sql = 'SELECT ';

      if(is_array($column)) {
        foreach($column as $value) {
            $sql .= $value.',';
        }

        $sql = substr($sql, 0,  -1); 
        $sql .= " FROM {$table}";

      } else {
        $sql .= "{$column} FROM {$table}"; 
      }

      if(!empty($where)) {
        $sql .= " WHERE {$where}"; 
      }

      if($this->debugflg) {
        $log = "execute sql:".$sql;
        error_log($log, $this->logtype);
      }

      $res = $this->mysqli->query($sql);
      if(!$res) {
         echo 'select error:'.$this->mysqli->error;
        if($this->debugflg) error_log($this->mysqli->error, $this->logtype);
        exit;
      }  

      $rows = null;
      while($row = $res->fetch_array($option)){
        $rows[] = $row;
      }

      return $rows;
    }

    public function insert($table, $column, $values = ''){
      $this->emptyValidate(array('table' => $table, 'column' => $column));

      $sql = "INSERT INTO {$table} ";
      $column_str = '(';
      $values_str = '(';

      if(is_array($column)) {
        foreach($column as $key => $value) {
          $column_str .= $key.',';

          if(is_string($value)){
            $values_str .= "'{$value}',"; 
          } else {
            $values_str .= $value.','; 
          }
        }
        $column_str = substr($column_str, 0,  -1).')'; 
        $values_str = substr($values_str, 0,  -1).')'; 

        $sql .= $column_str;
        $sql .= ' VALUES '.$values_str;

      } else {
        $sql .= " ({$column}) VALUES ({$values})"; 
      }

      if($this->debugflg) {
        $log = "execute sql:".$sql;
        error_log($log, $this->logtype);
      }


      $res = $this->mysqli->query($sql);
      if(!$res) {
        echo 'insert error:'.$this->mysqli->error;
        if($this->debugflg) error_log($this->mysqli->error, $this->logtype);
        exit;
      }  
      return $res;
    }

    public function insertPrepare($table, $values){
      $this->emptyValidate(array('table' => $table));

      $where_values = '';
      $column = '';
      foreach($values as $key => $val){
        $column .= "{$key},";
        $where_values .= '?,';
      }
      $column = substr($column, 0, -1);
      $where_values = substr($where_values, 0, -1);

      $sql = "INSERT INTO {$table} ({$column}) VALUES ({$where_values}) ";

      if($stmt = $this->mysqli->prepare($sql)){
        $params = array();
        foreach($values as $key => $val) {
          if(is_int($val)){
            $params[0] .= "i";
          } elseif(is_double($val)){
            $params[0] .= "d";
          } else {
            if(strpos($val, "\0") === false){
              $params[0] .= "s";
              $val = (string)$val;
            } else {
              $params[0] .= "b";
            }
          }

          $params[] = &$values[$key];
        }

        call_user_func_array(array($stmt, 'bind_param'), $params); 

        $stmt->execute();
        $stmt->close();

        return true;
      }

      return false;
    }

    public function delete($table, $where = ''){
      $this->emptyValidate(array('table' => $table));
      $sql = "DELETE FROM {$table} ";

      if(!empty($where)) {
        $sql .= "WHERE {$where}";
      }

      if($this->debugflg) {
        $log = "execute sql:".$sql;
        error_log($log, $this->logtype);
      }

      $res = $this->mysqli->query($sql);
      if(!$res) {
        echo 'delete error:'.$this->mysqli->error;
        if($this->debugflg) error_log($this->mysqli->error, $this->logtype);
        exit;
      }  
    }

    public function update($table, $column, $where = ''){
      $this->emptyValidate(array('table' => $table, 'column' => $column));

      $sql = "UPDATE {$table} SET ";
      if(is_array($column)) {
        foreach($column as $key => $value) {
          if(is_string($value)) {
            $sql .= "{$key} = '{$value}',";
          } else {
            $sql .= "{$key} = {$value},";
          }
        }
        $sql = substr($sql, 0,  -1); 

      } else {
        $sql .= "{$column}"; 
      }     

      if(!empty($where)) {
        $sql .= " WHERE {$where}"; 
      }

      if($this->debugflg) {
        $log = "execute sql:".$sql;
        error_log($log, $this->logtype);
      }

      $res = $this->mysqli->query($sql);
      if(!$res) {
         echo 'update error:'.$this->mysqli->error;
        if($this->debugflg) error_log($this->mysqli->error, $this->logtype);
        exit;
      } 
    }

    public function tranInsert($table, $column, $values = ''){
        $this->mysqli->autocommit(FALSE);

        try {
            if(!$this->insert($table, $column, $values)){
                throw new Exception('tranInsert error');
            }
        } catch(Exception $e) {
            $this->mysqli->rollback();
        }

        if($this->mysqli->commit()) {
            if($this->debugflg) error_log('commit failed', $this->logtype);
            exit();
        }

        $this->mysqli->autocommit(TRUE);
    }

    public function close(){
        $this->mysqli->close();
    }

    public function queryExec($sql){
      $res = $this->mysqli->query($sql);
      if(!$res) {
         echo 'queryExec error:'.$this->mysqli->error;
        if($this->debugflg) error_log($this->mysqli->error, $this->logtype);
        exit;
      } 

      return $res;
    }

    public function emptyValidate($data, $outname = '') {
      $error_message = '';
      if(is_array($data)) {
        foreach($data as $key => $value) {
          if(empty($value)){
            $error_message .=  "please input {$key}.";
          }
        }
      } else {
          if(empty($data)){
             $error_message .= "please input {$outname}.";        
          }
      }

      if(!empty($error_message)){
        echo $error_message;
        exit;
      }
    }

}
?>