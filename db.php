<?php
require 'vendor/autoload.php';
class dbpavalco{

    private $dbHost = 'tcp:c6mnuw0mvt.database.windows.net,1433';
    private $dbUser = 'AdminPavalco@c6mnuw0mvt';
    private $dbPass = 'P@valco2014';
    private $dbSchema = 'helpdesktest';


    private $dbConn;

    public function getDbHost() { return $this->dbHost; }
    public function getDbUser() { return $this->dbUser; }
    public function getDbPass() { return $this->dbPass; }
    public function getDbSchema() { return $this->dbSchema; }
    public function getConn() { return $this->dbConn; }


    function __construct() {
        $connectionTimeoutSeconds = 60;
        $connectionOptions = array("Database"=>$this->dbSchema, "Characterset" =>"UTF-8",
            "Uid"=>$this->dbUser, "PWD"=>$this->dbPass, "LoginTimeout" => $connectionTimeoutSeconds);
        $conn = null;
        $this->dbConn = sqlsrv_connect($this->dbHost, $connectionOptions);
    }

    function __destruct() {
        unset($this->dbConn);
    }

    public function doSelectASJson($query){ return json_encode($this->dbConn->query($query)->fetchAll(5)); }

    public function doQuery($query) { $this->dbConn->query($query); }

    public function getQuery($query) {
        $result = sqlsrv_query($this->dbConn, $query);
        if ($result == FALSE)
            return false;

        $set = [];
        while($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) { $set[] = $row; }
        sqlsrv_free_stmt($result);
        return $set;
    }

    public function getQueryAsArray($query) { return $this->dbConn->query($query)->fetchAll(3); }

    public function getLastId(){return $this->dbConn->query('SELECT LAST_INSERT_ID()')->fetchColumn(0);}

    public function getOneField($query){return $this->dbConn->query($query)->fetchColumn(0);}

    public function initSession($userdata){
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['uid'] = $userdata['id'];
        $_SESSION['nombre'] = $userdata['nombre'];
        $_SESSION['usuario'] = $userdata['usuario'];
        $_SESSION['correoe'] = $userdata['correoe'];
        $_SESSION['workingon'] = 0;
        $_SESSION['logged'] = true;
    }

    public function getSession(){
        try{
            session_start();
            $sess = array();
            $sess['uid'] = $_SESSION['uid'];
            $sess['nombre'] = $_SESSION['nombre'];
            $sess['usuario'] = $_SESSION['usuario'];
            $sess['correoe'] = $_SESSION['correoe'];
            $sess['workingon'] = $_SESSION['workingon'];
            $sess['logged'] = $_SESSION['logged'];
            return $sess;
        }catch(Exception $e){
            return ['Error' => $e->getMessage()];
        }
    }

    public function finishSession(){
        if (!isset($_SESSION)) {
            session_start();
        }
        if(isset($_SESSION['uid'])){
            unset($_SESSION['uid']);
            unset($_SESSION['nombre']);
            unset($_SESSION['usuario']);
            unset($_SESSION['correoe']);
            unset($_SESSION['workingon']);
            unset($_SESSION['logged']);
            $info='info';
            if(isSet($_COOKIE[$info])){
                $cookie_time = 86400;
                setcookie ($info, '', time() - $cookie_time);
            }
            $msg="Logged Out Successfully...";
        }
        else{
            $msg = "Not logged in...";
        }
        return $resultado[] = $msg;
    }
}