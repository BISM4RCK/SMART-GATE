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
        redirect('dashboard.php');
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
            $plate = trim($_POST['plate_number'] ?? '');
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
            $ticketId = (int)($_POST['ticket_id'] ?? 0);
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
        View::render('admin/users', [
            'pageTitle' => 'Users',
            'residents' => ResidentModel::all(),
            'guards' => UserModel::byRole('guard'),
            'admins' => UserModel::byRole('admin'),
            'vehicles' => VehicleModel::all(),
            'blacklist' => Database::pdo()->query("SELECT * FROM blacklist ORDER BY created_at DESC")->fetchAll(),
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
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            NotificationModel::markRead($id, (int)$u['id']);
        }
        $back = trim($_GET['back'] ?? '');
        if ($back !== '') {
            header('Location: ' . $back);
            exit;
        }
        redirect('notifications.php');
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
