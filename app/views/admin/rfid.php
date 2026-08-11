<?php
/* BISM4RCK-KUN3H0 2026 */
include app_path('views/layouts/header.php');
$roleLabel=function($role){return $role==='resident'?'Resident':ucfirst($role);};
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h2 class="mb-1">RFID Management</h2><div class="text-muted">Assign an RFID UID to a resident or staff profile, review active credentials, and void credentials when a card is retired.</div></div>
    </div>

    <div class="alert alert-secondary shadow-sm">
        <strong>ESP32 + RC522:</strong> The project ZIP includes <code>esp32/SmartGate_RFID_RC522/SmartGate_RFID_RC522.ino</code>. Set Wi-Fi, the Smart Gate base URL, and the device key, then open it in Arduino IDE. In Serial Monitor use <code>BURN &lt;account_id&gt;</code> to scan and assign a card, or <code>VOID</code> to scan and retire the card. The card's factory UID is not rewritten; the sketch clears a writable data block and Smart Gate voids the server credential.
    </div>

    <div class="alert alert-info shadow-sm">
        <strong>Hardware note:</strong> this page manages the RFID credential in Smart Gate and records the programming/voiding action. Physical card writing or UID rewriting still requires compatible RFID writer hardware; many RFID cards have manufacturer-locked UIDs.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-1"><i class="bi bi-credit-card-2-front me-2"></i>Burn / Program RFID Profile</h5>
                <div class="small text-muted mb-3">Select the account profile that this scanned UID should validate against.</div>
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
                    <div class="alert alert-info mb-0">
                        <strong>Generated Smart Gate RFID ID:</strong> <code id="generatedRfidCode">Select an account</code>
                        <div class="small mt-1">Residents use <code>res00Block-Lot-Letter</code>; admins use <code>adm00AccountNumber</code>; guards use <code>grd00AccountNumber</code>.</div>
                    </div>
                    <div class="alert alert-secondary mb-0">The physical RC522 UID is captured automatically by the ESP32. It is not entered here.</div>
                    <div>
                        <label class="form-label" for="rfidNotes">Notes <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="rfidNotes" name="notes" rows="2" placeholder="Card issue, replacement, reason, etc."></textarea>
                    </div>
                    <button class="btn gh-primary btn-lg" id="startRfidBurn" type="submit"><i class="bi bi-broadcast me-2"></i>START RFID BURN</button>
                </form>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="gh-card p-4 h-100">
                <h5 class="mb-1">Credential rules</h5>
                <ul class="small text-muted mb-0 mt-3">
                    <li>Active RFID profiles are valid for the selected account.</li>
                    <li>Voiding removes the UID from the active credential and prevents gate validation.</li>
                    <li>Every program and void operation is recorded in Admin / Guard Logs.</li>
                    <li>Resident and staff accounts can both receive RFID profiles.</li>
                    <li>Void credentials remain in the history so administrators can audit retired cards.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="gh-card p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div><h5 class="mb-1">RFID Credentials</h5><div class="small text-muted">Filter by profile type, status, or account/UID.</div></div>
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-3"><select class="form-select" name="account_type"><option value="">All profiles</option><option value="resident" <?=$filters['account_type']==='resident'?'selected':''?>>Residents</option><option value="staff" <?=$filters['account_type']==='staff'?'selected':''?>>Staff</option></select></div>
            <div class="col-md-2"><select class="form-select" name="status"><option value="">All status</option><option value="active" <?=$filters['status']==='active'?'selected':''?>>Active</option><option value="void" <?=$filters['status']==='void'?'selected':''?>>Void</option></select></div>
            <div class="col-md-5"><input class="form-control" name="search" value="<?=e($filters['search'])?>" placeholder="Account name, email, or RFID UID"></div>
            <div class="col-md-2"><button class="btn gh-primary w-100">Filter</button></div>
        </form>
        <div class="table-responsive">
            <table class="table gh-table align-middle">
                <thead><tr><th>Account</th><th>Type</th><th>Smart Gate ID</th><th>RFID UID</th><th>Status</th><th>Issued</th><th>Voided</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($cards as $card): ?>
                    <tr>
                        <td><strong><?=e($card['full_name'])?></strong><div class="small text-muted"><?=e($card['email'])?></div></td>
                        <td><span class="badge text-bg-<?=$card['role']==='resident'?'primary':'dark'?>"><?=e($roleLabel($card['role']))?></span></td>
                        <td><code><?=e($card['credential_code'] ?? '—')?></code></td>
                        <td><code><?=e($card['uid'] ?? 'NULL / VOID')?></code></td>
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
                <?php if(empty($cards)): ?><tr><td colspan="8" class="text-center text-muted py-4">No RFID profiles match the selected filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(()=>{
  const select=document.getElementById('rfidAccount'),code=document.getElementById('generatedRfidCode'),form=document.getElementById('rfidBurnForm'),button=document.getElementById('startRfidBurn');
  if(!select||!code||!form||!button)return;
  const codes={};
  <?php foreach($accounts as $account): $house=''; if(($account['role']??'')==='resident'){ $house=preg_replace('/\s+/','', (string)($account['house_number']??'')); } $prefix=($account['role']??'')==='resident'?'res00'.$house:(($account['role']??'')==='admin'?'adm00'.(int)$account['id']:'grd00'.(int)$account['id']); ?>codes['<?=e($account['id'])?>']='<?=e($prefix)?>';<?php endforeach; ?>
  const update=()=>{code.textContent=codes[select.value]||'Select an account';}; select.addEventListener('change',update); update();
  form.addEventListener('submit',async(e)=>{
    e.preventDefault(); if(!select.value){alert('Select an account first.');return;}
    button.disabled=true; button.textContent='WAITING FOR RFID...';
    const status=document.createElement('div'); status.className='alert alert-info mt-2'; status.textContent='Waiting for the ESP32 + RC522. Present the card when the reader is ready.'; form.appendChild(status);
    try{
      const res=await fetch('<?=e(url('admin/rfid.php'))?>',{method:'POST',body:new FormData(form),headers:{'Accept':'application/json'},cache:'no-store'}); const data=await res.json();
      if(!data.ok)throw new Error(data.message||'Could not start RFID burn.');
      const poll=async()=>{const r=await fetch('<?=e(url('admin/rfid-result.php'))?>?session_id='+encodeURIComponent(data.session_id),{headers:{'Accept':'application/json'},cache:'no-store'});const d=await r.json();if(!d.ok)throw new Error(d.message||'Unable to check RFID burn.');if(d.status==='waiting'){status.textContent='Waiting for RFID card from ESP32...';setTimeout(poll,1000);return;} if(d.status==='approved'){status.className='alert alert-success mt-2';status.textContent='RFID BURNED: '+(d.result?.credential_code||data.credential_code)+' — '+(d.result?.account||'account');setTimeout(()=>location.reload(),900);}else{throw new Error(d.result?.notes||d.message||'RFID burn failed.');}}; await poll();
    }catch(err){status.className='alert alert-danger mt-2';status.textContent='ERROR! '+err.message;button.disabled=false;button.innerHTML='<i class="bi bi-broadcast me-2"></i>START RFID BURN';}
  });
})();
</script>
<?php include app_path('views/layouts/footer.php'); ?>
