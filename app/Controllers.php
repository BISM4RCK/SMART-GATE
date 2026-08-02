<?php
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
class HomeController
{
    public function index(): void
    {
        View::render('home', ['pageTitle' => 'Home']);
    }
}

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('dashboard.php');
        }
        View::render('auth/login', ['pageTitle' => 'Login', 'error' => null]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('login.php');
        }

        $user = Auth::login(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''));
        if (!$user) {
            View::render('auth/login', ['pageTitle' => 'Login', 'error' => 'Invalid email or password.']);
            return;
        }

        flash_set('success', 'Welcome back, ' . $user['name'] . '.');
        redirect(dashboard_url($user));
    }

    public function logout(): void
    {
        Auth::logout();
        flash_set('success', 'Logged out.');
        redirect('index.php');
    }
}

class VisitorController
{
    public function form(): void
    {
        View::render('visitor/register', ['pageTitle' => 'Visitor Request']);
    }

    public function submit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('visitor/register.php');
        }

        $house = trim($_POST['house_number'] ?? '');
        $name = trim($_POST['visitor_name'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $plate = trim($_POST['plate_number'] ?? '');
        $type = trim($_POST['vehicle_type'] ?? '');
        $purpose = trim($_POST['purpose'] ?? '');
        $resident = ResidentModel::findByHouse($house);

        if (!$resident) {
            flash_set('danger', 'No resident was found for that house number.');
            redirect('visitor/register.php');
        }

        if ($house === '' || $name === '' || $plate === '' || $type === '' || $purpose === '') {
            flash_set('danger', 'Please complete all required fields.');
            redirect('visitor/register.php');
        }

        $qr = 'GH-' . strtoupper(bin2hex(random_bytes(4)));
        $requestId = VisitorRequestModel::create([
            'resident_id' => (int)$resident['id'],
            'house_number' => $house,
            'visitor_name' => $name,
            'contact_number' => $contact,
            'plate_number' => $plate,
            'vehicle_type' => $type,
            'purpose_of_visit' => $purpose,
            'qr_reference' => $qr,
            'requested_visit_date' => date('Y-m-d'),
            'requested_arrival_time' => date('H:i:s'),
        ]);

        $idFile = store_upload($_FILES['government_id'] ?? [], 'ids');
        if ($idFile) {
            $stmt = Database::pdo()->prepare("INSERT INTO visitor_attachments (visitor_request_id, file_type, file_path, original_filename, mime_type, file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");
            $stmt->execute([$requestId, $idFile, $_FILES['government_id']['name'] ?? '', $_FILES['government_id']['type'] ?? '', $_FILES['government_id']['size'] ?? null]);
        }

        NotificationModel::create((int)$resident['user_id'], 'New visitor request', 'A visitor request was submitted for House ' . $house . '.');
        flash_set('success', 'Request submitted and sent to the resident.');
        redirect('visitor/status.php?ref=' . urlencode($qr));
    }

    public function status(): void
    {
        $ref = trim($_GET['ref'] ?? '');
        $request = $ref ? VisitorRequestModel::findByReference($ref) : null;
        View::render('visitor/status', ['pageTitle' => 'Visitor Status', 'request' => $request, 'ref' => $ref]);
    }
}

class ResidentController
{
    private function resident(): array
    {
        $user = current_user();
        $resident = ResidentModel::findByUserId((int)$user['id']);
        if (!$resident) {
            flash_set('danger', 'Resident profile not found.');
            redirect('logout.php');
        }
        return $resident;
    }

    public function dashboard(): void
    {
        require_role('resident');
        $resident = $this->resident();
        View::render('resident/dashboard', [
            'pageTitle' => 'Resident Dashboard',
            'resident' => $resident,
            'stats' => [
                'pending' => count(VisitorRequestModel::pendingForResident((int)$resident['id'])),
                'vehicles' => count(VehicleModel::forResident((int)$resident['id'])),
                'tickets' => count(TicketModel::forResident((int)$resident['id'])),
                'logs' => count(GateLogModel::forResident((int)$resident['id'])),
            ],
            'requests' => array_slice(VisitorRequestModel::forResident((int)$resident['id']), 0, 5),
            'vehicles' => array_slice(VehicleModel::forResident((int)$resident['id']), 0, 5),
            'tickets' => array_slice(TicketModel::forResident((int)$resident['id']), 0, 5),
            'logs' => array_slice(GateLogModel::forResident((int)$resident['id']), 0, 5),
        ]);
    }

    public function requests(): void
    {
        require_role('resident');
        $resident = $this->resident();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['request_id'] ?? 0);
            $action = strtolower(trim($_POST['action'] ?? ''));
            $reason = trim($_POST['reason'] ?? '');
            if ($id && in_array($action, ['approved', 'rejected'], true)) {
                VisitorRequestModel::updateStatus($id, $action, (int)current_user()['id'], $reason);
                flash_set('success', 'Request updated.');
                redirect('resident/requests.php');
            }
        }

        View::render('resident/requests', [
            'pageTitle' => 'Visitor Requests',
            'resident' => $resident,
            'requests' => VisitorRequestModel::forResident((int)$resident['id']),
        ]);
    }

    public function vehicles(): void
    {
        require_role('resident');
        $resident = $this->resident();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate();
            $action = strtolower(trim($_POST['action'] ?? 'add'));
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            if ($action === 'delete' && $vehicleId) {
                if (VehicleModel::delete($vehicleId, (int)$resident['id'])) {
                    flash_set('success', 'Vehicle removed.');
                } else {
                    flash_set('danger', 'Vehicle could not be removed.');
                }
                redirect('resident/vehicles.php');
            }

            $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
            $type = trim($_POST['vehicle_type'] ?? '');
            $color = trim($_POST['color'] ?? '');
            if ($plate !== '' && $type !== '') {
                try {
                    VehicleModel::create((int)$resident['id'], $plate, $type, $color);
                    NotificationModel::create((int)current_user()['id'], 'Vehicle added', 'Vehicle ' . $plate . ' was added to your account.');
                    flash_set('success', 'Vehicle saved.');
                    redirect('resident/vehicles.php');
                } catch (Throwable $e) {
                    flash_set('danger', 'Vehicle could not be saved. Plate may already exist.');
                }
            } else {
                flash_set('danger', 'Please fill in the required fields.');
            }
        }

        View::render('resident/vehicles', [
            'pageTitle' => 'Vehicles',
            'resident' => $resident,
            'vehicles' => VehicleModel::forResident((int)$resident['id']),
        ]);
    }

    public function tickets(): void
    {
        require_role('resident');
        $resident = $this->resident();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if ($subject !== '' && $message !== '') {
                TicketModel::create((int)$resident['id'], current_user()['name'], 'resident', current_user()['house'] ?? $resident['house_number'], $subject, $message);
                $admin = UserModel::byRole('admin')[0] ?? null;
                if ($admin) {
                    NotificationModel::create((int)$admin['id'], 'New trouble ticket', $subject);
                }
                NotificationModel::create((int)current_user()['id'], 'Ticket created', 'Your ticket was sent to the admin.');
                flash_set('success', 'Ticket created.');
                redirect('resident/tickets.php');
            }
            flash_set('danger', 'Please fill in the ticket fields.');
        }

        View::render('resident/tickets', [
            'pageTitle' => 'Tickets',
            'resident' => $resident,
            'tickets' => TicketModel::forResident((int)$resident['id']),
        ]);
    }
}

class GuardController
{
    public function dashboard(): void
    {
        require_role('guard');
        View::render('guard/dashboard', [
            'pageTitle' => 'Guard Dashboard',
            'stats' => [
                'pending' => count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE status = 'pending'"),
                'logs' => count_rows("SELECT COUNT(*) c FROM gate_logs"),
                'tickets' => TicketModel::openCount(),
                'vehicles' => count_rows("SELECT COUNT(*) c FROM vehicles"),
            ],
            'requests' => array_slice(VisitorRequestModel::all(), 0, 6),
            'logs' => array_slice(GateLogModel::recent(8), 0, 8),
        ]);
    }

    public function logs(): void
    {
        require_role('guard');
        View::render('guard/logs', ['pageTitle' => 'Logs', 'logs' => GateLogModel::recent(50)]);
    }

    public function scan(): void
    {
        require_role('guard');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = GateLogModel::createAccess([
                'plate_number' => trim($_POST['plate_number'] ?? ''),
                'rfid_uid' => trim($_POST['rfid_uid'] ?? ''),
                'event_type' => trim($_POST['event_type'] ?? 'manual_open'),
                'source_device' => 'manual-guard',
                'manual_override' => bool_from_input($_POST['manual_override'] ?? '0'),
                'guard_id' => (int)current_user()['id'],
                'raw_payload' => $_POST,
            ]);
            flash_set($result['gate_status'] === 'approved' ? 'success' : 'warning', $result['notes']);
            redirect('guard/scan.php');
        }

        View::render('guard/scan', [
            'pageTitle' => 'Quick Scan',
            'logs' => GateLogModel::recent(10),
            'requests' => VisitorRequestModel::all(),
        ]);
    }

    public function blacklist(): void
    {
        require_role('guard');
        $u = current_user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate();
            $action = strtolower(trim($_POST['action'] ?? ''));
            try {
                if ($action === 'add') {
                    $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
                    $reason = trim($_POST['reason'] ?? '');
                    if ($reason === '' || ($plate === '' && trim($_POST['visitor_name'] ?? '') === '')) throw new RuntimeException('Provide a plate number or visitor name and a reason.');
                    BlacklistModel::add($_POST, (int)$u['id']);
                    flash_set('success', 'Blacklist entry added.');
                } elseif ($action === 'remove') {
                    if (!BlacklistModel::remove((int)($_POST['blacklist_id'] ?? 0))) throw new RuntimeException('Blacklist entry not found.');
                    flash_set('success', 'Blacklist entry removed.');
                }
            } catch (Throwable $e) { flash_set('danger', $e->getMessage()); }
            redirect('guard/blacklist.php');
        }
        View::render('guard/blacklist', ['pageTitle'=>'Vehicle Blacklist', 'blacklist'=>BlacklistModel::all()]);
    }
}

class AdminController
{
    public function dashboard(): void
    {
        require_role('admin');
        View::render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats' => [
                'residents' => count_rows("SELECT COUNT(*) c FROM residents"),
                'requests' => count_rows("SELECT COUNT(*) c FROM visitor_requests"),
                'tickets' => TicketModel::openCount(),
                'logs' => count_rows("SELECT COUNT(*) c FROM gate_logs"),
            ],
            'tickets' => array_slice(TicketModel::all(), 0, 5),
            'logs' => array_slice(GateLogModel::recent(8), 0, 8),
        ]);
    }

    public function tickets(): void
    {
        require_role('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate();
            $action = strtolower(trim($_POST['action'] ?? 'reply'));
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
            if ($action === 'delete' && $ticketId) {
                if (TicketModel::delete($ticketId)) {
                    flash_set('success', 'Ticket deleted.');
                } else {
                    flash_set('danger', 'Ticket could not be deleted.');
                }
                redirect('admin/tickets.php');
            }
            $reply = trim($_POST['reply'] ?? '');
            if ($ticketId && $reply !== '') {
                TicketModel::reply($ticketId, (int)current_user()['id'], $reply);
                $stmt = Database::pdo()->prepare("SELECT resident_id FROM concerns WHERE id = ?");
                $stmt->execute([$ticketId]);
                $row = $stmt->fetch();
                if (!empty($row['resident_id'])) {
                    $stmt2 = Database::pdo()->prepare("SELECT user_id FROM residents WHERE id = ?");
                    $stmt2->execute([(int)$row['resident_id']]);
                    $u = $stmt2->fetch();
                    if (!empty($u['user_id'])) {
                        NotificationModel::create((int)$u['user_id'], 'Ticket replied', 'Your ticket has a reply from admin.');
                    }
                }
                flash_set('success', 'Reply saved.');
                redirect('admin/tickets.php');
            }
            flash_set('danger', 'Write a reply first.');
        }

        View::render('admin/tickets', ['pageTitle' => 'Tickets', 'tickets' => TicketModel::all()]);
    }

    public function logs(): void
    {
        require_role('admin');
        View::render('admin/logs', ['pageTitle' => 'Logs', 'logs' => GateLogModel::recent(50)]);
    }

    public function users(): void
    {
        require_role('admin');
        $me = current_user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate();
            $action = strtolower(trim($_POST['action'] ?? ''));

            try {
                if ($action === 'create_user') {
                    $fullName = trim($_POST['full_name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = (string)($_POST['password'] ?? '');
                    $role = trim($_POST['role'] ?? '');
                    if ($fullName === '' || $email === '' || strlen($password) < 6 || !in_array($role, ['resident','guard','admin'], true)) {
                        throw new RuntimeException('Complete the user fields; password must be at least 6 characters.');
                    }
                    $pdo = Database::pdo();
                    $pdo->beginTransaction();
                    $userId = UserModel::create(['full_name'=>$fullName,'email'=>$email,'password'=>$password,'role'=>$role]);
                    if ($role === 'resident') {
                        $stmt = $pdo->prepare("INSERT INTO residents (user_id, house_number, block_number, contact_number) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, trim($_POST['house_number'] ?? ''), trim($_POST['block_number'] ?? ''), trim($_POST['contact_number'] ?? '')]);
                    } elseif ($role === 'guard') {
                        $stmt = $pdo->prepare("INSERT INTO guards (user_id, guard_code, shift_name, contact_number) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$userId, trim($_POST['guard_code'] ?? ''), trim($_POST['shift_name'] ?? ''), trim($_POST['contact_number'] ?? '')]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO admins (user_id, admin_code) VALUES (?, ?)");
                        $stmt->execute([$userId, trim($_POST['admin_code'] ?? '')]);
                    }
                    $pdo->commit();
                    flash_set('success', ucfirst($role) . ' account created.');
                } elseif ($action === 'delete_user') {
                    $userId = (int)($_POST['user_id'] ?? 0);
                    if ($userId === (int)$me['id']) {
                        throw new RuntimeException('You cannot remove your own admin account.');
                    }
                    $target = UserModel::findById($userId);
                    if (!$target) throw new RuntimeException('User not found.');
                    UserModel::delete($userId);
                    flash_set('success', 'User removed.');
                } elseif ($action === 'add_vehicle') {
                    $residentId = (int)($_POST['resident_id'] ?? 0);
                    $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
                    $type = trim($_POST['vehicle_type'] ?? '');
                    if (!$residentId || $plate === '' || $type === '') throw new RuntimeException('Resident, plate and vehicle type are required.');
                    VehicleModel::create($residentId, $plate, $type, trim($_POST['color'] ?? ''));
                    flash_set('success', 'Vehicle added to the resident account.');
                } elseif ($action === 'delete_vehicle') {
                    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
                    if (!VehicleModel::delete($vehicleId)) throw new RuntimeException('Vehicle not found.');
                    flash_set('success', 'Vehicle removed.');
                } elseif ($action === 'blacklist_add') {
                    $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
                    $reason = trim($_POST['reason'] ?? '');
                    if ($reason === '' || ($plate === '' && trim($_POST['visitor_name'] ?? '') === '')) throw new RuntimeException('Provide a plate number or visitor name and a reason.');
                    BlacklistModel::add($_POST, (int)$me['id']);
                    flash_set('success', 'Vehicle/visitor added to blacklist.');
                } elseif ($action === 'blacklist_remove') {
                    $id = (int)($_POST['blacklist_id'] ?? 0);
                    if (!BlacklistModel::remove($id)) throw new RuntimeException('Blacklist entry not found.');
                    flash_set('success', 'Blacklist entry removed.');
                }
            } catch (Throwable $e) {
                if (Database::pdo()->inTransaction()) Database::pdo()->rollBack();
                flash_set('danger', $e->getMessage());
            }
            redirect('admin/users.php');
        }

        View::render('admin/users', [
            'pageTitle' => 'Users & Vehicles',
            'residents' => ResidentModel::all(),
            'guards' => UserModel::byRole('guard'),
            'admins' => UserModel::byRole('admin'),
            'vehicles' => VehicleModel::all(),
            'blacklist' => BlacklistModel::all(),
        ]);
    }
}

class NotificationController
{
    public function index(): void
    {
        require_login();
        $u = current_user();
        View::render('notifications/index', [
            'pageTitle' => 'Notifications',
            'notifications' => NotificationModel::all((int)$u['id']),
        ]);
    }

    public function read(): void
    {
        require_login();
        $u = current_user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_validate();
        $action = strtolower(trim($_REQUEST['action'] ?? 'one'));
        if ($action === 'all') {
            NotificationModel::markAllRead((int)$u['id']);
        } else {
            $id = (int)($_REQUEST['id'] ?? $_REQUEST['read'] ?? 0);
            if ($id) NotificationModel::markRead($id, (int)$u['id']);
        }
        $back = trim($_REQUEST['back'] ?? '');
        redirect($back !== '' ? $back : 'notifications.php');
    }
}

class Esp32Controller
{
    public function logAccess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
        }

        $result = GateLogModel::createAccess([
            'plate_number' => trim($_POST['plate_number'] ?? ''),
            'rfid_uid' => trim($_POST['rfid_uid'] ?? ''),
            'event_type' => trim($_POST['event_type'] ?? 'rfid_scan'),
            'source_device' => trim($_POST['source_device'] ?? 'esp32'),
            'manual_override' => bool_from_input($_POST['manual_override'] ?? '0'),
            'raw_payload' => $_POST,
            'plate_photo' => $_FILES['plate_photo'] ?? [],
            'vehicle_photo' => $_FILES['vehicle_photo'] ?? [],
        ]);

        json_response($result);
    }
}
/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
