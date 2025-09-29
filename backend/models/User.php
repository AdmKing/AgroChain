<?php
// backend/models/User.php

class User {
    private $pdo;
    private $table = "users";

    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $pwd;
    public $role_id;


    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Register new user
    public function registerUser() {
        $query = "INSERT INTO " . $this->table . " 
        (firstname, lastname, email, phone, pwd, role_id, created_at) 
        VALUES (:firstname, :lastname, :email, :phone, :pwd, :role_id, NOW())";

        $stmt = $this->pdo->prepare($query);

        $stmt->bindParam(':firstname', $this->firstname);
        $stmt->bindParam(':lastname', $this->lastname);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':pwd', $this->pwd);
        $stmt->bindParam(':role_id', $this->role_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // check of email already exists in database
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // create new user
    public function createUser($firstname, $lastname, $email, $phone, $password, $role_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (firstname, lastname, email, phone, pwd, role_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        return $stmt->execute([$firstname, $lastname, $email, $phone, $hashedPassword, $role_id]);
    }

}
?>
