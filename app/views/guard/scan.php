<?php
/* BISM4RCK-KUN3H0 2026 */ include app_path('views/layouts/header.php'); ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3"><h2>Gate Scan</h2></div>
  <div class="row g-3">
    <div class="col-lg-5">
      <div class="gh-card p-4">
        <h5>RFID / QR / Barcode</h5>
        <p class="small text-muted">For RFID, press SCAN RFID. The system waits for the ESP32 + RC522 reader; you do not enter the UID manually.</p>
        <button type="button" id="scanRfidBtn" class="btn gh-primary btn-lg w-100 py-4 mb-3"><i class="bi bi-credit-card-2-front me-2"></i>SCAN RFID</button>
        <div id="rfidStatus" class="alert alert-secondary d-none" role="status" aria-live="polite"></div>
        <form method="post" class="d-grid gap-3">
          <?=csrf_field()?>
          <div class="text-center text-muted">or scan a visitor credential</div>
          <div><label>Visitor QR Token</label><input class="form-control" name="qr_token" placeholder="Scan QR here"></div>
          <div><label>Visitor Barcode Token</label><input class="form-control" name="barcode_token" placeholder="Scan barcode here"></div>
          <button class="btn btn-outline-primary rounded-pill py-3">Validate QR / Barcode</button>
        </form>
      </div>
    </div>
    <div class="col-lg-7"><div class="gh-card p-4"><h5>Recent Gate Logs</h5><div class="table-responsive"><table class="table gh-table"><thead><tr><th>Time</th><th>Event</th><th>Status</th><th>Notes</th></tr></thead><tbody><?php foreach($logs as $log): ?><tr><td><?=e($log['created_at'])?></td><td><?=e($log['event_type'])?></td><td><span class="badge rounded-pill <?=e(gate_badge($log['gate_status']))?>"><?=e($log['gate_status'])?></span></td><td><?=e($log['log_notes'])?></td></tr><?php endforeach;?></tbody></table></div></div></div>
  </div>
</div>
<script>
(() => {
  const button=document.getElementById('scanRfidBtn'), status=document.getElementById('rfidStatus');
  if(!button||!status)return;
  let timer=null, sessionId=null;
  const show=(text,kind='secondary')=>{status.className='alert alert-'+kind;status.textContent=text;status.classList.remove('d-none');};
  const stop=()=>{if(timer){clearInterval(timer);timer=null;}button.disabled=false;};
  const poll=async()=>{
    try{
      const res=await fetch('<?=e(url('guard/scan-result.php'))?>?session_id='+encodeURIComponent(sessionId),{headers:{'Accept':'application/json'},cache:'no-store'});
      const data=await res.json();
      if(!data.ok){stop();show('ERROR! '+(data.message||'Unable to check scan.'),'danger');return;}
      if(data.status==='waiting'){show('Waiting for RFID scan from the ESP32...','info');return;}
      stop();
      if(data.status==='approved'){show('GATE OPENED — '+(data.result?.notes||'RFID accepted.'),'success');}
      else{show('ERROR! '+(data.result?.notes||data.message||'RFID was not accepted.'),'danger');}
    }catch(e){stop();show('ERROR! Could not communicate with the Smart Gate server.','danger');}
  };
  button.addEventListener('click',async()=>{
    stop(); button.disabled=true; show('Starting RFID scan...','info');
    const fd=new FormData(); fd.append('action','start_rfid_scan'); fd.append('csrf_token','<?=e(csrf_token())?>');
    try{
      const res=await fetch('<?=e(url('guard/scan.php'))?>',{method:'POST',body:fd,headers:{'Accept':'application/json'},cache:'no-store'}); const data=await res.json();
      if(!data.ok){stop();show('ERROR! '+(data.message||'Could not start scan.'),'danger');return;}
      sessionId=data.session_id; show('Waiting for RFID scan from the ESP32...','info'); poll(); timer=setInterval(poll,1000);
    }catch(e){stop();show('ERROR! Could not start RFID scan.','danger');}
  });
})();
</script>
<?php include app_path('views/layouts/footer.php'); ?>
