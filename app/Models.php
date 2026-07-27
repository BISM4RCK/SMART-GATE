<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
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
    public static function all(): array
    {
        return Database::pdo()->query("
            SELECT v.*, r.house_number, u.full_name
            FROM vehicles v
            JOIN residents r ON r.id = v.resident_id
            JOIN users u ON u.id = r.user_id
            ORDER BY v.created_at DESC
        ")->fetchAll();
    }
    public static function create(int $residentId, string $plate, string $type, string $color = ''): int
    {
        $stmt = Database::pdo()->prepare("INSERT INTO vehicles (resident_id, plate_number, vehicle_type, color, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$residentId, $plate, strtolower($type), $color ?: 'N/A']);
        return (int)Database::pdo()->lastInsertId();
    }
    public static function findByPlate(string $plate): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT v.*, r.id AS resident_id, r.user_id AS resident_user_id, r.house_number, u.full_name, rt.uid AS rfid_uid
            FROM vehicles v
            JOIN residents r ON r.id = v.resident_id
            JOIN users u ON u.id = r.user_id
            LEFT JOIN rfid_tags rt ON rt.vehicle_id = v.id
            WHERE v.plate_number = ?
            LIMIT 1
        ");
        $stmt->execute([$plate]);
        return $stmt->fetch() ?: null;
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
            (resident_id, house_number, visitor_name, contact_number, plate_number, vehicle_type, purpose_of_visit, status, qr_reference, requested_visit_date, requested_arrival_time)
            VALUES
            (:resident_id, :house_number, :visitor_name, :contact_number, :plate_number, :vehicle_type, :purpose_of_visit, 'pending', :qr_reference, :requested_visit_date, :requested_arrival_time)
        ");
        $stmt->execute([
            ':resident_id' => $data['resident_id'],
            ':house_number' => $data['house_number'],
            ':visitor_name' => $data['visitor_name'],
            ':contact_number' => $data['contact_number'] ?: null,
            ':plate_number' => $data['plate_number'],
            ':vehicle_type' => strtolower($data['vehicle_type']),
            ':purpose_of_visit' => $data['purpose_of_visit'],
            ':qr_reference' => $data['qr_reference'],
            ':requested_visit_date' => $data['requested_visit_date'] ?? null,
            ':requested_arrival_time' => $data['requested_arrival_time'] ?? null,
        ]);
        return (int)Database::pdo()->lastInsertId();
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
        $pdo = Database::pdo();
        $rfid = trim((string)($payload['rfid_uid'] ?? ''));
        $plate = trim((string)($payload['plate_number'] ?? ''));
        $event = strtolower((string)($payload['event_type'] ?? 'rfid_scan'));
        $source = trim((string)($payload['source_device'] ?? 'esp32'));
        $manual = bool_from_input($payload['manual_override'] ?? '0');
        $rawPayload = isset($payload['raw_payload']) ? json_encode($payload['raw_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $matched = null;
        if ($rfid !== '') $matched = VehicleModel::findByRfid($rfid);
        if (!$matched && $plate !== '') $matched = VehicleModel::findByPlate($plate);

        $residentId = $matched ? (int)$matched['resident_id'] : null;
        $residentUserId = $matched ? (int)$matched['resident_user_id'] : null;
        $vehicleId = $matched['id'] ?? null;
        $gateStatus = $matched ? 'approved' : 'denied';
        $notes = $matched ? 'Matched ' . ($rfid !== '' ? 'RFID' : 'plate') : 'No matching resident vehicle found';

        if ($manual) {
            $gateStatus = 'manual_override';
            $notes = 'Manual override from guard/admin';
        }

        $platePhoto = '';
        $vehiclePhoto = '';
        if (!empty($payload['plate_photo']) && is_array($payload['plate_photo'])) {
            $platePhoto = store_upload($payload['plate_photo'], 'gate');
        }
        if (!empty($payload['vehicle_photo']) && is_array($payload['vehicle_photo'])) {
            $vehiclePhoto = store_upload($payload['vehicle_photo'], 'gate');
        }

        $guardId = !empty($payload['guard_id']) ? (int)$payload['guard_id'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO gate_logs
            (resident_id, vehicle_id, guard_id, rfid_uid, plate_number, event_type, gate_status, source_device, plate_photo_path, vehicle_photo_path, raw_payload, log_notes)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $residentId,
            $vehicleId,
            $guardId,
            $rfid ?: null,
            $plate ?: null,
            $event,
            $gateStatus,
            $source,
            $platePhoto ?: null,
            $vehiclePhoto ?: null,
            $rawPayload,
            $notes,
        ]);

        if ($matched && $residentUserId) {
            NotificationModel::create($residentUserId, 'Gate activity', 'Your vehicle was ' . $gateStatus . ' at the gate.');
        }

        return ['ok' => true, 'gate_status' => $gateStatus, 'matched' => (bool)$matched, 'notes' => $notes, 'log_id' => (int)$pdo->lastInsertId()];
    }
    public static function all(): array
    {
        return Database::pdo()->query("SELECT * FROM gate_logs ORDER BY created_at DESC")->fetchAll();
    }
}
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
