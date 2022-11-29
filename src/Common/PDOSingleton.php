<?php

namespace App\Common;

use PDO;
use Symfony\Component\VarDumper\Caster\PdoCaster;

class PDOSingleton{
    private static PDOSingleton $instance;
    private PDO $pdo;

    private function __construct (){
        $this->pdo = new PDO("mysql:host=db;dbname=tp;charset=utf8mb4", "root", "root");
    }

    public static function get () : PDO {
        if ( ! isset( self::$instance ))
            self::$instance = new PDOSingleton();
        return self::$instance->pdo;
    }

}