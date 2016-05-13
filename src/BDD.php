<?php

/**
 * Created by PhpStorm.
 * User: amaddah
 * Date: 12/04/16
 * Time: 04:58
 */
class BDD
{
    private function db_connection ()
    {
        try {
	    $ini = parse_ini_file(__DIR__ . '/../config/app.ini');
	    $pass = $ini['db_password'];
	    $user = $ini['db_user'];
	    $dbName = $ini['db_name'];
        $SGBD = $ini['sgbd'];
            $pdo = new PDO($SGBD . ':host=localhost;dbname=' . $dbName . ';charset=utf8', $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function execute($sql)
    {
        // Exec une commande sql
        $co = $this->db_connection();
        $co->beginTransaction();
        $q  = $co->prepare($sql);
        $q->execute();
        $co->commit();
        return $q;
    }

    public function sql_getToken($datas){
        $sql = "Select u.token as token, u.idUser as idUser FROM User u, Societe s WHERE u.Societe_idSociete=s.idSociete and u.email='" . $datas[0] . "' and u.mdp='" . $datas[1] . "' group by u.idUser";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        if ( !empty($d[0]) ) {
            return $d[0];
        }
        else
            return null;
    }

    public function sql_getBalises($mdp, $token){
        $sql = "Select nomBalise as nom FROM Balise b, User u, Societe s WHERE u.Societe_idSociete=s.idSociete and b.Societe_idSociete=s.idSociete and u.mdp='" . $mdp ."' and u.token='" . $token . "' group by b.idBalise";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        if ( !empty($d) ) {
            return $d;
        }
        else
            return null;
    }

    public function sql_getIdUser($token){
        $sql = "Select idUser FROM User WHERE token='" . $token . "'";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        if ( !empty($d[0]['idUser']) ) {
            return $d[0]['idUser'];
        }
        else
            return null;
    }

    public function sql_getPrivilege($data, $idUser){
        $sql="Select p.droit as droit, b.idBalise as idBalise FROM User u, Privilege p, Societe s, Balise b WHERE u.idUser=p.User_idUser and p.Societe_idSociete=s.idSociete and b.Societe_idSociete=s.idSociete and b.nomBalise='" . $data . "' and u.idUser='" . $idUser . "' group by u.idUser";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        if ( !empty($d[0])) {
            return $d[0];
        }
        else
            return null;
    }
    public function sql_getDatas($idBalise){
        $sql = "select trame, RSSI, timestamp, idData from Data where Balise_idBalise='" . $idBalise ."'order by timestamp desc limit 10";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        return $d;
    }

    public function sql_getRSSI($idBalise){
        $sql = "select RSSI, timestamp from Data where Balise_idBalise='" . $idBalise ."'order by timestamp desc limit 10";
        $q = $this->execute($sql);
        $d = $q->fetchAll();
        return $d;
    }
}
