<?php

    include '../db/db.php';

    class DAOUser {
        private $con;
        
        public function __construct() {
            $this->con = Database::start_con();
        }

        public function getAllUsers() {
            $sql = "SELECT * FROM users";
            $stmt = $this->con->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        public function getUserByAge($age) {
            $sql = "SELECT * FROM users WHERE age = :age";
            $stmt = $this->con->prepare($sql);
            $stmt->execute(['age' => $age]);
            return $stmt->fetchAll();
        }

        public function addUser($user) {
            $sql = "INSERT INTO users (name, age, email) VALUES (:?, :?, :?)";
            $stmt = $this->con->prepare($sql);

            $stmt->bindParam(1, $usuario)
            $stmt->bindParam(2, $edad)
            $stmt->bindParam(3, $email)
            $stmt->execute($user);

            try {
                return $stmt->rowCount();
            } catch (PDOException $e) {
                throw $e;
            }
        }
        
    }
?>