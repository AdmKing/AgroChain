<?php
// backend/models/User.php

class UserController {
    private $pdo;
    private $table = "users";

    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $pwd;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function registerUser() {
        $query = "INSERT INTO " . $this->table . " 
        (firstname, lastname, email, phone, pwd, created_at) 
        VALUES (:firstname, :lastname, :email, :phone, :pwd, NOW())";

        $stmt = $this->pdo->prepare($query);

        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':pwd', $this->pwd);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>
