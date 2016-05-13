<?php

/**
 * Created by PhpStorm.
 * User: amaddah
 * Date: 11/04/16
 * Time: 17:26
 */
class Server
{
    /* Le serveur ouvre une socket sur le port 5000 où il discute avec des clients qui se connectent afin d'obtenir des informations
     * Soit ces clients s'inscrivent, dans quel cas, on leur envoie leur jeton de connexion
     * Soit ces clients demandent les messages les concernant, dans quel cas on leur envoie les messages concernés.
     */
    private $protocol = "udp";
    private $host = "localhost";
    private $port = 0;
    private $socket = 0;
    //private $errno = 0;
    //private $errstr = "";

    //private $tailleEnTete = 5;
    private $tailleMAX = 128;
    private $log;
    private $bdd;
    private $seps = ";";
    private $sepc = "+";
    private $sepb = ",";
    private $sepd = "&";
    //private $clients;

    /**
     * Server constructor.
     * @param string $protocol
     * @param string $host
     * @param string $port
     * @param int $socket
     */
    public function __construct($protocol, $host, $port, $bdd, $log)
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->bdd = $bdd;
        $this->log = $log;
    }


    /**
     * @return int
     */

    public function getSocket()
        // Retourne le socket d'ecoute
    {
        return $this->socket;
    }

    /**
     *
     */
    public function runServer()
    {
        $this->log->write("========DEBUT========");

        if(!($sock = socket_create(AF_INET, SOCK_DGRAM, 0)))
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            die("Couldn't create socket: [$errorcode] $errormsg \n");
        }

        if( !socket_bind($sock, $this->host , $this->port) )
        {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);

            die("Could not bind socket : [$errorcode] $errormsg \n");
        }

        $this->log->write("Serveur lancé");
        while(1)
        {
            $r = socket_recvfrom($sock, $received, 128, 0, $remote_ip, $remote_port);
            $this->log->write($remote_ip . ":" . $remote_port . "--" . $received);

            // trim off the trailing/beginning white spaces
            $received = trim($received);

            if (!empty($received)) {
                $this->log->write("[Received] : " . $received);
                //$chaine_taille = substr($received, 1);
                //$taille = intval($chaine_taille);
                //$received .= @socket_read($read_sock, $taille); // recu complet

                $type = intval(substr($received, 0, 1));
                $payload = substr($received, 6);

                switch($type){
                    case 1:
                        // Connexion
                        $datas = explode($this->sepc,trim($payload),4);
                        $d = $this->bdd->sql_getToken($datas); // On recupere le token de l'utilisateur
                        $token = $d['token'];
                        $idUser= $d['idUser'];
                        if(empty($token)) {
                            $this->log->write("Token non trouvé, FIN");
                            socket_sendto($sock, PROTOCOL_ERROR, strlen(PROTOCOL_ERROR) , 0 , $remote_ip , $remote_port);
                            break;

                        }
                        $balises = $this->bdd->sql_getBalises($datas[1], $token);
                        $this->log->write("Datas : " . $datas[0] . '--' . $datas[1] . '\nToken : ' . $token);

                        $sent = $token . $this->seps;
                        if(!empty($balises)) {
                            foreach($balises as $b){
                                $sent .= $b['nom'];
                                $sent .= $this->sepb;
                            }
                            socket_sendto($sock, $sent, strlen($sent), 0, $remote_ip, $remote_port);
                            $this->log->write("[Sent] : " . $sent);
                        }else {
                            socket_sendto($sock, PRIVILEGE_ERROR, strlen(PRIVILEGE_ERROR), 0, $remote_ip, $remote_port);
                            $this->log->write("[NothingSent] : " . PRIVILEGE_ERROR);
                        }
                        break;
                    case 2:
                        // Demande de getData (user, admin, superadmin)
                        $datas = explode($this->sepc, trim($payload),2);
                        if ( empty($datas) ) {
                            socket_sendto($sock, TOKEN_ERROR, strlen(TOKEN_ERROR) , 0 , $remote_ip , $remote_port);
                            break;
                        }
                        $this->log->write("[DEBUG] privilege");
                        $idUser = $this->bdd->sql_getIdUser($datas[0]);
                        $privilege = $this->bdd->sql_getPrivilege($datas[1], $idUser);
                        if ( count($privilege) == 0 ) {
                            socket_sendto($sock, PRIVILEGE_ERROR, strlen(PRIVILEGE_ERROR) , 0 , $remote_ip , $remote_port);
                            break;
                        }
                        $idBalise = $privilege['idBalise'];
                        $droit = $privilege['droit'];

                        $this->log->write("[droit] = " . $droit);
                        $this->log->write("[idBalise] = " . $idBalise);

                        $this->log->write("[DEBUG] avant switch");

                        // Premier cas : get voltage/data and RSSI pr admin/superadmin
                        // On envoie la trame

                        if ($droit == 2 or $droit == 3) {
                            $this->log->write("[DEBUG] superadmin/admin getData");
                            $d = $this->bdd->sql_getDatas($idBalise);
                            if ( count($d) == 0 ) {
                                socket_sendto($sock, DATA_ERROR, strlen(DATA_ERROR) , 0 , $remote_ip , $remote_port);
                                break;
                            }
                            $str = $this->getNameOfPrivilege($droit) . $this->seps;
                            $i = 0;
                            foreach ($d as $one) {
                                $str .= $one['trame'];
                                $str .= $this->sepd;
                                $str .= $one['RSSI'];
                                $str .= $this->sepd;
                                $str .= $one['timestamp'];
                                $str .= $this->sepd;
                                $str .= $one['idData'];
                                $str .= $this->sepb;
                            }
                            if(!empty($str)){
                                $this->log->write("[Sent] = " . $str);
                                socket_sendto($sock, $str, strlen($str), 0 , $remote_ip , $remote_port);
                            }
                            else{
                                $this->log->write("[nothing sent]");
                                socket_sendto($sock, DATA_ERROR, strlen(DATA_ERROR) , 0 , $remote_ip , $remote_port);
                            }
                        }

                        // Deuxieme cas : get only RSSI pr technicien (user)
                        elseif($droit == 1){
                            $this->log->write("[DEBUG] user getData");
                            $d = $this->bdd->sql_getRSSI($idBalise);
                            if ( count($d) == 0 ) {
                                socket_sendto($sock, DATA_ERROR, strlen(DATA_ERROR) , 0 , $remote_ip , $remote_port);
                                break;
                            }
                            $str = $this->getNameOfPrivilege($droit) . $this->sepc;
                            $i = 0;
                            foreach ($d as $one) {
                                $str .= $one['RSSI'];
                                $str .= $this->sepd;
                                $str .= $one['timestamp'];
                                $str .= $this->sepb;
                            }
                            if(!empty($str)){
                                $this->log->write("[Sent] = " . $str);
                                socket_sendto($sock, $str, strlen($str), 0 , $remote_ip , $remote_port);
                            }
                            else{
                                $this->log->write("[nothing sent]");
                                socket_sendto($sock, DATA_ERROR, strlen(DATA_ERROR) , 0 , $remote_ip , $remote_port);
                            }
                        }
                        break;
                    case 3:
                        // modify data (superadmin)
                        break;
                    case 4:
                        // remove data (superadmin)
                        break;
                    default:
                        break;
                }
            }
        } // Fin while
        $this->closeSocket();
        $this->log->write("========FIN========");
    }

    public function getNameOfPrivilege($droit){
        switch($droit){
            case 1:
                $d="User";
                break;
            case 2:
                $d="Admin";
                break;
            case 3:
                $d="Superadmin";
                break;
            default:
                $d="guest";
                break;
        }
        return $d;
    }

    public function closeSocket()
        // Ferme la socket
    {
        fclose($this->getSocket());
    }

}