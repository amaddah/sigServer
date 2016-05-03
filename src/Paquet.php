<?php

/**
 * Created by PhpStorm.
 * User: amaddah
 * Date: 11/04/16
 * Time: 19:24
 */
class Paquet
{
    private $type = 0;
    private $payload = "";

    private $tailleTrame = 0;


    private $sep = ";";
    private $tailleType = 1;
    private $octetTaille = 4;
    private $tailleEnTete = 5;

    /**
     * Paquet constructor.
     * @param int $type
     * @param string $payload
     */
    public function __construct($type, $payload)
    {
        $this->type = $type;
        $this->payload = $payload;
    }

}