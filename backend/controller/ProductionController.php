<?php
// backend/controller/ProductionController.php
require_once __DIR__ . '/../helpers/auth.php';

class ProductionController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. Farmer creates production plan
    public function createProduction($farmer)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $crop_name             = $data['crop_name'] ?? '';
        $quantity_planned      = $data['quantity_planned'] ?? '';
        $start_date            = $data['start_date'] ?? '';
        $expected_harvest_date = $data['expected_harvest_date'] ?? '';
        $notes                 = $data['notes'] ?? '';

        if (empty($crop_name) || empty($quantity_planned) || empty($start_date) || empty($expected_harvest_date)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing required fields"]);
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO production (farmer_id, crop_name, quantity_planned, start_date, expected_harvest_date, notes, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );

        try {
            $stmt->execute([
                $farmer['id'],
                $crop_name,
                $quantity_planned,
                $start_date,
                $expected_harvest_date,
                $notes
            ]);
            echo json_encode(["status" => "success", "message" => "Production plan created successfully"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }

    // 2. Farmer logs a production stage / update
    public function addProductionUpdate($farmer)
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $production_id = $data['production_id'] ?? '';
        $stage         = $data['stage'] ?? '';
        $notes         = $data['notes'] ?? '';

        if (empty($production_id) || empty($stage)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing production_id or stage"]);
            return;
        }

        // Check ownership of the production plan
        $stmt = $this->pdo->prepare("SELECT id FROM production WHERE id=? AND farmer_id=?");
        $stmt->execute([$production_id, $farmer['id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "You do not own this production plan"]);
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO production_updates (production_id, stage, notes, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$production_id, $stage, $notes]);

        echo json_encode(["status" => "success", "message" => "Production stage logged"]);
    }

    // 3. List productions
    public function listProduction($user)
    {
        if ($user['role_id'] == 1) {
            $stmt = $this->pdo->query("SELECT * FROM production");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM production WHERE farmer_id=?");
            $stmt->execute([$user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status'=>'success','data'=>$rows]);
    }

    // 4. List production stage updates
    public function listProductionUpdates($user, $production_id) {
        if (empty($production_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Missing production_id']);
            return;
        }

        // Admin can see all updates
        if ($user['role_id'] == 1) {
            $stmt = $this->pdo->prepare(
                "SELECT pu.*, p.crop_name 
                FROM production_updates pu 
                JOIN production p ON pu.production_id = p.id 
                WHERE pu.production_id=? 
                ORDER BY pu.created_at ASC"
            );
            $stmt->execute([$production_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Farmer can see only their own production’s updates
            $stmt = $this->pdo->prepare(
                "SELECT pu.*, p.crop_name 
                FROM production_updates pu 
                JOIN production p ON pu.production_id = p.id 
                WHERE pu.production_id=? AND p.farmer_id=? 
                ORDER BY pu.created_at ASC"
            );
            $stmt->execute([$production_id, $user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status'=>'success','data'=>$rows]);
    }

    // 5. List all production stage updates for a user (farmer or admin)
    public function listAllProductionUpdates($user) {

        // Admin – see all farmers’ updates
        if ($user['role_id'] == 1) {
            $stmt = $this->pdo->prepare(
                "SELECT pu.*, p.crop_name, p.farmer_id 
                FROM production_updates pu
                JOIN production p ON pu.production_id = p.id
                ORDER BY pu.created_at DESC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } else {
            // Farmer – see only their own updates
            $stmt = $this->pdo->prepare(
                "SELECT pu.*, p.crop_name 
                FROM production_updates pu
                JOIN production p ON pu.production_id = p.id
                WHERE p.farmer_id=?
                ORDER BY pu.created_at DESC"
            );
            $stmt->execute([$user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['status'=>'success','data'=>$rows]);
    }

}
