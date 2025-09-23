<?php
class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function registerUser() {
        // Try JSON first
        $data = json_decode(file_get_contents('php://input'), true);

        // If not JSON, fallback to normal POST
        if (!$data || !is_array($data)) {
            $data = $_POST;
        }

        // Sanitize (optional but recommended)
        $firstname = $data['firstname'] ?? '';
        $lastname  = $data['lastname'] ?? '';
        $email     = $data['email'] ?? '';
        $phone     = $data['phone'] ?? '';
        $pwd       = $data['pwd'] ?? '';

        // Validate
        if (
            empty($firstname) ||
            empty($lastname) ||
            empty($email) ||
            empty($phone) ||
            empty($pwd)
        ) {
            echo json_encode(["message" => "Incomplete data"]);
            return;
        }

        // Hash password
        $hashedPwd = password_hash($pwd, PASSWORD_BCRYPT);

        // Insert
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (firstname, lastname, email, phone, pwd, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())"
        );
        try {
            $stmt->execute([$firstname, $lastname, $email, $phone, $hashedPwd]);
            echo json_encode(["message" => "User registered successfully"]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate entry (email)
                echo json_encode(["message" => "Email already exists"]);
            } else {
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
        }
    }
}