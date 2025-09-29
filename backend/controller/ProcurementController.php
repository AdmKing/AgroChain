<?php
// backend/controller/ProcurementController.php
require_once __DIR__ . '/../helpers/auth.php';

class ProcurementController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. Procurement officer creates a procurement record
    public function createProcurement($officer) {
        $data = json_decode(file_get_contents("php://input"), true);

        $production_id = $data['production_id'] ?? '';
        $notes         = $data['notes'] ?? '';

        if (empty($production_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'production_id is required']);
            return;
        }

        // Check production exists
        $stmtCheck = $this->pdo->prepare("SELECT * FROM production WHERE id=?");
        $stmtCheck->execute([$production_id]);
        $production = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$production) {
            http_response_code(404);
            echo json_encode(['status'=>'error','message'=>'Production not found']);
            return;
        }

        // Insert procurement record
        $stmt = $this->pdo->prepare("INSERT INTO procurement (production_id, officer_id, notes) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$production_id, $officer['id'], $notes]);
            echo json_encode(['status'=>'success','message'=>'Procurement record created']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
        }
    }

    // 2. Update procurement status (approved/rejected/delivered)
    public function updateProcurementStatus($officer, $id) {
        $data = json_decode(file_get_contents("php://input"), true);
        $status = $data['status'] ?? '';

        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Status is required']);
            return;
        }

        // check if procurement exists
        $stmtCheck = $this->pdo->prepare("SELECT * FROM procurement WHERE id=?");
        $stmtCheck->execute([$id]);
        $record = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            echo json_encode(['status'=>'error','message'=>'Procurement record not found']);
            return;
        }

        // Admin or same officer can update
        if ($officer['role_id'] == 1 || $record['officer_id'] == $officer['id']) {
            $stmt = $this->pdo->prepare("UPDATE procurement SET status=? WHERE id=?");
            $stmt->execute([$status, $id]);
            echo json_encode(['status'=>'success','message'=>'Status updated']);
        } else {
            http_response_code(403);
            echo json_encode(['status'=>'error','message'=>'Not authorized']);
        }
    }

    // 3. List procurements
    public function listProcurements($user) {
        if ($user['role_id'] == 1) {
            // Admin sees all
            $stmt = $this->pdo->query("SELECT * FROM procurement");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($user['role_id'] == 3) {
            // Officer sees theirs
            $stmt = $this->pdo->prepare("SELECT * FROM procurement WHERE officer_id=?");
            $stmt->execute([$user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($user['role_id'] == 2) {
            // Farmer sees only procurements linked to their production
            $stmt = $this->pdo->prepare("
                SELECT pr.* FROM procurement pr
                JOIN production p ON pr.production_id = p.id
                WHERE p.farmer_id=?
            ");
            $stmt->execute([$user['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $rows = [];
        }

        echo json_encode(['status'=>'success','data'=>$rows]);
    }
}
