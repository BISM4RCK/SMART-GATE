<?php
/* BISM4RCK-KUN3H0 2026 */ include app_path('views/layouts/header.php'); ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2>RFID Gate Scan</h2><a class="btn btn-outline-secondary rounded-pill" href="<?=e(url('admin/rfid.php'))?>">RFID Management</a></div>
  <div class="row g-3">
    <div class="col-lg-5"><div class="gh-card p-4">
      <h5>RFID</h5><p class="small text-muted">Press SCAN RFID. The system waits for the ESP32 + RC522 and captures the physical UID automatically.</p>
      <button type="button" id="scanRfidBtn" class="btn gh-primary btn-lg w-100 py-4"><i class="bi bi-credit-card-2-front me-2"></i>SCAN RFID</button>
      <div id="rfidStatus" class="alert alert-secondary d-none mt-3" role="status" aria-live="polite"></div>
    </div></div>
    <div class="col-lg-7"><div class="gh-card p-4"><h5>Recent Gate Logs</h5><div class="table-responsive"><table class="table gh-table"><thead><tr><th>Time</th><th>Event</th><th>Status</th><th>Notes</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?=e($log['created_at'])?></td><td><?=e($log['event_type'])?></td><td><span class="badge rounded-pill <?=e(gate_badge($log['gate_status']))?>"><?=e($log['gate_status'])?></span></td><td><?=e($log['log_notes'])?></td></tr><?php endforeach;?></tbody></table></div></div></div>
  </div>
</div>
<script>
(()=>{const b=document.getElementById('scanRfidBtn'),s=document.getElementById('rfidStatus');let timer=null,id=null;const show=(t,k)=>{s.className='alert alert-'+k+' mt-3';s.textContent=t;s.classList.remove('d-none');};const stop=()=>{if(timer)clearInterval(timer);timer=null;b.disabled=false;};const poll=async()=>{try{const r=await fetch('<?=e(url('admin/rfid-scan-result.php'))?>?session_id='+encodeURIComponent(id),{headers:{Accept:'application/json'},cache:'no-store'}),d=await r.json();if(!d.ok){stop();show('ERROR! '+d.message,'danger');return;}if(d.status==='waiting'){show('Waiting for RFID scan from the ESP32...','info');return;}stop();if(d.status==='approved')show('GATE OPENED — '+(d.result?.notes||'RFID accepted.'),'success');else show('ERROR! '+(d.result?.notes||d.message||'RFID was not accepted.'),'danger');}catch(e){stop();show('ERROR! Could not communicate with Smart Gate.','danger');}};b.addEventListener('click',async()=>{stop();b.disabled=true;show('Starting RFID scan...','info');const f=new FormData();f.append('action','start_rfid_scan');f.append('csrf_token','<?=e(csrf_token())?>');try{const r=await fetch('<?=e(url('admin/rfid-scan.php'))?>',{method:'POST',body:f,headers:{Accept:'application/json'},cache:'no-store'}),d=await r.json();if(!d.ok)throw new Error(d.message||'Unable to start scan.');id=d.session_id;poll();timer=setInterval(poll,1000);}catch(e){stop();show('ERROR! '+e.message,'danger');}});})();
</script>
<?php include app_path('views/layouts/footer.php'); ?>
