<?php
class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 🔹 Register a new user
     */
    public function registerUser() {
        // Accept JSON or form POST
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            $data = $_POST;
        }

        $firstname = trim($data['firstname'] ?? '');
        $lastname  = trim($data['lastname'] ?? '');
        $email     = trim($data['email'] ?? '');
        $phone     = trim($data['phone'] ?? '');
        $pwd       = trim($data['pwd'] ?? '');
        $role_name = trim($data['role'] ?? 'User'); // e.g. "Admin" or "User"

        // Map role name to role_id (adjust these IDs to match your DB)
        $role_map = [
            'Admin'              => 1,
            'Farmer'             => 2,
            'Procurement Officer'=> 3,
            'Sales Manager'      => 4,
            'Buyer'              => 5
        ];
        $role_id = $role_map[$role_name] ?? 2; // Default normal user

        // Validation
        if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($pwd)) {
            echo json_encode(["status"=>"error","message"=>"Incomplete data"]);
            return;
        }

        // Hash password
        $hashedPwd = password_hash($pwd, PASSWORD_BCRYPT);

        // Insert into DB
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (firstname, lastname, email, phone, pwd, role_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        try {
            $stmt->execute([$firstname, $lastname, $email, $phone, $hashedPwd, $role_id]);
            echo json_encode(["status"=>"success","message"=>"User registered successfully"]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(["status"=>"error","message"=>"Email already exists"]);
            } else {
                echo json_encode(["status"=>"error","message"=>"Error: ".$e->getMessage()]);
            }
        }
    }

    /**
     * 🔹 Login a user & issue token
     */
    public function loginUser() {
        // Try JSON first
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !is_array($data)) {
            $data = $_POST;
        }

        $email = $data['email'] ?? '';
        $pwd   = $data['pwd'] ?? '';

        if (empty($email) || empty($pwd)) {
            echo json_encode(["message" => "Email and password required"]);
            return;
        }

        // Find user
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pwd, $user['pwd'])) {
            // ✅ Create a token
            $token = bin2hex(random_bytes(32)); // 64-char random token

            // ✅ Save it to DB
            $update = $this->pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
            $update->execute([$token, $user['id']]);

            // Don’t send password in response
            unset($user['pwd']);
            $user['api_token'] = $token;

            echo json_encode([
                "message" => "Login successful",
                "user" => $user,
                "token" => $token
            ]);
        } else {
            echo json_encode(["message" => "Invalid credentials"]);
        }
    }


    /**
     * 🔹 Private method to authenticate a request
     */
    private function authenticate() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE api_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                return $user;
            }
        }

        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized']);
        exit;
    }

    /**
     * 🔹 Admin creates a new user
     */
    public function adminCreateUser() {
        // Include our token helper
        require_once __DIR__ . '/../helpers/auth.php';   

        // Require a valid token and get the user
        $currentUser = requireAuthToken($this->pdo);

        // Only admins can create new users
        if ((int)$currentUser['role_id'] !== 1) {
            http_response_code(403);
            echo json_encode(['message' => 'Access denied.']);
            exit;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $firstname = $data['firstname'] ?? '';
        $lastname  = $data['lastname'] ?? '';
        $email     = $data['email'] ?? '';
        $phone     = $data['phone'] ?? '';
        $pwd       = $data['pwd'] ?? '';
        $role_name = $data['role'] ?? 'User';

        $role_map = [
            'Admin' => 1,
            'Farmer'  => 2,
            'Procurement Officer' => 3,
            'Sales Manager' => 4,
            'Buyer' => 5
        ];
        $role_id = $role_map[$role_name] ?? 2;

        if (
            empty($firstname) ||
            empty($lastname) ||
            empty($email) ||
            empty($phone) ||
            empty($pwd)
        ) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
            return;
        }

        $hashedPwd = password_hash($pwd, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (firstname, lastname, email, phone, pwd, role_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        try {
            $stmt->execute([$firstname, $lastname, $email, $phone, $hashedPwd, $role_id]);
            echo json_encode(["message" => "User created successfully"]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                echo json_encode(["message" => "Email already exists"]);
            } else {
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
        }
    }

    /**
     * 🔹 Reset password using email and new password
     */
    public function resetPassword() {
        // Use the auth helpers (for getBearerToken/getUserByToken)
        require_once __DIR__ . '/../helpers/auth.php';

        // Read input: JSON first, else form-data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents("php://input"), true) ?: [];
        } else {
            $data = $_POST;
        }

        $email = $data['email'] ?? '';
        $newPwd = $data['new_password'] ?? '';
        $oldPwd = $data['old_password'] ?? ''; // optional for owners
        $bodyToken = $data['token'] ?? null;   // fallback token in body (convenience for testing)

        if (empty($email) || empty($newPwd)) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data: email and new_password required"]);
            return;
        }

        // Get token from header first, else fallback to body token
        $headerToken = getBearerToken();
        $token = $headerToken ?: $bodyToken;

        if (!$token) {
            http_response_code(401);
            echo json_encode(["message" => "Missing token. Provide Authorization: Bearer <token> header or token in body"]);
            return;
        }

        // Validate token and get current user
        $currentUser = getUserByToken($this->pdo, $token);
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(["message" => "Invalid or expired token"]);
            return;
        }

        // Admin may reset any user's password
        if ((int)$currentUser['role_id'] === 1) {
            // admin flow: just update the target user's password
            $hashedPwd = password_hash($newPwd, PASSWORD_BCRYPT);

            $stmt = $this->pdo->prepare("UPDATE users SET pwd = ?, otp_code = NULL, otp_expires = NULL, api_token = NULL WHERE email = ?");
            try {
                if ($stmt->execute([$hashedPwd, $email])) {
                    echo json_encode(["message" => "Password reset successfully (by admin)."]);
                } else {
                    echo json_encode(["message" => "Error resetting password"]);
                }
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(["message" => "Error: " . $e->getMessage()]);
            }
            return;
        }

        // Non-admin (owner) flow: can only reset their own account
        if (strtolower($currentUser['email']) !== strtolower($email)) {
            http_response_code(403);
            echo json_encode(["message" => "You can only reset your own password. For account recovery use the OTP flow."]);
            return;
        }

        // Owner must provide old password (or use the OTP flow instead)
        if (empty($oldPwd)) {
            http_response_code(400);
            echo json_encode(["message" => "Provide old_password to reset your password, or use the OTP reset flow."]);
            return;
        }

        // Verify old password (we have currentUser['pwd'] since getUserByToken uses SELECT *)
        if (!password_verify($oldPwd, $currentUser['pwd'])) {
            http_response_code(403);
            echo json_encode(["message" => "Old password is incorrect"]);
            return;
        }

        // All good — update password and invalidate tokens/OTPs
        $hashedPwd = password_hash($newPwd, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("UPDATE users SET pwd = ?, otp_code = NULL, otp_expires = NULL, api_token = NULL WHERE email = ?");
        try {
            if ($stmt->execute([$hashedPwd, $email])) {
                echo json_encode(["message" => "Password reset successfully"]);
            } else {
                echo json_encode(["message" => "Error resetting password"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["message" => "Error: " . $e->getMessage()]);
        }
    }

    /**
     * 🔹 Send OTP to email for password reset
     */
    public function sendResetOTP() {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $email = $data['email'] ?? '';

        if (empty($email)) {
            http_response_code(400);
            echo json_encode(["message" => "Email is required"]);
            return;
        }

        // Check user exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            http_response_code(404);
            echo json_encode(["message" => "User not found"]);
            return;
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $expires = date("Y-m-d H:i:s", time() + 600); // 10 minutes from now

        // Save OTP in DB
        $stmt = $this->pdo->prepare("UPDATE users SET otp_code=?, otp_expires=? WHERE email=?");
        $stmt->execute([$otp, $expires, $email]);

        // TODO: actually send via email; for now return OTP for testing
        echo json_encode(["message" => "OTP generated", "otp" => $otp]);
    }

    /**
     * 🔹 Reset password using OTP
     */
    public function resetPasswordWithOTP() {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $email = $data['email'] ?? '';
        $otp = $data['otp'] ?? '';
        $newPwd = $data['new_password'] ?? '';

        if (empty($email) || empty($otp) || empty($newPwd)) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
            return;
        }

        // Verify OTP
        $stmt = $this->pdo->prepare("SELECT otp_code, otp_expires FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['otp_code'] !== $otp || strtotime($row['otp_expires']) < time()) {
            http_response_code(403);
            echo json_encode(["message" => "Invalid or expired OTP"]);
            return;
        }

        // Hash new password
        $hashedPwd = password_hash($newPwd, PASSWORD_BCRYPT);

        // Update password & clear OTP
        $stmt = $this->pdo->prepare("UPDATE users SET pwd=?, otp_code=NULL, otp_expires=NULL WHERE email=?");
        if ($stmt->execute([$hashedPwd, $email])) {
            echo json_encode(["message" => "Password reset successfully"]);
        } else {
            echo json_encode(["message" => "Error resetting password"]);
        }
    }

}