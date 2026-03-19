<?php

class Database {

    public static function start_con() {

        try{

            $dbname = 'lamp_db';
            $dsn = "mysql:host=mysql;dbname=" . $dbname;

            $user = 'lamp_user';
            $password = 'lamp_password';

            $dbh = new PDO($dsn, $user, $password);

            return $dbh;

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    
}

?>