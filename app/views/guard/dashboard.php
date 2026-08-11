<?php
/* BISM4RCK-KUN3H0 2026 */ include app_path('views/layouts/header.php'); ?>
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h2 class="mb-1">Guard Dashboard</h2><div class="text-muted">Monitor RFID access and manage gate operations.</div></div><span id="rfidListenerStatus" class="badge text-bg-success rounded-pill px-3 py-2"><i class="bi bi-broadcast me-1"></i>RFID LISTENING</span></div>
  <div class="gh-card p-4 mb-4 admin-override-card">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3"><div><h4 class="mb-1"><i class="bi bi-unlock me-2"></i>Gate Control</h4><div class="text-muted">Open the gate manually for an authorized vehicle or emergency.</div></div><span class="badge text-bg-warning rounded-pill px-3 py-2">GUARD OVERRIDE</span></div>
    <form method="post" class="row g-2 align-items-end mt-2"><?=csrf_field()?><input type="hidden" name="action" value="gate_override"><div class="col-md-4"><label class="form-label">Plate Number</label><input class="form-control form-control-lg" name="plate_number" placeholder="ABC 1234"></div><div class="col-md-2"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="emergency" id="guardEmergency" value="1"><label class="form-check-label fw-semibold" for="guardEmergency">EMERGENCY</label></div></div><div class="col-md-4"><label class="form-label">Reason</label><input class="form-control form-control-lg" name="reason" placeholder="Reason / incident"></div><div class="col-md-2"><button class="btn btn-warning btn-lg w-100">Open Gate</button></div></form>
  </div>
  <div class="row g-3 mb-4">
    <div class="col-lg-6">
      <div class="gh-card p-4 h-100">
        <h5><i class="bi bi-person-badge me-2"></i>Visitor ID</h5>
        <div class="text-muted mb-3">Enter a visitor's 6-character credential to check the current request status.</div>
        <form method="post" class="d-flex gap-2"><?=csrf_field()?>
          <input type="hidden" name="action" value="visitor_id_check">
          <input class="form-control form-control-lg text-uppercase" name="visitor_id" maxlength="6" pattern="[A-Za-z0-9]{6}" placeholder="ABC123" required>
          <button class="btn gh-primary btn-lg px-4">Check</button>
        </form>
      </div>
    </div>
  </div>
  <div class="gh-action-grid mb-4"><a class="btn gh-gold gh-action-square" href="<?=e(url('guard/walkin.php'))?>"><i class="bi bi-person-plus"></i>WALK-IN VISITOR</a><a class="btn btn-outline-danger gh-action-square" href="<?=e(url('guard/blacklist.php'))?>"><i class="bi bi-slash-circle"></i>BLACKLIST</a><a class="btn gh-btn-soft gh-action-square" href="<?=e(url('guard/logs.php'))?>"><i class="bi bi-journal-text"></i>GATE LOGS</a><a class="btn gh-btn-soft gh-action-square" href="<?=e(url('guard/activity-logs.php'))?>"><i class="bi bi-person-lines-fill"></i>MY ACTIVITY</a></div>
  <div class="row g-2 gh-small-stats mb-4"><div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Pending</div><div class="value"><?= (int)$stats['pending']?></div></div></div><div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Gate Logs</div><div class="value"><?= (int)$stats['logs']?></div></div></div><div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Tickets</div><div class="value"><?= (int)$stats['tickets']?></div></div></div><div class="col-6 col-md-3"><div class="gh-stat"><div class="label">Vehicles</div><div class="value"><?= (int)$stats['vehicles']?></div></div></div></div>
  <div class="gh-card p-4"><h5>Recent Visitor Requests</h5><div class="table-responsive"><table class="table gh-table"><thead><tr><th>House</th><th>Visitor</th><th>Plate</th><th>Status</th></tr></thead><tbody><?php foreach($requests as $row): ?><tr><td><?=e($row['house_number'])?></td><td><?=e($row['visitor_name'])?></td><td><?=e($row['plate_number'])?></td><td><span class="badge rounded-pill <?=e(gate_badge($row['status']))?>"><?=e($row['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></div>
</div>
<div class="modal fade" id="gateResultModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg"><div class="modal-body text-center p-5"><div id="gateResultIcon" class="display-3 mb-3"></div><h2 id="gateResultTitle" class="fw-bold"></h2><p id="gateResultText" class="text-muted mb-0"></p></div></div></div></div>
<script>
(()=>{
let afterId=<?= (int)$latestGateLogId ?>;
const modalEl=document.getElementById('gateResultModal');
const modal=window.bootstrap&&modalEl?new bootstrap.Modal(modalEl):null;
const title=document.getElementById('gateResultTitle'),text=document.getElementById('gateResultText'),icon=document.getElementById('gateResultIcon'),listener=document.getElementById('rfidListenerStatus');
let fallbackTimer=null;
function popup(kind,heading,message){
 title.textContent=heading;text.textContent=message||'';
 icon.innerHTML=kind==='success'?'<i class=\"bi bi-check-circle-fill text-success\"></i>':kind==='warning'?'<i class=\"bi bi-hourglass-split text-warning\"></i>':'<i class=\"bi bi-x-circle-fill text-danger\"></i>';
 if(modal){modal.show();return;}
 modalEl.classList.add('show');modalEl.style.display='block';modalEl.removeAttribute('aria-hidden');modalEl.setAttribute('aria-modal','true');
 clearTimeout(fallbackTimer);fallbackTimer=setTimeout(()=>{modalEl.classList.remove('show');modalEl.style.display='none';modalEl.setAttribute('aria-hidden','true');},4500);
}
function showEvent(event){const status=String(event.gate_status||'').toLowerCase();popup(status==='approved'?'success':status==='pending'?'warning':'danger',status==='approved'?'GATE OPENED':status==='pending'?'REQUEST STILL PENDING':'DENIED',event.log_notes||'RFID access result received.');}
async function poll(){try{const r=await fetch('<?=e(url('api/live_rfid.php'))?>?after_id='+encodeURIComponent(afterId),{cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});if(!r.ok)throw new Error('HTTP '+r.status);const d=await r.json();if(d.ok&&Array.isArray(d.events)&&d.events.length){d.events.forEach(showEvent);afterId=Number(d.events[d.events.length-1].id);}listener.className='badge text-bg-success rounded-pill px-3 py-2';listener.innerHTML='<i class=\"bi bi-broadcast me-1\"></i>RFID LISTENING';}catch(e){listener.className='badge text-bg-secondary rounded-pill px-3 py-2';listener.innerHTML='<i class=\"bi bi-broadcast-pin me-1\"></i>RFID RECONNECTING';}}
setInterval(poll,750);poll();
const cmdId=<?= (int)$gateCommandId ?>;
if(cmdId){const check=async()=>{try{const r=await fetch('<?=e(url('api/gate_command_status.php'))?>?command_id='+cmdId,{cache:'no-store',credentials:'same-origin'}),d=await r.json();if(d.ok&&d.status==='completed'){popup('success','GATE OPENED','The ESP32 confirmed the gate command.');return;}if(d.ok&&d.status==='expired'){popup('danger','ERROR!','The gate command expired before the gate opened.');return;}setTimeout(check,750);}catch(e){setTimeout(check,1000);}};check();}
})();
</script>
<?php include app_path('views/layouts/footer.php'); ?>