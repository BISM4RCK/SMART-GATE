<?php
/* BISM4RCK-KUN3H0 2026 */
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'gate_override') {
            csrf_validate();
            $plate = strtoupper(trim($_POST['plate_number'] ?? ''));
            $emergency = !empty($_POST['emergency']);
            if ($plate === '' && !$emergency) { flash_set('danger', 'Enter a plate number or tick Emergency.'); redirect('guard/dashboard.php'); }
            $reason = trim($_POST['reason'] ?? '') ?: ($emergency ? 'Emergency manual override' : 'Manual gate override');
            $me = current_user();
            $cmd = GateCommandModel::create((int)$me['id'], 'guard', 'open_gate', 'guard-dashboard', ['plate_number'=>$plate, 'emergency'=>$emergency, 'reason'=>$reason]);
            GateLogModel::createAccess(['event_type'=>'manual_open','source_device'=>'guard-dashboard','manual_override'=>1,'plate_number'=>$plate,'guard_id'=>(int)$me['id'],'actor_user_id'=>(int)$me['id'],'actor_role'=>'guard','raw_payload'=>['command_id'=>$cmd,'plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]]);
            activity_log('gate_override', 'Gate opened manually. Command '.$cmd.' / '.($plate ?: 'EMERGENCY'));
            flash_set('success', 'Gate override issued and logged.');
            redirect('guard/dashboard.php');
        }
        View::render('guard/dashboard', ['pageTitle'=>'Guard Dashboard','stats'=>['pending'=>count_rows("SELECT COUNT(*) c FROM visitor_requests WHERE status = 'pending'"),'logs'=>count_rows("SELECT COUNT(*) c FROM gate_logs"),'tickets'=>TicketModel::openCount(),'vehicles'=>count_rows("SELECT COUNT(*) c FROM vehicles")],'requests'=>array_slice(VisitorRequestModel::all(),0,6),'logs'=>array_slice(GateLogModel::recent(8),0,8)]);
    }

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

    public function scan(): void
    {
        require_role(['guard','admin']);
        $me=current_user(); $target=$me['role']==='admin'?'admin/scan.php':'guard/scan.php';
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            csrf_validate();
            $rfid=trim($_POST['rfid_uid']??''); $qr=trim($_POST['qr_token']??''); $barcode=trim($_POST['barcode_token']??'');
            if($rfid===''&&$qr===''&&$barcode===''){flash_set('warning','Scan an RFID, QR, or barcode credential.');redirect($target);}
            $result=GateLogModel::createAccess(['rfid_uid'=>$rfid,'qr_token'=>$qr,'barcode_token'=>$barcode,'event_type'=>$qr?'qr_scan':($barcode?'barcode_scan':'rfid_scan'),'source_device'=>$me['role']==='admin'?'admin-panel':'guard-panel','guard_id'=>$me['role']==='guard'?(int)$me['id']:null,'actor_user_id'=>(int)$me['id'],'actor_role'=>$me['role'],'raw_payload'=>$_POST]);
            activity_log('gate_scan',$result['notes']);
            flash_set(in_array($result['gate_status'],['approved','manual_override'],true)?'success':($result['gate_status']==='pending'?'warning':'danger'),$result['notes']);
            redirect($target);
        }
        View::render('guard/scan',['pageTitle'=>'Quick Scan','logs'=>GateLogModel::recent(10),'requests'=>VisitorRequestModel::all()]);
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
                    $result=GateLogModel::createAccess(['visitor_id'=>$lookup['visitor_id'],'barcode_token'=>$lookup['barcode_token'],'event_type'=>'walk_in_checkin','source_device'=>$me['role']==='admin'?'admin-walkin':'guard-walkin','actor_user_id'=>(int)$me['id'],'actor_role'=>$me['role'],'guard_id'=>$me['role']==='guard'?(int)$me['id']:null,'raw_payload'=>$_POST]);
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
        if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='gate_override'){
            csrf_validate();$plate=strtoupper(trim($_POST['plate_number']??''));$emergency=!empty($_POST['emergency']);
            if($plate===''&&!$emergency){flash_set('danger','Enter a plate number or tick Emergency.');redirect('admin/dashboard.php');}
            $reason=trim($_POST['reason']??'')?:($emergency?'Emergency manual override':'Manual gate override');$me=current_user();
            $cmd=GateCommandModel::create((int)$me['id'],'admin','open_gate','admin-dashboard',['plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]);
            GateLogModel::createAccess(['event_type'=>'manual_open','source_device'=>'admin-dashboard','manual_override'=>1,'plate_number'=>$plate,'actor_user_id'=>(int)$me['id'],'actor_role'=>'admin','raw_payload'=>['command_id'=>$cmd,'plate_number'=>$plate,'emergency'=>$emergency,'reason'=>$reason]]);
            activity_log('gate_override','Gate opened manually. Command '.$cmd.' / '.($plate?:'EMERGENCY'));flash_set('success','Gate override issued and logged.');redirect('admin/dashboard.php');
        }
        View::render('admin/dashboard',['pageTitle'=>'Admin Dashboard','stats'=>['residents'=>count_rows('SELECT COUNT(*) c FROM residents'),'requests'=>count_rows('SELECT COUNT(*) c FROM visitor_requests'),'tickets'=>TicketModel::openCount(),'logs'=>count_rows('SELECT COUNT(*) c FROM gate_logs')],'tickets'=>array_slice(TicketModel::all(),0,5),'logs'=>array_slice(GateLogModel::recent(8),0,8)]);
    }
    public function scan(): void { require_role('admin'); (new GuardController())->scan(); }
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
        require_role('admin');$filters=['event_type'=>trim($_GET['event_type']??''),'gate_status'=>trim($_GET['gate_status']??''),'actor_user_id'=>trim($_GET['actor_user_id']??''),'search'=>trim($_GET['search']??'')];
        View::render('admin/logs',['pageTitle'=>'Gate Logs','logs'=>GateLogModel::all($filters),'filters'=>$filters,'actors'=>array_merge(UserModel::byRole('guard'),UserModel::byRole('admin'))]);
    }
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
                }elseif($action==='delete_user'){$userId=(int)($_POST['user_id']??0);if($userId===(int)$me['id'])throw new RuntimeException('You cannot remove your own admin account.');$target=UserModel::findById($userId);if(!$target)throw new RuntimeException('User not found.');UserModel::delete($userId);activity_log('account_deleted','Account '.$target['email']);flash_set('success','User removed.');}
            }catch(Throwable $e){if(Database::pdo()->inTransaction())Database::pdo()->rollBack();flash_set('danger',$e->getMessage());}
            redirect('admin/users.php');
        }
        View::render('admin/users',['pageTitle'=>'Account Management','residents'=>ResidentModel::all(),'guards'=>UserModel::byRole('guard'),'admins'=>UserModel::byRole('admin')]);
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
