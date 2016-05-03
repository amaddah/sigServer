<?php

/**
 * Created by PhpStorm.
 * User: amaddah
 * Date: 11/04/16
 * Time: 19:31
 */
class Log
{
    private $path;

    /**
     * Log constructor.
     * @param $fd
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function write($msg)
    {
        $fd = fopen($this->path, "a");
        fwrite($fd, $msg . "\n");
        fclose($fd);
    }

}