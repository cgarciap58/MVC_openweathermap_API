<?php

include '../db/db.php';

class DAOWeather {
        private $con;
        
        public function __construct() {
            $this->con = Database::start_con();
        }
        
    }
?>
