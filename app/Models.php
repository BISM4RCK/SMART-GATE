<?php
/* BISM4RCK-KUN3H0 2026 */


class AccountActivityLogModel
{
    public static function record(?int $userId, string $accountType, ?string $accountIdentifier, string $action, string $details = ''): void
    {
        try { $stmt=Database::pdo()->prepare("INSERT INTO account_activity_logs (user_id,account_type,account_identifier,action,details,ip_address,user_agent) VALUES (?,?,?,?,?,?,?)"); $stmt->execute([$userId,$accountType,$accountIdentifier,$action,$details?:null,$_SERVER['REMOTE_ADDR']??null,$_SERVER['HTTP_USER_AGENT']??null]); } catch(Throwable $e) {}
    }
    public static function filtered(array $filters=[], int $limit=200): array
    {
        $where=[];$params=[]; if(($filters['account_type']??'')!==''){ $where[]='aal.account_type=?';$params[]=$filters['account_type']; } if(($filters['user_id']??'')!==''){ $where[]='aal.user_id=?';$params[]=(int)$filters['user_id']; } if(($filters['action']??'')!==''){ $where[]='aal.action=?';$params[]=$filters['action']; }
        $sql='SELECT aal.*,u.full_name AS user_name,u.email AS user_email,u.role AS user_role FROM account_activity_logs aal LEFT JOIN users u ON u.id=aal.user_id'; if($where)$sql.=' WHERE '.implode(' AND ',$where); $limit=max(1,min(500,$limit)); $sql.=" ORDER BY aal.created_at DESC LIMIT {$limit}"; $stmt=Database::pdo()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll();
    }
}

class VisitorCredentialModel
{
    public static function create(int $requestId): array
    {
        $pdo=Database::pdo();
        do { $alphabet='ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $visitorId=''; for($i=0;$i<6;$i++) $visitorId.=$alphabet[random_int(0,strlen($alphabet)-1)]; } while(self::findByVisitorId($visitorId));
        $qr='GHQR-'.$visitorId.'-'.bin2hex(random_bytes(8)); $bc='GHBC-'.$visitorId.'-'.bin2hex(random_bytes(8));
        $s=$pdo->prepare('INSERT INTO visitor_credentials (visitor_request_id,visitor_id,qr_token_hash,barcode_token_hash,qr_token,barcode_token) VALUES (?,?,?,?,?,?)'); $s->execute([$requestId,$visitorId,hash('sha256',$qr),hash('sha256',$bc),$qr,$bc]); return ['visitor_id'=>$visitorId,'qr_token'=>$qr,'barcode_token'=>$bc];
    }
    public static function forRequest(int $requestId): ?array { $s=Database::pdo()->prepare('SELECT * FROM visitor_credentials WHERE visitor_request_id=? LIMIT 1');$s->execute([$requestId]);return $s->fetch()?:null; }
    public static function findByVisitorId(string $id): ?array { $s=Database::pdo()->prepare('SELECT vc.*,vr.status,vr.visitor_name,vr.house_number,vr.purpose_of_visit,vr.requested_visit_date,vr.rejection_reason FROM visitor_credentials vc JOIN visitor_requests vr ON vr.id=vc.visitor_request_id WHERE vc.visitor_id=? LIMIT 1');$s->execute([strtoupper(trim($id))]);return $s->fetch()?:null; }
    public static function findByToken(string $token,string $type='qr'): ?array { $token=trim($token); if($type==='qr' && preg_match('/[?&]id=([A-Za-z0-9]{6})/',$token,$m)) return self::findByVisitorId($m[1]); $column=$type==='barcode'?'barcode_token_hash':'qr_token_hash';$s=Database::pdo()->prepare("SELECT vc.*,vr.status,vr.visitor_name,vr.house_number,vr.purpose_of_visit,vr.rejection_reason FROM visitor_credentials vc JOIN visitor_requests vr ON vr.id=vc.visitor_request_id WHERE vc.{$column}=? LIMIT 1");$s->execute([hash('sha256',$token)]);return $s->fetch()?:null; }
}

class WalkInVisitorModel
{
    public static function create(array $data, int $createdBy): array
    {
        $pdo = Database::pdo();
        do {
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $visitorId = '';
            for ($i = 0; $i < 6; $i++) $visitorId .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        } while (self::findByVisitorId($visitorId));
        $barcode = 'GHWBC-' . $visitorId . '-' . bin2hex(random_bytes(8));
        $plates = $data['vehicle_plate'] ?? []; $types = $data['vehicle_type'] ?? []; $people = $data['vehicle_people'] ?? [];
        if (!is_array($plates)) $plates=[$plates]; if (!is_array($types)) $types=[$types]; if (!is_array($people)) $people=[$people];
        $vehicles=[]; foreach($plates as $i=>$plate){ $plate=strtoupper(trim($plate)); $type=strtolower(trim($types[$i]??'other')); $pc=max(1,(int)($people[$i]??1)); if($plate!=='') $vehicles[]=['plate'=>$plate,'type'=>$type,'people'=>$pc]; }
        $primary=$vehicles[0]??['plate'=>null,'type'=>'other'];
        $stmt = $pdo->prepare("INSERT INTO walk_in_visitors (visitor_id, visitor_name, contact_number, purpose_of_visit, plate_number, vehicle_type, barcode_token_hash, barcode_token, created_by, status) VALUES (?,?,?,?,?,?,?,?,?,'active')");
        $stmt->execute([$visitorId, trim($data['visitor_name']), trim($data['contact_number'] ?? '') ?: null, trim($data['purpose']), $primary['plate'], $primary['type'], hash('sha256', $barcode), $barcode, $createdBy]);
        $walkInId=(int)$pdo->lastInsertId(); foreach($vehicles as $v){$vs=$pdo->prepare('INSERT INTO walk_in_visitor_vehicles (walk_in_id,plate_number,vehicle_type,people_count) VALUES (?,?,?,?)');$vs->execute([$walkInId,$v['plate'],$v['type'],$v['people']]);}
        return self::findByVisitorId($visitorId) ?? [];
    }
    public static function findByVisitorId(string $id): ?array { $stmt=Database::pdo()->prepare('SELECT w.*,u.full_name AS created_by_name FROM walk_in_visitors w LEFT JOIN users u ON u.id=w.created_by WHERE w.visitor_id=? LIMIT 1'); $stmt->execute([strtoupper(trim($id))]); return $stmt->fetch()?:null; }
    public static function findByToken(string $token): ?array { $stmt=Database::pdo()->prepare('SELECT w.*,u.full_name AS created_by_name FROM walk_in_visitors w LEFT JOIN users u ON u.id=w.created_by WHERE w.barcode_token_hash=? LIMIT 1'); $stmt->execute([hash('sha256',trim($token))]); return $stmt->fetch()?:null; }
}

class UserVehicleModel
{
    public static function allStaff(array $filters=[]): array { $where=[];$params=[]; if(($filters['vehicle_type']??'')!==''){ $where[]='uv.vehicle_type=?';$params[]=$filters['vehicle_type']; } if(($filters['owner_id']??'')!==''){ $where[]='uv.user_id=?';$params[]=(int)$filters['owner_id']; } $sql="SELECT uv.*,u.full_name,u.email,u.role FROM user_vehicles uv JOIN users u ON u.id=uv.user_id"; if($where)$sql.=' WHERE '.implode(' AND ',$where); $sql.=' ORDER BY u.role,u.full_name,uv.created_at DESC'; $st=Database::pdo()->prepare($sql);$st->execute($params);return $st->fetchAll(); }
    public static function create(int $userId,string $plate,string $type,string $color=''): int { $s=Database::pdo()->prepare('INSERT INTO user_vehicles(user_id,plate_number,vehicle_type,color) VALUES(?,?,?,?)');$s->execute([$userId,strtoupper(trim($plate)),strtolower(trim($type)),$color?:'N/A']);return (int)Database::pdo()->lastInsertId(); }
    public static function delete(int $id): bool { $s=Database::pdo()->prepare('DELETE FROM user_vehicles WHERE id=?');$s->execute([$id]);return $s->rowCount()>0; }
    public static function findByPlate(string $plate): ?array { $s=Database::pdo()->prepare("SELECT uv.*,u.full_name,u.email,u.role FROM user_vehicles uv JOIN users u ON u.id=uv.user_id WHERE uv.plate_number=? AND u.status='active' LIMIT 1");$s->execute([strtoupper(trim($plate))]);return $s->fetch()?:null; }
}

class GateCommandModel
{
    public static function create(int $userId,string $role,string $command,string $source,array $payload=[]): int { $s=Database::pdo()->prepare("INSERT INTO gate_commands(issued_by,issued_by_role,command,source,payload,status,completed_at) VALUES(?,?,?,?,?,'completed',NOW())");$s->execute([$userId,$role,$command,$source,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);return (int)Database::pdo()->lastInsertId(); }
}

class BlacklistModel
{
    public static function all(): array
    {
        return Database::pdo()->query("SELECT b.*, u.full_name AS created_by_name FROM blacklist b LEFT JOIN users u ON u.id = b.created_by ORDER BY b.created_at DESC")->fetchAll();
    }

    public static function add(array $data, int $createdBy): int
    {
        $stmt = Database::pdo()->prepare("INSERT INTO blacklist (resident_id, visitor_name, plate_number, reason, status, start_date, end_date, created_by) VALUES (?, ?, ?, ?, 'active', ?, ?, ?)");
        $stmt->execute([
            !empty($data['resident_id']) ? (int)$data['resident_id'] : null,
            trim($data['visitor_name'] ?? '') ?: null,
            strtoupper(trim($data['plate_number'] ?? '')) ?: null,
            trim($data['reason'] ?? ''),
            !empty($data['start_date']) ? $data['start_date'] : null,
            !empty($data['end_date']) ? $data['end_date'] : null,
            $createdBy,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function remove(int $id): bool
    {
        $stmt = Database::pdo()->prepare("DELETE FROM blacklist WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function setStatus(int $id, string $status): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE blacklist SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function isActivePlate(string $plate): bool
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) c FROM blacklist WHERE plate_number = ? AND status = 'active' AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())");
        $stmt->execute([strtoupper(trim($plate))]);
        return (int)($stmt->fetch()['c'] ?? 0) > 0;
    }
}

class UserModel
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public static function byRole(string $role): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE role = ? ORDER BY full_name ASC");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
    public static function all(): array
    {
        return Database::pdo()->query("SELECT * FROM users ORDER BY full_name ASC")->fetchAll();
    }
    public static function touchLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }
    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$data['full_name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT), $data['role']]);
        return (int)$pdo->lastInsertId();
    }
    public static function updatePassword(int $id, string $password): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET password = ? WHERE id = ?');
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

class ResidentModel
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT r.*, u.full_name, u.email
            FROM residents r
            JOIN users u ON u.id = r.user_id
            WHERE r.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }
    public static function findByHouse(string $house): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT r.*, u.full_name, u.email
            FROM residents r
            JOIN users u ON u.id = r.user_id
            WHERE r.house_number = ?
            LIMIT 1
        ");
        $stmt->execute([$house]);
        return $stmt->fetch() ?: null;
    }
    public static function all(): array
    {
        return Database::pdo()->query("
            SELECT r.*, u.full_name, u.email
            FROM residents r
            JOIN users u ON u.id = r.user_id
            ORDER BY r.house_number ASC
        ")->fetchAll();
    }
}

class VehicleModel
{
    public static function forResident(int $residentId): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM vehicles WHERE resident_id = ? ORDER BY created_at DESC");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }
    public static function all(array $filters = []): array
    {
        $where=[]; $params=[];
        if (($filters['vehicle_type'] ?? '') !== '') { $where[]='v.vehicle_type=?'; $params[]=$filters['vehicle_type']; }
        if (($filters['owner_id'] ?? '') !== '') { $where[]='r.user_id=?'; $params[]=(int)$filters['owner_id']; }
        if (($filters['search'] ?? '') !== '') { $where[]='(v.plate_number LIKE ? OR u.full_name LIKE ? OR r.house_number LIKE ?)'; $term='%'.$filters['search'].'%'; array_push($params,$term,$term,$term); }
        $sql="SELECT v.*,r.house_number,u.full_name,u.id AS owner_id,'resident' AS owner_group FROM vehicles v JOIN residents r ON r.id=v.resident_id JOIN users u ON u.id=r.user_id";
        if($where)$sql.=' WHERE '.implode(' AND ',$where);
        $sql.=' ORDER BY v.created_at DESC'; $st=Database::pdo()->prepare($sql); $st->execute($params); return $st->fetchAll();
    }
    public static function create(int $residentId, string $plate, string $type, string $color = ''): int
    {
        $stmt = Database::pdo()->prepare("INSERT INTO vehicles (resident_id, plate_number, vehicle_type, color, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$residentId, $plate, strtolower($type), $color ?: 'N/A']);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function findByPlate(string $plate): ?array
    {
        $stmt=Database::pdo()->prepare("SELECT v.*,r.id AS resident_id,r.user_id AS resident_user_id,r.house_number,u.full_name,rt.uid AS rfid_uid,'resident' AS owner_role FROM vehicles v JOIN residents r ON r.id=v.resident_id JOIN users u ON u.id=r.user_id LEFT JOIN rfid_tags rt ON rt.vehicle_id=v.id WHERE v.plate_number=? LIMIT 1");
        $stmt->execute([$plate]); $resident=$stmt->fetch(); if($resident)return $resident; $staff=UserVehicleModel::findByPlate($plate); return $staff?array_merge($staff,['id'=>$staff['id'],'resident_id'=>null,'resident_user_id'=>$staff['user_id'],'house_number'=>'STAFF','rfid_uid'=>null,'owner_role'=>$staff['role'],'staff_vehicle'=>true]):null;
    }
    public static function delete(int $vehicleId, ?int $residentId = null): bool
    {
        if ($residentId !== null) {
            $stmt = Database::pdo()->prepare("DELETE FROM vehicles WHERE id = ? AND resident_id = ?");
            $stmt->execute([$vehicleId, $residentId]);
            return $stmt->rowCount() > 0;
        }
        $stmt = Database::pdo()->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        return $stmt->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM vehicles WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function update(int $id, string $plate, string $type, string $color = '', string $brand = '', string $model = ''): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE vehicles SET plate_number = ?, vehicle_type = ?, color = ?, brand = ?, model = ? WHERE id = ?");
        return $stmt->execute([$plate, strtolower($type), $color ?: 'N/A', $brand ?: null, $model ?: null, $id]);
    }

    public static function findByRfid(string $rfid): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT v.*, r.id AS resident_id, r.user_id AS resident_user_id, r.house_number, u.full_name, rt.uid AS rfid_uid
            FROM rfid_tags rt
            JOIN vehicles v ON v.id = rt.vehicle_id
            JOIN residents r ON r.id = v.resident_id
            JOIN users u ON u.id = r.user_id
            WHERE rt.uid = ?
            LIMIT 1
        ");
        $stmt->execute([$rfid]);
        return $stmt->fetch() ?: null;
    }
}

class NotificationModel
{
    public static function create(int $userId, string $title, string $message): int
    {
        $stmt = Database::pdo()->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $title, $message]);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function unreadCount(int $userId): int
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)($stmt->fetch()['c'] ?? 0);
    }
    public static function latest(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::pdo()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public static function all(int $userId): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public static function markRead(int $id, int $userId): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }
    public static function markAllRead(int $userId): bool
    {
        $stmt = Database::pdo()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        return $stmt->execute([$userId]);
    }


    /**
     * Delete a notification belonging to a user.
     * BISM4RCK/KUN3H0 2026
     */
    public static function delete(int $notificationId, int $userId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "DELETE FROM notifications WHERE id = :id AND user_id = :user_id"
        );
        $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId,
        ]);
        return $stmt->rowCount() > 0;
    }

}

class TicketModel
{
    public static function create(int $residentId, string $senderName, string $senderRole, string $houseNumber, string $subject, string $message): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO concerns (resident_id, sender_name, sender_role, house_number, subject, message, status)
            VALUES (?, ?, ?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$residentId ?: null, $senderName, $senderRole, $houseNumber ?: null, $subject, $message]);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function all(): array
    {
        return Database::pdo()->query("
            SELECT c.*, u.full_name AS replied_by_name
            FROM concerns c
            LEFT JOIN users u ON u.id = c.replied_by
            ORDER BY c.created_at DESC
        ")->fetchAll();
    }
    public static function forResident(int $residentId): array
    {
        $stmt = Database::pdo()->prepare("
            SELECT c.*, u.full_name AS replied_by_name
            FROM concerns c
            LEFT JOIN users u ON u.id = c.replied_by
            WHERE c.resident_id = ? OR c.sender_role = 'resident'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }
    public static function reply(int $ticketId, int $adminId, string $reply): bool
    {
        $stmt = Database::pdo()->prepare("
            UPDATE concerns SET reply = ?, status = 'closed', replied_by = ?, replied_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$reply, $adminId, $ticketId]);
    }
    public static function delete(int $ticketId): bool
    {
        $stmt = Database::pdo()->prepare("DELETE FROM concerns WHERE id = ?");
        $stmt->execute([$ticketId]);
        return $stmt->rowCount() > 0;
    }

    public static function openCount(): int
    {
        return (int)(Database::pdo()->query("SELECT COUNT(*) c FROM concerns WHERE status = 'open'")->fetch()['c'] ?? 0);
    }
}

class VisitorRequestModel
{
    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO visitor_requests
            (resident_id, house_number, visitor_name, contact_number, plate_number, vehicle_type, purpose_of_visit, people_count, id_not_available, status, qr_reference, requested_visit_date, requested_arrival_time)
            VALUES
            (:resident_id, :house_number, :visitor_name, :contact_number, :plate_number, :vehicle_type, :purpose_of_visit, :people_count, :id_not_available, 'pending', :qr_reference, :requested_visit_date, :requested_arrival_time)
        ");
        $stmt->execute([
            ':resident_id' => $data['resident_id'],
            ':house_number' => $data['house_number'],
            ':visitor_name' => $data['visitor_name'],
            ':contact_number' => $data['contact_number'] ?: null,
            ':plate_number' => $data['plate_number'],
            ':vehicle_type' => strtolower($data['vehicle_type']),
            ':purpose_of_visit' => $data['purpose_of_visit'],
            ':people_count' => max(1,(int)($data['people_count'] ?? 1)),
            ':id_not_available' => !empty($data['id_not_available']) ? 1 : 0,
            ':qr_reference' => $data['qr_reference'],
            ':requested_visit_date' => $data['requested_visit_date'] ?? null,
            ':requested_arrival_time' => $data['requested_arrival_time'] ?? null,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function addVehicle(int $requestId, string $plate, string $type, int $people): int
    {
        $stmt = Database::pdo()->prepare("INSERT INTO visitor_request_vehicles (visitor_request_id,plate_number,vehicle_type,people_count) VALUES (?,?,?,?)");
        $stmt->execute([$requestId,strtoupper(trim($plate)),strtolower(trim($type)),max(1,$people)]);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function vehicles(int $requestId): array
    {
        $stmt=Database::pdo()->prepare("SELECT * FROM visitor_request_vehicles WHERE visitor_request_id=? ORDER BY id");
        $stmt->execute([$requestId]); return $stmt->fetchAll();
    }
    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM visitor_requests WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public static function findByReference(string $ref): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM visitor_requests WHERE qr_reference = ? LIMIT 1");
        $stmt->execute([$ref]);
        return $stmt->fetch() ?: null;
    }
    public static function forResident(int $residentId): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM visitor_requests WHERE resident_id = ? ORDER BY created_at DESC");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }
    public static function pendingForResident(int $residentId): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM visitor_requests WHERE resident_id = ? AND status = 'pending' ORDER BY created_at DESC");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }
    public static function all(): array
    {
        return Database::pdo()->query("
            SELECT vr.*, u.full_name AS resident_name
            FROM visitor_requests vr
            JOIN residents r ON r.id = vr.resident_id
            JOIN users u ON u.id = r.user_id
            ORDER BY vr.created_at DESC
        ")->fetchAll();
    }
    public static function updateStatus(int $id, string $status, int $userId, string $reason = ''): bool
    {
        $status = strtolower($status);
        $req = self::findById($id);
        if (!$req) return false;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($status === 'approved') {
                $stmt = $pdo->prepare("UPDATE visitor_requests SET status = 'approved', approved_by = ?, approved_at = NOW(), rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$userId, $id]);
                $bookingCode = 'BK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = $pdo->prepare("INSERT INTO bookings (visitor_request_id, booking_code, qr_code_text, status) VALUES (?, ?, ?, 'active')");
                $stmt->execute([$id, $bookingCode, $req['qr_reference']]);
                $resident = ResidentModel::findByHouse($req['house_number']);
                if ($resident) {
                    NotificationModel::create((int)$resident['user_id'], 'Visitor approved', 'Your visitor request for House ' . $req['house_number'] . ' was approved.');
                }
            } elseif ($status === 'rejected') {
                $stmt = $pdo->prepare("UPDATE visitor_requests SET status = 'rejected', rejected_by = ?, rejected_at = NOW(), rejection_reason = ? WHERE id = ?");
                $stmt->execute([$userId, $reason ?: 'Rejected by resident', $id]);
                $resident = ResidentModel::findByHouse($req['house_number']);
                if ($resident) {
                    NotificationModel::create((int)$resident['user_id'], 'Visitor rejected', 'Your visitor request for House ' . $req['house_number'] . ' was rejected.');
                }
            } else {
                $stmt = $pdo->prepare("UPDATE visitor_requests SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
            }
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

class GateLogModel
{
    public static function recent(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        return Database::pdo()->query("SELECT * FROM gate_logs ORDER BY created_at DESC LIMIT {$limit}")->fetchAll();
    }
    public static function forResident(int $residentId): array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM gate_logs WHERE resident_id = ? ORDER BY created_at DESC");
        $stmt->execute([$residentId]);
        return $stmt->fetchAll();
    }
    public static function createAccess(array $payload): array
    {
        $pdo=Database::pdo(); $rfid=trim((string)($payload['rfid_uid']??'')); $qr=trim((string)($payload['qr_token']??'')); $barcode=trim((string)($payload['barcode_token']??''));
        $event=strtolower((string)($payload['event_type']??(($qr||$barcode)?'qr_scan':'rfid_scan'))); $source=trim((string)($payload['source_device']??'guard-panel')); $manual=bool_from_input($payload['manual_override']??'0');
        $matched=null;$visitor=null;$walkIn=null; if($qr!=='')$visitor=VisitorCredentialModel::findByToken($qr,'qr'); if(!$visitor&&$barcode!==''){$visitor=VisitorCredentialModel::findByToken($barcode,'barcode');if(!$visitor)$walkIn=WalkInVisitorModel::findByToken($barcode);} if(!$walkIn&&!empty($payload['visitor_id']))$walkIn=WalkInVisitorModel::findByVisitorId((string)$payload['visitor_id']); if($rfid!=='')$matched=VehicleModel::findByRfid($rfid);
        $plate=!empty($payload['plate_number'])?trim((string)$payload['plate_number']):($matched['plate_number']??($walkIn['plate_number']??null)); if(!$matched&&$plate) $matched=VehicleModel::findByPlate($plate);
        $residentId=$matched['resident_id']??null;$vehicleId=($matched&&!empty($matched['resident_id']))?$matched['id']:null;$visitorRequestId=$visitor['visitor_request_id']??null;$walkInId=$walkIn['id']??null;
        if($visitor){$gateStatus=match($visitor['status']){'approved'=>'approved','pending'=>'pending','rejected'=>'denied',default=>'denied'};$notes='Visitor credential '.$visitor['visitor_id'].' is '.$visitor['status'];$event=$qr?'qr_scan':'barcode_scan';}
        elseif($walkIn){$gateStatus='approved';$notes='Walk-in visitor '.$walkIn['visitor_id'].' checked in';$event='walk_in_checkin';}
        else {$blacklisted=$plate?BlacklistModel::isActivePlate($plate):false;$gateStatus=$blacklisted?'denied':($matched?'approved':'denied');$notes=$blacklisted?'Plate is on the active blacklist':($matched?'Matched '.($rfid?'RFID':'vehicle'):'No matching RFID/QR credential found');}
        if($manual){$gateStatus='manual_override';$notes='Manual gate override by authorized account';}
        $guardId=!empty($payload['guard_id'])?(int)$payload['guard_id']:null; $actorUserId=!empty($payload['actor_user_id'])?(int)$payload['actor_user_id']:($guardId?:null); $actorRole=trim((string)($payload['actor_role']??''))?:null; $raw=isset($payload['raw_payload'])?json_encode($payload['raw_payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null;
        $s=$pdo->prepare('INSERT INTO gate_logs(resident_id,vehicle_id,visitor_request_id,walk_in_id,guard_id,actor_user_id,actor_role,rfid_uid,plate_number,event_type,gate_status,source_device,raw_payload,log_notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)'); $s->execute([$residentId,$vehicleId,$visitorRequestId,$walkInId,$guardId,$actorUserId,$actorRole,$rfid?:null,$plate?:null,$event,$gateStatus,$source,$raw,$notes]);
        return ['ok'=>true,'gate_status'=>$gateStatus,'matched'=>(bool)($matched||$visitor||$walkIn),'notes'=>$notes,'log_id'=>(int)$pdo->lastInsertId(),'visitor'=>$visitor,'walk_in'=>$walkIn];
    }
    public static function all(array $filters=[]): array { $where=[];$params=[]; if(($filters['event_type']??'')!==''){ $where[]='gl.event_type=?';$params[]=$filters['event_type']; } if(($filters['gate_status']??'')!==''){ $where[]='gl.gate_status=?';$params[]=$filters['gate_status']; } if(($filters['actor_user_id']??'')!==''){ $where[]='gl.actor_user_id=?';$params[]=(int)$filters['actor_user_id']; } if(($filters['search']??'')!==''){ $where[]='(gl.plate_number LIKE ? OR gl.rfid_uid LIKE ? OR gl.log_notes LIKE ?)';$term='%'.$filters['search'].'%';array_push($params,$term,$term,$term); } $sql='SELECT gl.*,u.full_name AS actor_name,u.role AS actor_role FROM gate_logs gl LEFT JOIN users u ON u.id=gl.actor_user_id';if($where)$sql.=' WHERE '.implode(' AND ',$where);$sql.=' ORDER BY gl.created_at DESC LIMIT 500';$st=Database::pdo()->prepare($sql);$st->execute($params);return $st->fetchAll(); }
}


class UiSettingsModel
{
    public static function all(): array
    {
        try {
            $rows = Database::pdo()->query("SELECT setting_key, bg_color, text_color, width_px AS width, height_px AS height, radius_px AS radius FROM ui_settings")->fetchAll();
        } catch (Throwable $e) { return []; }
        $out = [];
        foreach ($rows as $row) $out[$row['setting_key']] = $row;
        return $out;
    }

    public static function save(string $key, array $data, int $userId): void
    {
        $allowed = ['landing_login','landing_visitor','guard_walkin','guard_logs','guard_blacklist','admin_override','admin_users','admin_vehicles','admin_logs','dashboard_background'];
        if (!in_array($key, $allowed, true)) throw new RuntimeException('Invalid customization target.');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO ui_settings (setting_key,bg_color,text_color,width_px,height_px,radius_px,updated_by)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE bg_color=VALUES(bg_color), text_color=VALUES(text_color),
            width_px=VALUES(width_px), height_px=VALUES(height_px), radius_px=VALUES(radius_px), updated_by=VALUES(updated_by)
        ");
        $stmt->execute([
            $key,
            preg_match('/^#[0-9a-fA-F]{6}$/', $data['bg_color'] ?? '') ? $data['bg_color'] : null,
            preg_match('/^#[0-9a-fA-F]{6}$/', $data['text_color'] ?? '') ? $data['text_color'] : null,
            max(80,min(800,(int)($data['width'] ?? 0))) ?: null,
            max(40,min(600,(int)($data['height'] ?? 0))) ?: null,
            max(0,min(80,(int)($data['radius'] ?? 0))) ?: null,
            $userId
        ]);
    }
}


?>
