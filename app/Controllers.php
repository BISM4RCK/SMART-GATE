<?php
/* BISM4RCK-KUN3H0 2026 */
class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            redirect(dashboard_url());
        }

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
    public function form(): void { View::render('visitor/register', ['pageTitle'=>'Visitor Request','resident'=>null,'residentMode'=>false]); }

    public function residentForm(): void
    {
        require_role('resident');
        $resident=ResidentModel::findByUserId((int)current_user()['id']);
        View::render('visitor/register',['pageTitle'=>'Pre-register Visitor','resident'=>$resident,'residentMode'=>true]);
    }

    public function submit(bool $residentMode=false): void
    {
        if ($_SERVER['REQUEST_METHOD']!=='POST') redirect($residentMode?'resident/visitor.php':'visitor/register.php');
        $houseInput = $residentMode ? '' : (preg_match('/^\d+-\d+(?:-[A-Za-z])?$/', trim($_POST['house_number'] ?? '')) ? trim($_POST['house_number']) : (trim($_POST['house_block']??'') && trim($_POST['house_lot']??'') ? trim($_POST['house_block']).'-'.trim($_POST['house_lot']).(trim($_POST['house_letter']??'')?'-'.strtoupper(trim($_POST['house_letter'])):'') : ''));
        $resident=$residentMode ? ResidentModel::findByUserId((int)current_user()['id']) : ResidentModel::findByHouse($houseInput);
        $house=$resident['house_number']??''; $name=trim($_POST['visitor_name']??''); $contact=trim($_POST['contact_number']??''); $purpose=trim($_POST['purpose']??''); $people=(int)($_POST['people_count']??0);
        $idNotAvailable=!empty($_POST['id_not_available']); $plates=$_POST['vehicle_plate']??[]; $types=$_POST['vehicle_type']??[]; $vp=$_POST['vehicle_people']??[];
        if(!is_array($plates))$plates=[$plates]; if(!is_array($types))$types=[$types]; if(!is_array($vp))$vp=[$vp];
        $valid=[]; foreach($plates as $i=>$p){$p=strtoupper(trim($p));$t=strtolower(trim($types[$i]??''));$pc=(int)($vp[$i]??0);if($p!==''&&in_array($t,['car','motorcycle','truck','other'],true)&&$pc>0)$valid[]=['plate'=>$p,'type'=>$t,'people'=>$pc];}
        if(!$resident||$name===''||$purpose===''||$people<1||empty($valid)){flash_set('danger','Please complete the visitor details and add at least one vehicle.');redirect($residentMode?'resident/visitor.php':'visitor/register.php');}
        $qr='GH-'.strtoupper(bin2hex(random_bytes(4)));
        $requestId=VisitorRequestModel::create(['resident_id'=>(int)$resident['id'],'house_number'=>$house,'visitor_name'=>$name,'contact_number'=>$contact,'plate_number'=>$valid[0]['plate'],'vehicle_type'=>$valid[0]['type'],'purpose_of_visit'=>$purpose,'people_count'=>$people,'id_not_available'=>$idNotAvailable,'qr_reference'=>$qr,'requested_visit_date'=>date('Y-m-d'),'requested_arrival_time'=>date('H:i:s')]);
        foreach($valid as $v) VisitorRequestModel::addVehicle($requestId,$v['plate'],$v['type'],$v['people']);
        $cred=VisitorCredentialModel::create($requestId);
        $idFile=store_upload($_FILES['government_id']??[],'ids');
        if($idFile){$stmt=Database::pdo()->prepare("INSERT INTO visitor_attachments (visitor_request_id,file_type,file_path,original_filename,mime_type,file_size) VALUES (?, 'government_id', ?, ?, ?, ?)");$stmt->execute([$requestId,$idFile,$_FILES['government_id']['name']??'',$_FILES['government_id']['type']??'',$_FILES['government_id']['size']??null]);}
        NotificationModel::create((int)$resident['user_id'],'New visitor request','A visitor request was submitted for House '.$house.'.');
        if($residentMode) activity_log('visitor_pre_registration_created','Visitor '.$name.' / ID '.$cred['visitor_id']);
        redirect('visitor/status.php?id='.urlencode($cred['visitor_id']));
    }

    public function status(): void
    {
        $id=strtoupper(trim($_GET['id']??'')); $ref=trim($_GET['ref']??'');
        $credential=$id?VisitorCredentialModel::findByVisitorId($id):null;
        $request=$credential?VisitorRequestModel::findById((int)$credential['visitor_request_id']):($ref?VisitorRequestModel::findByReference($ref):null);
        if($request&&!$credential)$credential=VisitorCredentialModel::forRequest((int)$request['id']);
        $vehicles=$request?VisitorRequestModel::vehicles((int)$request['id']):[];
        View::render('visitor/status',['pageTitle'=>'Visitor Status','request'=>$request,'credential'=>$credential,'vehicles'=>$vehicles,'ref'=>$ref,'id'=>$id]);
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

    public function visitor(): void
    {
        require_role('resident');
        $resident=$this->resident();
        if ($_SERVER['REQUEST_METHOD']==='POST') { csrf_validate(); (new VisitorController())->submit(true); return; }
        View::render('visitor/register',['pageTitle'=>'Pre-register Visitor','resident'=>$resident,'residentMode'=>true]);
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
                $target=VisitorRequestModel::findById($id);
                if(!$target || (int)$target['resident_id']!==(int)$resident['id']) { flash_set('danger','That visitor request does not belong to your account.'); redirect('resident/requests.php'); }
                if(VisitorRequestModel::updateStatus($id,$action,(int)current_user()['id'],$reason)) { activity_log('visitor_request_'.$action,'Request ID '.$id.($reason?': '.$reason:'')); flash_set('success','Request updated.'); } else flash_set('danger','Request could not be updated.');
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate(); $action=strtolower(trim($_POST['action']??''));
            if ($action==='gate_override') {
                $plate=strtoupper(trim($_POST['plate_number']??'')); $emergency=!empty($_POST['emergency']);
                if($plate===''&&!$emergency){flash_set('danger','Enter a plate number or tick Emergency.');redirect('guard/dashboard.php');}
                $reason=trim($_POST['reason']??'')?:($emergency?'Emergency manual override':'Manual gate override');$me=current_user();
                $cmd=GateCommandModel::create((int)$me['id'],'guard','open_gate','guard-dashboard',['plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]);
                GateLogModel::createAccess(['event_type'=>'manual_open','source_device'=>'guard-dashboard','manual_override'=>1,'plate_number'=>$plate,'guard_id'=>(int)$me['id'],'actor_user_id'=>(int)$me['id'],'actor_role'=>'guard','raw_payload'=>['command_id'=>$cmd,'plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]]);
                activity_log('gate_override','Gate opening command issued. Command '.$cmd.' / '.($plate?:'EMERGENCY')); redirect('guard/dashboard.php?gate_command='.$cmd);
            }
            if ($action==='visitor_id_check') {
                $visitorId=strtoupper(trim($_POST['visitor_id']??''));
                if(!preg_match('/^[A-Z0-9]{6}$/',$visitorId)){flash_set('danger','Enter a valid 6-character Visitor ID.');redirect('guard/dashboard.php');}
                $result=GateLogModel::createAccess(['visitor_id'=>$visitorId,'event_type'=>'visitor_id_check','source_device'=>'guard-dashboard','actor_user_id'=>(int)current_user()['id'],'actor_role'=>'guard','guard_id'=>(int)current_user()['id'],'raw_payload'=>$_POST]);
                if($result['gate_status']==='approved'){ $cmd=GateCommandModel::create((int)current_user()['id'],'guard','open_gate','guard-visitor-id',['visitor_id'=>$visitorId,'gate_log_id'=>$result['log_id']]); activity_log('visitor_id_check','Visitor '.$visitorId.' approved; gate command '.$cmd.' issued.'); redirect('guard/dashboard.php?gate_command='.$cmd); }
                flash_set($result['gate_status']==='pending'?'warning':'danger',$result['gate_status']==='pending'?'Request still pending.':'Denied.'); redirect('guard/dashboard.php');
            }
        }
        View::render('guard/dashboard',['pageTitle'=>'Guard Dashboard','stats'=>['pending'=>count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE status = 'pending'"),'logs'=>count_rows("SELECT COUNT(*) c FROM gate_logs"),'tickets'=>TicketModel::openCount(),'vehicles'=>count_rows("SELECT COUNT(*) c FROM vehicles")],'requests'=>array_slice(VisitorRequestModel::all(),0,6),'logs'=>array_slice(GateLogModel::recent(8),0,8),'latestGateLogId'=>GateLogModel::latestId(),'gateCommandId'=>(int)($_GET['gate_command']??0)]);
    }

    public function liveRfid(): void { require_role('guard'); $after=(int)($_GET['after_id']??0); json_response(['ok'=>true,'log'=>GateLogModel::latestRfidAfter($after)]); }
    public function gateCommandStatus(): void { require_role('guard'); $id=(int)($_GET['command_id']??0); $cmd=GateCommandModel::find($id); if(!$cmd) json_response(['ok'=>false,'message'=>'Gate command not found.'],404); json_response(['ok'=>true,'status'=>$cmd['status'],'command_id'=>(int)$cmd['id']]); }

    public function logs(): void
    {
        require_role('guard');
        $filters=['event_type'=>trim($_GET['event_type']??''),'gate_status'=>trim($_GET['gate_status']??''),'search'=>trim($_GET['search']??'')];
        View::render('guard/logs',['pageTitle'=>'Gate Logs','logs'=>GateLogModel::all($filters),'filters'=>$filters]);
    }

    public function activityLogs(): void
    {
        require_role('guard'); $me=current_user();
        $filters=['account_type'=>'guard','user_id'=>(string)$me['id'],'action'=>trim($_GET['action']??'')];
        View::render('guard/activity_logs',['pageTitle'=>'Guard Activity Logs','activityLogs'=>AccountActivityLogModel::filtered($filters,200),'activityFilters'=>$filters]);
    }

    public function walkIn(): void
    {
        require_role(['guard','admin']); $me=current_user(); $created=null; $lookup=null;
        $target=$me['role']==='admin'?'admin/walkin.php':'guard/walkin.php';
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate(); $action=strtolower(trim($_POST['action']??'create'));
            try{
                if($action==='create'){
                    $name=trim($_POST['visitor_name']??'');$purpose=trim($_POST['purpose']??'');$plates=$_POST['vehicle_plate']??[];$types=$_POST['vehicle_type']??[];$people=$_POST['vehicle_people']??[];
                    if(!is_array($plates))$plates=[$plates];if(!is_array($types))$types=[$types];if(!is_array($people))$people=[$people];$hasVehicle=false;foreach($plates as $i=>$plate){$plate=trim($plate);$type=strtolower(trim($types[$i]??'other'));if($plate!==''){if(!in_array($type,['car','motorcycle','truck','other'],true))throw new RuntimeException('Choose a valid vehicle type.');$hasVehicle=true;}}
                    if($name===''||$purpose==='')throw new RuntimeException('Visitor name and purpose are required.');
                    $created=WalkInVisitorModel::create(['visitor_name'=>$name,'contact_number'=>$_POST['contact_number']??'','purpose'=>$purpose,'vehicle_plate'=>$plates,'vehicle_type'=>$types,'vehicle_people'=>$people],(int)$me['id']);
                    activity_log('walk_in_registered','Walk-in visitor '.$created['visitor_id'].' / '.$name);flash_set('success','Walk-in visitor registered.');
                } elseif($action==='checkin'){
                    $visitorId=strtoupper(trim($_POST['visitor_id']??''));$barcode=trim($_POST['barcode_token']??'');$lookup=$barcode?WalkInVisitorModel::findByToken($barcode):WalkInVisitorModel::findByVisitorId($visitorId);
                    if(!$lookup)throw new RuntimeException('Walk-in visitor credential not found.');
                    $result=GateLogModel::createAccess(['visitor_id'=>$lookup['visitor_id'],'barcode_token'=>$lookup['barcode_token'],'event_type'=>'walk_in_checkin','source_device'=>$me['role']==='admin'?'admin-walkin':'guard-walkin','actor_user_id'=>(int)$me['id'],'actor_role'=>$me['role'],'guard_id'=>(int)$me['id'],'raw_payload'=>$_POST]);
                    activity_log('walk_in_checkin','Walk-in visitor '.$lookup['visitor_id'].' checked in');flash_set($result['gate_status']==='approved'?'success':'danger',$result['notes']);
                }
            }catch(Throwable $e){flash_set('danger',$e->getMessage());}
        }
        View::render('guard/walkin',['pageTitle'=>'Walk-In Visitor','created'=>$created,'lookup'=>$lookup]);
    }

    public function blacklist(): void
    {
        require_role(['guard','admin']);$u=current_user();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate();$action=strtolower(trim($_POST['action']??''));
            try{
                if($action==='add'){$plate=strtoupper(trim($_POST['plate_number']??''));$reason=trim($_POST['reason']??'');if($reason===''||($plate===''&&trim($_POST['visitor_name']??'')===''))throw new RuntimeException('Provide a plate number or visitor name and a reason.');BlacklistModel::add($_POST,(int)$u['id']);activity_log('blacklist_added','Plate '.($plate?:trim($_POST['visitor_name']??'')));flash_set('success','Blacklist entry added.');}
                elseif($action==='remove'){if(!BlacklistModel::remove((int)($_POST['blacklist_id']??0)))throw new RuntimeException('Blacklist entry not found.');activity_log('blacklist_removed','Blacklist ID '.(int)($_POST['blacklist_id']??0));flash_set('success','Blacklist entry removed.');}
            }catch(Throwable $e){flash_set('danger',$e->getMessage());}
            redirect($u['role']==='admin'?'admin/blacklist.php':'guard/blacklist.php');
        }
        View::render('guard/blacklist',['pageTitle'=>'Vehicle Blacklist','blacklist'=>BlacklistModel::all()]);
    }
}

class AdminController
{
    public function dashboard(): void
    {
        require_role('admin');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate();$action=strtolower(trim($_POST['action']??''));
            if($action==='gate_override'){
                $plate=strtoupper(trim($_POST['plate_number']??''));$emergency=!empty($_POST['emergency']);
                if($plate===''&&!$emergency){flash_set('danger','Enter a plate number or tick Emergency.');redirect('admin/dashboard.php');}
                $reason=trim($_POST['reason']??'')?:($emergency?'Emergency manual override':'Manual gate override');$me=current_user();
                $cmd=GateCommandModel::create((int)$me['id'],'admin','open_gate','admin-dashboard',['plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]);
                GateLogModel::createAccess(['event_type'=>'manual_open','source_device'=>'admin-dashboard','manual_override'=>1,'plate_number'=>$plate,'actor_user_id'=>(int)$me['id'],'actor_role'=>'admin','raw_payload'=>['command_id'=>$cmd,'plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]]);
                activity_log('gate_override','Gate opening command issued. Command '.$cmd.' / '.($plate?:'EMERGENCY'));redirect('admin/dashboard.php?gate_command='.$cmd);
            }
            if($action==='visitor_id_check'){
                $visitorId=strtoupper(trim($_POST['visitor_id']??'')); if(!preg_match('/^[A-Z0-9]{6}$/',$visitorId)){flash_set('danger','Enter a valid 6-character Visitor ID.');redirect('admin/dashboard.php');}
                $result=GateLogModel::createAccess(['visitor_id'=>$visitorId,'event_type'=>'visitor_id_check','source_device'=>'admin-dashboard','actor_user_id'=>(int)current_user()['id'],'actor_role'=>'admin','raw_payload'=>$_POST]);
                if($result['gate_status']==='approved'){ $cmd=GateCommandModel::create((int)current_user()['id'],'admin','open_gate','admin-visitor-id',['visitor_id'=>$visitorId,'gate_log_id'=>$result['log_id']]); activity_log('visitor_id_check','Visitor '.$visitorId.' approved; gate command '.$cmd.' issued.');redirect('admin/dashboard.php?gate_command='.$cmd);}
                flash_set($result['gate_status']==='pending'?'warning':'danger',$result['gate_status']==='pending'?'Request still pending.':'Denied.');redirect('admin/dashboard.php');
            }
        }
        View::render('admin/dashboard',['pageTitle'=>'Admin Dashboard','stats'=>['residents'=>count_rows('SELECT COUNT(*) c FROM residents'),'requests'=>count_rows('SELECT COUNT(*) c FROM visitor_requests'),'tickets'=>TicketModel::openCount(),'logs'=>count_rows('SELECT COUNT(*) c FROM gate_logs')],'tickets'=>array_slice(TicketModel::all(),0,5),'logs'=>array_slice(GateLogModel::recent(8),0,8),'latestGateLogId'=>GateLogModel::latestId(),'gateCommandId'=>(int)($_GET['gate_command']??0)]);
    }
    public function walkIn(): void { require_role('admin'); (new GuardController())->walkIn(); }
    public function tickets(): void
    {
        require_role('admin');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate();$action=strtolower(trim($_POST['action']??'reply'));$ticketId=(int)($_POST['ticket_id']??0);
            if($action==='delete'&&$ticketId){if(TicketModel::delete($ticketId)){activity_log('ticket_deleted','Ticket ID '.$ticketId);flash_set('success','Ticket deleted.');}else flash_set('danger','Ticket could not be deleted.');redirect('admin/tickets.php');}
            $reply=trim($_POST['reply']??'');if($ticketId&&$reply!==''){TicketModel::reply($ticketId,(int)current_user()['id'],$reply);activity_log('ticket_replied','Ticket ID '.$ticketId);$stmt=Database::pdo()->prepare('SELECT resident_id FROM concerns WHERE id=?');$stmt->execute([$ticketId]);$row=$stmt->fetch();if(!empty($row['resident_id'])){$stmt2=Database::pdo()->prepare('SELECT user_id FROM residents WHERE id=?');$stmt2->execute([(int)$row['resident_id']]);$ru=$stmt2->fetch();if($ru)NotificationModel::create((int)$ru['user_id'],'Ticket replied','Your ticket has a new reply.');}flash_set('success','Reply saved.');redirect('admin/tickets.php');}
            flash_set('danger','Write a reply first.');
        }
        View::render('admin/tickets',['pageTitle'=>'Tickets','tickets'=>TicketModel::all()]);
    }
    public function logs(): void
    {
        require_role('admin');$filters=['event_type'=>trim($_GET['event_type']??''),'gate_status'=>trim($_GET['gate_status']??''),'actor_user_id'=>trim($_GET['actor_user_id']??''),'reader'=>trim($_GET['reader']??''),'search'=>trim($_GET['search']??'')];
        View::render('admin/logs',['pageTitle'=>'Gate Logs','logs'=>GateLogModel::all($filters),'filters'=>$filters,'actors'=>array_merge(UserModel::byRole('guard'),UserModel::byRole('admin'))]);
    }
    public function rfid(): void
    {
        require_role('admin');
        $me = current_user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_validate();
            $action = strtolower(trim($_POST['action'] ?? ''));

            try {
                if ($action === 'assign') {
                    $userId = (int)($_POST['user_id'] ?? 0);
                    $target = UserModel::findById($userId);

                    if (
                        !$target ||
                        !in_array($target['role'], ['resident', 'guard', 'admin'], true) ||
                        ($target['status'] ?? 'active') !== 'active'
                    ) {
                        throw new RuntimeException('Select a valid active resident or staff account.');
                    }

                    $vehicleId = null;
                    $staffVehicleId = null;
                    $vehicle = null;

                    if (($target['role'] ?? '') === 'resident') {
                        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

                        if ($vehicleId <= 0) {
                            throw new RuntimeException('Select the resident vehicle that this RFID card will be assigned to.');
                        }

                        $resident = ResidentModel::findByUserId($userId);
                        $vehicle = $resident ? VehicleModel::find($vehicleId) : null;

                        if (
                            !$resident ||
                            !$vehicle ||
                            (int)$vehicle['resident_id'] !== (int)$resident['id'] ||
                            ($vehicle['status'] ?? 'active') !== 'active'
                        ) {
                            throw new RuntimeException('Select a valid active vehicle belonging to the selected resident.');
                        }
                    } else {
                        $staffVehicleId = (int)($_POST['vehicle_id'] ?? 0);

                        if ($staffVehicleId <= 0) {
                            throw new RuntimeException('Select the staff vehicle that this RFID card will be assigned to.');
                        }

                        $vehicle = UserVehicleModel::find($staffVehicleId);

                        if (
                            !$vehicle ||
                            (int)$vehicle['user_id'] !== $userId
                        ) {
                            throw new RuntimeException('Select a valid active vehicle belonging to the selected staff account.');
                        }
                    }

                    $credential = RfidCardModel::profileCodeForUser($target, $vehicle);

                    $sessionId = RfidScanSessionModel::create(
                        ESP32_DEVICE_ID,
                        (int)$me['id'],
                        'admin',
                        'burn',
                        $userId,
                        trim($_POST['notes'] ?? ''),
                        $vehicleId,
                        $staffVehicleId
                    );

                    json_response([
                        'ok' => true,
                        'session_id' => $sessionId,
                        'credential_code' => $credential,
                        'message' => 'Waiting for RFID card...',
                    ]);
                } elseif ($action === 'void') {
                    $cardId = (int)($_POST['rfid_card_id'] ?? 0);
                    $cards = RfidCardModel::all(['status' => 'active', 'search' => '']);
                    $target = null;

                    foreach ($cards as $row) {
                        if ((int)$row['id'] === $cardId) {
                            $target = $row;
                            break;
                        }
                    }

                    if (!$target) {
                        throw new RuntimeException('Active RFID profile not found.');
                    }

                    if (RfidCardModel::void($cardId, (int)$me['id'], trim($_POST['notes'] ?? ''))) {
                        activity_log(
                            'rfid_voided',
                            'RFID '.($target['uid'] ?? '').' voided for '.$target['email'].'; physical card must be rewritten using compatible RFID hardware.'
                        );
                        flash_set('success', 'RFID profile voided. It can no longer validate at the gate.');
                    } else {
                        throw new RuntimeException('RFID profile could not be voided.');
                    }
                }
            } catch (Throwable $e) {
                flash_set('danger', $e->getMessage());
            }

            redirect('admin/rfid.php');
        }

        $filters = [
            'account_type' => strtolower(trim($_GET['account_type'] ?? '')),
            'status' => strtolower(trim($_GET['status'] ?? '')),
            'search' => trim($_GET['search'] ?? ''),
        ];

        if (!in_array($filters['account_type'], ['resident', 'staff'], true)) {
            $filters['account_type'] = '';
        }

        if (!in_array($filters['status'], ['active', 'void'], true)) {
            $filters['status'] = '';
        }

        $accounts = UserModel::all();
        $cards = RfidCardModel::all($filters);

        View::render('admin/rfid', [
            'pageTitle' => 'RFID Management',
            'cards' => $cards,
            'accounts' => $accounts,
            'filters' => $filters,
        ]);
    }

    public function rfidVehicles(): void
    {
        require_role('admin');

        $userId = (int)($_GET['user_id'] ?? 0);

        if ($userId <= 0) {
            json_response([
                'ok' => false,
                'message' => 'Select an account first.',
                'vehicles' => [],
            ], 422);
            return;
        }

        $target = UserModel::findById($userId);

        if (
            !$target ||
            !in_array(($target['role'] ?? ''), ['resident', 'guard', 'admin'], true) ||
            ($target['status'] ?? 'active') !== 'active'
        ) {
            json_response([
                'ok' => false,
                'message' => 'The selected account is not an active resident or staff account.',
                'vehicles' => [],
            ], 422);
            return;
        }

        if (($target['role'] ?? '') === 'resident') {
            $resident = ResidentModel::findByUserId($userId);

            if (!$resident) {
                json_response([
                    'ok' => false,
                    'message' => 'No resident profile was found for this account.',
                    'vehicles' => [],
                ], 404);
                return;
            }

            $vehicles = VehicleModel::forResident((int)$resident['id']);
        } else {
            $vehicles = UserVehicleModel::allStaff(['owner_id' => $userId]);
        }

        $vehicles = array_values(array_map(
            static function (array $vehicle): array {
                return [
                    'id' => (int)$vehicle['id'],
                    'plate_number' => (string)$vehicle['plate_number'],
                    'vehicle_type' => (string)($vehicle['vehicle_type'] ?? 'other'),
                    'color' => (string)($vehicle['color'] ?? 'N/A'),
                    'status' => (string)($vehicle['status'] ?? 'active'),
                ];
            },
            array_filter(
                $vehicles,
                static fn(array $vehicle): bool => ($vehicle['status'] ?? 'active') === 'active'
            )
        ));

        json_response(['ok' => true, 'vehicles' => $vehicles]);
    }

    public function liveRfid(): void { require_role('admin'); $after=(int)($_GET['after_id']??0); json_response(['ok'=>true,'log'=>GateLogModel::latestRfidAfter($after)]); }
    public function gateCommandStatus(): void { require_role('admin'); $id=(int)($_GET['command_id']??0); $cmd=GateCommandModel::find($id); if(!$cmd) json_response(['ok'=>false,'message'=>'Gate command not found.'],404); json_response(['ok'=>true,'status'=>$cmd['status'],'command_id'=>(int)$cmd['id']]); }

    public function activityLogs(): void
    {
        require_role('admin');$filters=['account_type'=>trim($_GET['account_type']??''),'user_id'=>trim($_GET['user_id']??''),'action'=>trim($_GET['action']??'')];
        View::render('admin/activity_logs',['pageTitle'=>'Admin / Guard Logs','activityLogs'=>AccountActivityLogModel::filtered($filters,300),'activityFilters'=>$filters,'activityUsers'=>array_merge(UserModel::byRole('guard'),UserModel::byRole('admin'))]);
    }
    public function users(): void
    {
        require_role('admin');$me=current_user();
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate();$action=strtolower(trim($_POST['action']??''));
            try{
                if($action==='create_user'){
                    $fullName=trim($_POST['full_name']??'');$email=trim($_POST['email']??'');$password=(string)($_POST['password']??'');$role=trim($_POST['role']??'');
                    if($fullName===''||$email===''||strlen($password)<6||!in_array($role,['resident','guard','admin'],true))throw new RuntimeException('Select an account type and complete the basic fields; password must be at least 6 characters.');
                    $pdo=Database::pdo();$pdo->beginTransaction();$userId=UserModel::create(['full_name'=>$fullName,'email'=>$email,'password'=>$password,'role'=>$role]);
                    if($role==='resident'){$block=trim($_POST['resident_block']??'');$lot=trim($_POST['resident_lot']??'');$letter=strtoupper(trim($_POST['resident_letter']??''));if(!preg_match('/^\d+$/',$block)||!preg_match('/^\d+$/',$lot)||($letter!==''&&!preg_match('/^[A-Z]$/',$letter)))throw new RuntimeException('Resident Block and Lot must be numeric; Household Letter is optional and must be one letter.');$house=$block.'-'.$lot.($letter?'-'.$letter:'');$stmt=$pdo->prepare('INSERT INTO residents (user_id,house_number,block_number,lot_number,household_letter,contact_number) VALUES (?,?,?,?,?,?)');$stmt->execute([$userId,$house,$block,$lot,$letter?:null,trim($_POST['contact_number']??'')]);}
                    elseif($role==='guard'){if(trim($_POST['guard_code']??'')==='')throw new RuntimeException('Guard ID is required.');$stmt=$pdo->prepare('INSERT INTO guards (user_id,guard_code,shift_name,contact_number) VALUES (?,?,?,?)');$stmt->execute([$userId,trim($_POST['guard_code']),trim($_POST['shift_name']??''),trim($_POST['contact_number_guard']??'')]);}
                    else{if(trim($_POST['admin_code']??'')==='')throw new RuntimeException('Admin ID is required.');$stmt=$pdo->prepare('INSERT INTO admins (user_id,admin_code) VALUES (?,?)');$stmt->execute([$userId,trim($_POST['admin_code'])]);}
                    $pdo->commit();activity_log('account_created',ucfirst($role).' account '.$email);flash_set('success',ucfirst($role).' account created.');
                }elseif($action==='delete_user'){
                    $userId=(int)($_POST['user_id']??0);
                    if($userId===(int)$me['id'])throw new RuntimeException('You cannot remove your own admin account.');
                    $target=UserModel::findById($userId);
                    if(!$target)throw new RuntimeException('User not found.');
                    if(!empty($target['is_super_admin']))throw new RuntimeException('The KUN3H0 super admin account cannot be removed.');
                    UserModel::delete($userId);
                    activity_log('account_deleted','Account '.$target['email']);
                    flash_set('success','User removed.');
                }
                elseif($action==='change_password'){
                    $userId=(int)($_POST['user_id']??0);
                    $password=(string)($_POST['new_password']??'');
                    $target=UserModel::findById($userId);
                    if(!$target)throw new RuntimeException('User not found.');
                    if(!empty($target['is_super_admin']) && !UserModel::isSuperAdmin((int)$me['id'])){
                        throw new RuntimeException('Only the KUN3H0 super admin account can change its password.');
                    }
                    if(strlen($password)<6)throw new RuntimeException('Password must be at least 6 characters.');
                    if(UserModel::updatePassword($userId,$password)){
                        activity_log('account_password_changed','Password changed for '.$target['email']);
                        flash_set('success','Password changed for '.($target['full_name']??$target['email']).'.');
                    }else throw new RuntimeException('Password could not be changed.');
                }
                elseif($action==='change_username'){
                    $userId=(int)($_POST['user_id']??0);
                    $username=strtolower(trim($_POST['new_username']??''));
                    $target=UserModel::findById($userId);
                    if(!$target)throw new RuntimeException('User not found.');
                    if(!empty($target['is_super_admin']) && !UserModel::isSuperAdmin((int)$me['id'])){
                        throw new RuntimeException('Only the KUN3H0 super admin account can change its username.');
                    }
                    if(!filter_var($username,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Username must be a valid email address.');
                    $existing=UserModel::findByEmail($username);
                    if($existing&& (int)$existing['id']!==$userId)throw new RuntimeException('That username is already in use.');
                    if(UserModel::updateUsername($userId,$username)){
                        activity_log('account_username_changed','Username changed for '.$target['email'].' to '.$username);
                        flash_set('success','Username changed for '.($target['full_name']??$target['email']).'.');
                    }else throw new RuntimeException('Username could not be changed.');
                }
            }catch(Throwable $e){if(Database::pdo()->inTransaction())Database::pdo()->rollBack();flash_set('danger',$e->getMessage());}
            redirect('admin/users.php');
        }
        $accountType=strtolower(trim($_GET['account_type']??''));
        if(!in_array($accountType,['resident','staff'],true)) $accountType='all';
        $residents=$accountType==='staff'?[]:ResidentModel::all();
        $guards=$accountType==='resident'?[]:UserModel::byRole('guard');
        $admins=$accountType==='resident'?[]:UserModel::byRole('admin');
        View::render('admin/users',['pageTitle'=>'Account Management','residents'=>$residents,'guards'=>$guards,'admins'=>$admins,'accountType'=>$accountType]);
    }
    public function vehicles(): void
    {
        require_role('admin');
        if($_SERVER['REQUEST_METHOD']==='POST'){
            csrf_validate();$action=strtolower(trim($_POST['action']??''));
            try{
                if($action==='add_resident_vehicle'){$residentId=(int)($_POST['resident_id']??0);$plate=strtoupper(trim($_POST['plate_number']??''));$type=strtolower(trim($_POST['vehicle_type']??''));if(!$residentId||$plate===''||!in_array($type,['car','motorcycle','truck','other'],true))throw new RuntimeException('Choose a resident and complete the vehicle fields.');VehicleModel::create($residentId,$plate,$type,trim($_POST['color']??''));activity_log('resident_vehicle_added','Resident ID '.$residentId.' / '.$plate);flash_set('success','Resident vehicle added.');}
                elseif($action==='add_staff_vehicle'){$userId=(int)($_POST['user_id']??0);$plate=strtoupper(trim($_POST['plate_number']??''));$type=strtolower(trim($_POST['vehicle_type']??''));$target=UserModel::findById($userId);if(!$target||!in_array($target['role'],['guard','admin'],true)||$plate===''||!in_array($type,['car','motorcycle','truck','other'],true))throw new RuntimeException('Choose a guard/admin and complete the vehicle fields.');UserVehicleModel::create($userId,$plate,$type,trim($_POST['color']??''));activity_log('staff_vehicle_added','Account '.$target['email'].' / '.$plate);flash_set('success','Staff vehicle added.');}
                elseif($action==='delete_resident_vehicle'){if(!VehicleModel::delete((int)($_POST['vehicle_id']??0)))throw new RuntimeException('Vehicle not found.');activity_log('resident_vehicle_removed','Vehicle ID '.(int)$_POST['vehicle_id']);flash_set('success','Resident vehicle removed.');}
                elseif($action==='delete_staff_vehicle'){if(!UserVehicleModel::delete((int)($_POST['staff_vehicle_id']??0)))throw new RuntimeException('Staff vehicle not found.');activity_log('staff_vehicle_removed','Staff vehicle ID '.(int)$_POST['staff_vehicle_id']);flash_set('success','Staff vehicle removed.');}
            }catch(Throwable $e){flash_set('danger',$e->getMessage());}
            redirect('admin/vehicles.php');
        }
        $filters=['vehicle_type'=>strtolower(trim($_GET['vehicle_type']??'')),'owner_group'=>strtolower(trim($_GET['owner_group']??'')),'owner_id'=>trim($_GET['owner_id']??''),'search'=>trim($_GET['search']??'')];
        $residentVehicles=$filters['owner_group']==='staff'?[]:VehicleModel::all(['vehicle_type'=>$filters['vehicle_type'],'owner_id'=>$filters['owner_group']==='resident'?$filters['owner_id']:'','search'=>$filters['search']]);
        $staffVehicles=$filters['owner_group']==='resident'?[]:UserVehicleModel::allStaff(['vehicle_type'=>$filters['vehicle_type'],'owner_id'=>$filters['owner_group']==='staff'?$filters['owner_id']:'']);
        View::render('admin/vehicles',['pageTitle'=>'Vehicle Management','vehicles'=>$residentVehicles,'staffVehicles'=>$staffVehicles,'residents'=>ResidentModel::all(),'staff'=>array_merge(UserModel::byRole('guard'),UserModel::byRole('admin')),'filters'=>$filters]);
    }
    public function blacklist(): void
    {
        require_role('admin'); $u=current_user();
        if($_SERVER['REQUEST_METHOD']==='POST'){ csrf_validate(); $action=strtolower(trim($_POST['action']??'')); try{ if($action==='add'){ $plate=strtoupper(trim($_POST['plate_number']??'')); $reason=trim($_POST['reason']??''); if($reason===''||($plate===''&&trim($_POST['visitor_name']??'')==='')) throw new RuntimeException('Provide a plate number or visitor name and a reason.'); BlacklistModel::add($_POST,(int)$u['id']); activity_log('blacklist_added','Plate '.($plate?:trim($_POST['visitor_name']??''))); flash_set('success','Blacklist entry added.'); } elseif($action==='remove'){ if(!BlacklistModel::remove((int)($_POST['blacklist_id']??0))) throw new RuntimeException('Blacklist entry not found.'); activity_log('blacklist_removed','Blacklist ID '.(int)($_POST['blacklist_id']??0)); flash_set('success','Blacklist entry removed.'); } } catch(Throwable $e){ flash_set('danger',$e->getMessage()); } redirect('admin/blacklist.php'); }
        View::render('admin/blacklist',['pageTitle'=>'Blacklist Management','blacklist'=>BlacklistModel::all()]);
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
    private function requireEsp32Key(): void
    {
        $provided = trim((string)($_SERVER['HTTP_X_SMART_GATE_KEY'] ?? $_POST['device_key'] ?? $_GET['device_key'] ?? ''));
        $deviceId = trim((string)($_SERVER['HTTP_X_SMART_GATE_DEVICE'] ?? $_POST['device_id'] ?? $_GET['device_id'] ?? ''));
        if ($provided === '' || !hash_equals((string)ESP32_API_KEY, $provided)) json_response(['ok' => false, 'message' => 'Unauthorized RFID device.'], 401);
        if ($deviceId === '' || !hash_equals((string)ESP32_DEVICE_ID, $deviceId)) json_response(['ok' => false, 'message' => 'Unknown RFID device.'], 401);
    }

    public function pollScan(): void
    {
        $this->requireEsp32Key();
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
        $session=RfidScanSessionModel::waitingForDevice(ESP32_DEVICE_ID);
        $payload=['ok'=>true,'scan_requested'=>true,'continuous'=>!$session,'session_id'=>$session['id']??null,'purpose'=>$session['purpose']??'continuous'];
        if($session && ($session['purpose']??'gate')==='burn'){
            $target=UserModel::findById((int)$session['target_user_id']);
            $vehicle = null;
            if ($session && !empty($session['target_vehicle_id'])) {
                $vehicle = VehicleModel::find((int)$session['target_vehicle_id']);
            } elseif ($session && !empty($session['target_staff_vehicle_id'])) {
                $vehicle = UserVehicleModel::find((int)$session['target_staff_vehicle_id']);
            }
            $payload['credential_code'] = $target ? RfidCardModel::profileCodeForUser($target, $vehicle) : null;
        }
        json_response($payload);
    }

    public function submitScan(): void
    {
        $this->requireEsp32Key();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
        $sessionId=trim((string)($_POST['session_id']??'')); $uid=strtoupper(trim((string)($_POST['rfid_uid']??''))); $reader=in_array(strtolower(trim((string)($_POST['reader']??''))),['entry','exit'],true)?strtolower(trim((string)$_POST['reader'])):'entry';
        if(!preg_match('/^[a-f0-9:.-]{4,100}$/i',$uid)) json_response(['ok'=>false,'message'=>'Invalid scan payload.'],422);
        if($sessionId==='continuous'){ $result=GateLogModel::createAccess(['rfid_uid'=>$uid,'event_type'=>'rfid_scan','source_device'=>'esp32-'.$reader,'reader'=>$reader,'raw_payload'=>$_POST]); $opened=$result['gate_status']==='approved'; if($opened) AccountActivityLogModel::record(null,'device','ESP32 RFID Reader','rfid_gate_opened','RFID '.$uid.' accepted and gate opened.'); json_response(['ok'=>true,'gate_opened'=>$opened,'gate_status'=>$result['gate_status'],'notes'=>$result['notes'],'log_id'=>$result['log_id']]); }
        if($sessionId==='') json_response(['ok'=>false,'message'=>'Invalid scan session.'],422);
        $s=Database::pdo()->prepare("SELECT * FROM rfid_scan_sessions WHERE id=? AND device_id=? AND status='waiting' AND expires_at>NOW() LIMIT 1"); $s->execute([$sessionId,ESP32_DEVICE_ID]); $session=$s->fetch();
        if(!$session) json_response(['ok'=>false,'message'=>'Scan session expired or already completed.'],409);
        if (($session['purpose'] ?? 'gate') === 'burn') {
            $target = UserModel::findById((int)$session['target_user_id']);

            if (
                !$target ||
                !in_array($target['role'], ['resident', 'guard', 'admin'], true) ||
                ($target['status'] ?? 'active') !== 'active'
            ) {
                RfidScanSessionModel::finish(
                    $sessionId,
                    'error',
                    ['notes' => 'Target RFID account is invalid or inactive.']
                );
                json_response([
                    'ok' => false,
                    'message' => 'Target RFID account is invalid or inactive.',
                ], 422);
            }

            try {
                $vehicleId = !empty($session['target_vehicle_id'])
                    ? (int)$session['target_vehicle_id']
                    : null;
                $staffVehicleId = !empty($session['target_staff_vehicle_id'])
                    ? (int)$session['target_staff_vehicle_id']
                    : null;

                $vehicle = $vehicleId
                    ? VehicleModel::find($vehicleId)
                    : ($staffVehicleId ? UserVehicleModel::find($staffVehicleId) : null);

                $credential = RfidCardModel::profileCodeForUser($target, $vehicle);

                if (($target['role'] ?? '') === 'resident') {
                    if (!$vehicleId) {
                        throw new RuntimeException('A resident RFID card must have a resident vehicle selected.');
                    }

                    $resident = ResidentModel::findByUserId((int)$target['id']);
                    $residentVehicle = $resident ? VehicleModel::find($vehicleId) : null;

                    if (
                        !$resident ||
                        !$residentVehicle ||
                        (int)$residentVehicle['resident_id'] !== (int)$resident['id'] ||
                        ($residentVehicle['status'] ?? 'active') !== 'active'
                    ) {
                        throw new RuntimeException('The selected resident vehicle is no longer valid.');
                    }

                    $vehicle = $residentVehicle;
                } else {
                    if (!$staffVehicleId) {
                        throw new RuntimeException('A staff RFID card must have a staff vehicle selected.');
                    }

                    if (
                        !$vehicle ||
                        (int)$vehicle['user_id'] !== (int)$target['id']
                    ) {
                        throw new RuntimeException('The selected staff vehicle is no longer valid.');
                    }
                }

                $id = RfidCardModel::assign(
                    (int)$target['id'],
                    $uid,
                    null,
                    'ESP32 RC522 RFID burn',
                    $credential,
                    $vehicleId,
                    $staffVehicleId
                );

                $vehicleNote = $vehicle
                    ? ' / vehicle '.($vehicle['plate_number'] ?? $vehicle['id'])
                    : '';

                AccountActivityLogModel::record(
                    (int)$session['actor_user_id'],
                    $session['actor_role'],
                    null,
                    'rfid_programmed',
                    'RFID '.$uid.' assigned to '.$target['email'].' as '.$credential.
                    ' (profile #'.$id.')'.$vehicleNote
                );

                $result = [
                    'gate_opened' => false,
                    'rfid_uid' => $uid,
                    'rfid_card_id' => $id,
                    'credential_code' => $credential,
                    'account' => $target['full_name'],
                    'role' => $target['role'],
                    'vehicle_id' => $vehicleId ?: $staffVehicleId,
                    'vehicle_plate' => $vehicle['plate_number'] ?? null,
                    'notes' => 'Card updated successfully. RFID profile programmed and assigned.',
                ];

                RfidScanSessionModel::finish($sessionId, 'approved', $result);

                json_response([
                    'ok' => true,
                    'burn' => true,
                    'write_profile' => true,
                    'credential_code' => $credential,
                    'result' => $result,
                ]);
            } catch (Throwable $e) {
                RfidScanSessionModel::finish(
                    $sessionId,
                    'error',
                    ['notes' => $e->getMessage()]
                );
                json_response([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }
        }

        $result=GateLogModel::createAccess(['rfid_uid'=>$uid,'event_type'=>'rfid_scan','source_device'=>'esp32-'.ESP32_DEVICE_ID,'reader'=>$reader,'actor_user_id'=>(int)$session['actor_user_id'],'actor_role'=>$session['actor_role'],'raw_payload'=>['session_id'=>$sessionId,'rfid_uid'=>$uid,'device_id'=>ESP32_DEVICE_ID]]);
        $approved=in_array($result['gate_status'],['approved','manual_override'],true);
        RfidScanSessionModel::finish($sessionId,$approved?'approved':'error',$result+['rfid_uid'=>$uid]);
        AccountActivityLogModel::record((int)$session['actor_user_id'],$session['actor_role'],null,'rfid_gate_scan',$result['notes']);
        json_response(['ok'=>true,'gate_opened'=>$approved,'message'=>$approved?'Gate opened successfully.':('ERROR! '.$result['notes']),'result'=>$result]);
    }

    public function enrollRfid(): void
    {
        $this->requireEsp32Key();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
        $uid = strtoupper(trim((string)($_POST['rfid_uid'] ?? '')));
        $userId = (int)($_POST['user_id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $staffVehicleId = (int)($_POST['staff_vehicle_id'] ?? 0);
        if ($uid === '' || !preg_match('/^[A-F0-9:._-]{4,100}$/i', $uid)) json_response(['ok'=>false,'message'=>'Invalid RFID UID.'],422);
        $target = UserModel::findById($userId);
        if (!$target || !in_array($target['role'], ['resident','guard','admin'], true) || ($target['status'] ?? 'active') !== 'active') json_response(['ok'=>false,'message'=>'Invalid or inactive account.'],422);
        try {
            $id = RfidCardModel::assign(
                $userId,
                $uid,
                null,
                'ESP32 RC522 programming',
                null,
                $vehicleId ?: null,
                $staffVehicleId ?: null
            );
            AccountActivityLogModel::record(null, 'device', 'ESP32 RFID Reader', 'rfid_programmed', 'RFID '.$uid.' assigned to '.$target['email'].' (profile #'.$id.')');
            json_response(['ok'=>true,'rfid_card_id'=>$id,'uid'=>$uid,'user_id'=>$userId,'account'=>$target['full_name'],'role'=>$target['role']]);
        } catch (Throwable $e) { json_response(['ok'=>false,'message'=>$e->getMessage()],409); }
    }

    public function voidRfid(): void
    {
        $this->requireEsp32Key();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'message'=>'Method not allowed'],405);
        $uid = strtoupper(trim((string)($_POST['rfid_uid'] ?? '')));
        if ($uid === '' || !preg_match('/^[A-F0-9:._-]{4,100}$/i', $uid)) json_response(['ok'=>false,'message'=>'Invalid RFID UID.'],422);
        $card = RfidCardModel::findActiveByUid($uid);
        if (!$card) json_response(['ok'=>false,'message'=>'No active Smart Gate RFID profile exists for this UID.'],404);
        $stmt = Database::pdo()->prepare("UPDATE rfid_cards SET uid=NULL,status='void',voided_by=NULL,voided_at=NOW(),notes=CASE WHEN notes IS NULL OR notes='' THEN 'Voided by ESP32 RC522 reader' ELSE CONCAT(notes,' | Voided by ESP32 RC522 reader') END WHERE id=? AND status='active'");
        $stmt->execute([(int)$card['id']]);
        AccountActivityLogModel::record(null, 'device', 'ESP32 RFID Reader', 'rfid_voided', 'RFID '.$uid.' voided for '.$card['email'].' (profile #'.$card['id'].')');
        json_response(['ok'=>true,'rfid_card_id'=>(int)$card['id'],'uid'=>$uid,'account'=>$card['full_name'],'role'=>$card['role'],'status'=>'void']);
    }

    public function pollGateCommand(): void { $this->requireEsp32Key(); if($_SERVER['REQUEST_METHOD']!=='GET') json_response(['ok'=>false,'message'=>'Method not allowed'],405); json_response(['ok'=>true,'command'=>GateCommandModel::waitingForDevice()]); }
    public function completeGateCommand(): void { $this->requireEsp32Key(); if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['ok'=>false,'message'=>'Method not allowed'],405); $id=(int)($_POST['command_id']??0); if($id<=0||!GateCommandModel::complete($id)) json_response(['ok'=>false,'message'=>'Gate command is no longer pending.'],409); json_response(['ok'=>true,'status'=>'completed','command_id'=>$id]); }

    public function logAccess(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['ok' => false, 'message' => 'Method not allowed'], 405);
        }

        $rfid=trim($_POST['rfid_uid']??''); $qr=trim($_POST['qr_token']??'');
        if($rfid==='' && $qr==='') json_response(['ok'=>false,'message'=>'Only RFID or QR credentials are accepted.'],422);
        $result = GateLogModel::createAccess([
            'rfid_uid' => $rfid,
            'qr_token' => $qr,
            'event_type' => $qr ? 'qr_scan' : 'rfid_scan',
            'source_device' => trim($_POST['source_device'] ?? 'esp32'),
            'raw_payload' => $_POST,
        ]);

        json_response($result);
    }
}



?>
