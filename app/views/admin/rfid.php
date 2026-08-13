<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
$roleLabel=function($role){return $role==='resident'?'Resident':ucfirst($role);};
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h2 class="mb-1">RFID Management</h2><div class="text-muted">Program, review, and void RFID credentials for resident and staff accounts.</div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-1"><i class="bi bi-credit-card-2-front me-2"></i>Burn / Program RFID Profile</h5>
                <div class="small text-muted mb-3">Choose an account, then present its card to the ESP32 + RC522 reader.</div>
                <form method="post" id="rfidBurnForm" class="d-grid gap-3">
                    <?=csrf_field()?>
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="rfid_uid" id="rfidUid" value="">
                    <div>
                        <label class="form-label" for="rfidAccount">Resident / Staff Profile</label>
                        <select class="form-select" id="rfidAccount" name="user_id" required>
                            <option value="">Select account...</option>
                            <?php foreach($accounts as $account): ?>
                                <option value="<?=e($account['id'])?>"><?=e('#'.$account['id'].' — '.$roleLabel($account['role']).' — '.$account['full_name'].' — '.$account['email'])?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="residentVehicleWrap" class="d-none">
                        <label class="form-label" for="rfidVehicle">Account Vehicle <span class="text-danger">*</span></label>
                        <select class="form-select" id="rfidVehicle" name="vehicle_id">
                            <option value="">Select the vehicle for this RFID card...</option>
                        </select>
                        <div class="small text-muted mt-1">Select the vehicle that this RFID card will authorize.</div>
                    </div>
                    <div class="gh-card-soft p-3">
                        <strong>Generated Smart Gate RFID ID</strong><br><code id="generatedRfidCode">Select an account</code>
                        <div class="small text-muted mt-1">Residents: <code>res00Block-Lot-Letter-PLATE</code> · Admins: <code>adm-AdminNumber-PLATE</code> · Guards: <code>grd-GuardNumber-PLATE</code>.</div>
                    </div>
                    <div class="small text-muted">The physical RC522 UID is captured automatically by the ESP32. Every RFID card must be linked to an account vehicle.</div>
                    <div>
                        <label class="form-label" for="rfidNotes">Notes <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="rfidNotes" name="notes" rows="2" placeholder="Card issue, replacement, reason, etc."></textarea>
                    </div>
                    <button class="btn gh-primary btn-lg" id="startRfidBurn" type="submit"><i class="bi bi-broadcast me-2"></i>START RFID BURN</button>
                    <div id="rfidBurnStatus" class="d-none" aria-live="polite"></div>
                </form>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-1">Credential Rules</h5>
                <ul class="small text-muted mb-4 mt-3">
                    <li>Each account can have up to 20 active RFID cards.</li><li>Each resident vehicle and staff vehicle can have up to 2 active RFID cards.</li><li>Resident accounts can have up to 10 vehicles; each staff account can have up to 2 vehicles.</li>
                    <li>Replacing a card retires the previous active credential.</li>
                    <li>Void credentials cannot open the gate.</li>
                    <li>Program and void actions are recorded in Admin / Guard Logs.</li>
                </ul>
                <div class="border-top pt-4">
                    <h5 class="mb-2">How to burn a card</h5>
                    <ol class="mb-0 ps-3">
                        <li class="mb-2">Select the resident or staff account.</li>
                        <li class="mb-2">Confirm the generated Smart Gate RFID ID.</li>
                        <li class="mb-2">Click <strong>START RFID BURN</strong>.</li>
                        <li class="mb-2">Wait for the ESP32 + RC522 to request a card.</li>
                        <li class="mb-2">Place the RFID card on the RC522 reader.</li>
                        <li>Wait for <strong>Card updated</strong>, then return to RFID Management.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="gh-card p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h5 class="mb-1">RFID Credentials</h5><div class="small text-muted">Review active and retired RFID profiles.</div></div>
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-3"><select class="form-select" name="account_type"><option value="">All profiles</option><option value="resident" <?=$filters['account_type']==='resident'?'selected':''?>>Residents</option><option value="staff" <?=$filters['account_type']==='staff'?'selected':''?>>Staff</option></select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option value="">All status</option><option value="active" <?=$filters['status']==='active'?'selected':''?>>Active</option><option value="void" <?=$filters['status']==='void'?'selected':''?>>Void</option></select></div>
            <div class="col-md-5"><input class="form-control" name="search" value="<?=e($filters['search'])?>" placeholder="Account name, email, or RFID UID"></div>
            <div class="col-md-2"><button class="btn gh-primary w-100">Filter</button></div>
        </form>
        <div class="table-responsive">
            <table class="table gh-table align-middle">
                <thead><tr><th>Account</th><th>Type</th><th>Smart Gate ID</th><th>RFID UID</th><th>Vehicle</th><th>Status</th><th>Issued</th><th>Voided</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($cards as $card): ?>
                    <tr>
                        <td><strong><?=e($card['full_name'])?></strong><div class="small text-muted"><?=e($card['email'])?></div></td>
                        <td><span class="badge text-bg-<?=$card['role']==='resident'?'primary':'dark'?>"><?=e($roleLabel($card['role']))?></span></td>
                        <td><code><?=e($card['credential_code'] ?? '—')?></code></td>
                        <td><code><?=e($card['uid'] ?? 'NULL / VOID')?></code></td>
                        <td><?=e($card['vehicle_plate'] ?? '—')?></td>
                        <td><span class="badge rounded-pill <?=$card['status']==='active'?'text-bg-success':'text-bg-secondary'?>"><?=e(strtoupper($card['status']))?></span></td>
                        <td><div><?=e($card['issued_at'] ?? '—')?></div><div class="small text-muted"><?=e($card['issued_by_name'] ?? '—')?></div></td>
                        <td><div><?=e($card['voided_at'] ?? '—')?></div><div class="small text-muted"><?=e($card['voided_by_name'] ?? '—')?></div></td>
                        <td>
                            <?php if($card['status']==='active'): ?>
                                <form method="post" onsubmit="return confirm('Void this RFID profile? The UID will no longer validate at the gate.');">
                                    <?=csrf_field()?>
                                    <input type="hidden" name="action" value="void">
                                    <input type="hidden" name="rfid_card_id" value="<?=e($card['id'])?>">
                                    <input type="hidden" name="notes" value="Voided from RFID Management">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-slash-circle me-1"></i>Void RFID</button>
                                </form>
                            <?php else: ?><span class="text-muted small">Retired</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($cards)): ?><tr><td colspan="9" class="text-center text-muted py-4">No RFID profiles match the selected filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(()=>{
  const select=document.getElementById('rfidAccount'),vehicleSelect=document.getElementById('rfidVehicle'),vehicleWrap=document.getElementById('residentVehicleWrap'),code=document.getElementById('generatedRfidCode'),form=document.getElementById('rfidBurnForm'),button=document.getElementById('startRfidBurn'),statusBox=document.getElementById('rfidBurnStatus');
  if(!select||!code||!form||!button||!statusBox)return;
  const codes={},roles={};
  <?php foreach($accounts as $account): $house=''; if(($account['role']??'')==='resident'){ $house=preg_replace('/\s+/','', (string)($account['house_number']??'')); } $prefix=($account['role']??'')==='resident'?'res00'.$house:(($account['role']??'')==='admin'?'adm-'.(int)$account['id']:'grd-'.(int)$account['id']); ?>codes['<?=e($account['id'])?>']='<?=e($prefix)?>';roles['<?=e($account['id'])?>']='<?=e($account['role'])?>';<?php endforeach; ?>
  const updateCode=()=>{
    const baseCode=codes[select.value]||'Select an account';
    const selected=vehicleSelect.options[vehicleSelect.selectedIndex];
    const plate=(selected?.dataset?.plate||'').replace(/[^A-Za-z0-9]/g,'').toUpperCase();
    code.textContent=plate&&baseCode!=='Select an account'?baseCode+'-'+plate:baseCode;
  };
  const loadVehicles=async()=>{
    const selectedRole=roles[select.value]||'';
    const hasAccount=!!select.value;
    vehicleWrap.classList.toggle('d-none',!hasAccount);
    vehicleSelect.required=hasAccount;
    vehicleSelect.disabled=!hasAccount;
    vehicleSelect.innerHTML='<option value="">Select the vehicle for this RFID card...</option>';
    if(!hasAccount){updateCode();return;}
    vehicleSelect.disabled=true;
    vehicleSelect.innerHTML='<option value="">Loading vehicles...</option>';
    try{
      const response=await fetch('<?=e(url('admin/rfid-vehicles.php'))?>?user_id='+encodeURIComponent(select.value),{headers:{'Accept':'application/json'},cache:'no-store'});
      const data=await response.json();
      if(!data.ok)throw new Error(data.message||'Could not load this resident\'s vehicles.');
      vehicleSelect.innerHTML='<option value="">Select the vehicle for this RFID card...</option>';
      data.vehicles.forEach(v=>{
        const o=document.createElement('option');
        o.value=v.id;
        o.dataset.plate=v.plate_number||'';
        o.textContent=v.plate_number+' — '+(v.vehicle_type||'Vehicle')+(v.color?' — '+v.color:'');
        vehicleSelect.appendChild(o);
      });
      if(!data.vehicles.length){
        const o=document.createElement('option');
        o.value='';
        o.textContent='No active vehicles found for this account';
        vehicleSelect.appendChild(o);
      }
    }catch(err){
      vehicleSelect.innerHTML='<option value="">Unable to load vehicles</option>';
      showStatus('danger','Vehicle list unavailable.',err.message||'Could not load the selected resident\'s vehicles.');
    }finally{
      vehicleSelect.disabled=false;
      updateCode();
    }
  };
  const showStatus=(kind,title,message,withBack=false)=>{statusBox.className='mt-2 p-3 rounded-4 '+(kind==='success'?'bg-success-subtle text-success-emphasis':kind==='danger'?'bg-danger-subtle text-danger-emphasis':'bg-light');statusBox.innerHTML='<strong>'+title+'</strong><div class="small mt-1">'+message+'</div>'+(withBack?'<a class="btn btn-sm btn-outline-success mt-3" href="<?=e(url('admin/rfid.php'))?>">Back to RFID Management</a>':'');};
  select.addEventListener('change',loadVehicles);
  vehicleSelect.addEventListener('change',updateCode);
  loadVehicles();
  form.addEventListener('submit',async(e)=>{
    e.preventDefault();
    if(!select.value){showStatus('danger','Select an account.','Choose a resident or staff account before starting.');return;} if(roles[select.value]==='resident'&&!vehicleSelect.value){showStatus('danger','Select a vehicle.','Choose the resident vehicle that will use this RFID card.');return;}
    button.disabled=true; button.textContent='WAITING FOR RFID...'; showStatus('info','Waiting for card.','The ESP32 + RC522 is waiting for the physical RFID card.');
    try{
      const res=await fetch('<?=e(url('admin/rfid.php'))?>',{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'},cache:'no-store'});
      const data=await res.json(); if(!data.ok)throw new Error(data.message||'Could not start RFID burn.');
      const poll=async()=>{
        const r=await fetch('<?=e(url('admin/rfid-result.php'))?>?session_id='+encodeURIComponent(data.session_id),{headers:{'Accept':'application/json'},cache:'no-store'});
        const d=await r.json(); if(!d.ok)throw new Error(d.message||'Unable to check RFID burn.');
        if(d.status==='waiting'){showStatus('info','Waiting for card.','Present the RFID card on the RC522 reader.');setTimeout(poll,500);return;}
        if(d.status==='approved'){button.disabled=false;button.innerHTML='<i class="bi bi-check-circle me-2"></i>CARD UPDATED';showStatus('success','Card updated successfully.','RFID '+(d.result?.credential_code||data.credential_code)+' is now assigned to '+(d.result?.account||'the selected account')+'. The ESP32 confirmed the card write.',true);return;}
        if(d.status==='error'){
          button.disabled=false;
          button.innerHTML='<i class="bi bi-broadcast me-2"></i>START RFID BURN';
          const failedMessage=d.result?.burn_failed_message||d.result?.message||d.message||'RFID card didn’t burn, try again!';
          showStatus('danger','RFID card didn’t burn, try again!',failedMessage,true);
          return;
        }
        throw new Error(d.result?.notes||d.message||'RFID burn failed.');
      };
      await poll();
    }catch(err){
      button.disabled=false;
      button.innerHTML='<i class="bi bi-broadcast me-2"></i>START RFID BURN';
      const message=err.message||'The RFID card could not be updated.';
      showStatus('danger','Card update failed.',message);
      if(message==='You have reached the limit for this vehicle!'){
        window.setTimeout(()=>window.alert(message),50);
      }
    }
  });
})();
</script>
<?php include app_path('views/layouts/footer.php'); ?>
