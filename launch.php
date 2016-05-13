<?php
/**
 * Created by PhpStorm.
 * User: amaddah
 * Date: 11/04/16
 * Time: 17:38
 */

require(__DIR__ . "/src/Server.php");
require(__DIR__ . "/src/BDD.php");
require(__DIR__ . "/src/Log.php");

define("PROTOCOL_ERROR", "Protocol Error");
define("PRIVILEGE_ERROR", "Privilege Error");
define("DATA_ERROR", "Protocol Error");
define("TOKEN_ERROR", "Token Error");

if(!isset($argv[1]))
    die("Usage : php launch.php < IPDuServer >\n");

$bdd = new BDD();
$log = new Log(__DIR__ . "/logs/test.txt");

$serv = new Server("udp", $argv[1], 5000, $bdd, $log); //  Constructeur de serveur prend 3 parametres : le protocol, l'ip et enfin le port

$serv->runServer(); // Ecoute en UDP sur le port 5000 sur la machine locale
$serv->closeSocket(); // Ferme la socket UDP localhost:5000
