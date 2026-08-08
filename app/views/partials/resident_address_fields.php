<?php /* BISM4RCK/KUN3H0 2026 */ ?>
<div id="resident-address-fields" class="row g-3 d-none">
 <div class="col-md-4"><label class="form-label">Block Number</label><input id="resident_block" name="resident_block" class="form-control" inputmode="numeric"></div>
 <div class="col-md-4"><label class="form-label">Lot Number</label><input id="resident_lot" name="resident_lot" class="form-control" inputmode="numeric"></div>
 <div class="col-md-4"><label class="form-label">Household Letter <span class="text-muted">(optional)</span></label><input id="resident_letter" name="resident_letter" class="form-control text-uppercase" maxlength="1" pattern="[A-Za-z]"></div>
 <div class="col-12"><div class="alert alert-light border"><strong>House Number:</strong> <span id="resident-house-preview">Enter block and lot.</span><input type="hidden" name="house_number" id="resident_house_number"></div></div>
</div>
<script>
(()=>{const t=document.querySelector('[name="account_type"],[name="role"]'),s=document.getElementById('resident-address-fields');if(!t||!s)return;
const b=document.getElementById('resident_block'),l=document.getElementById('resident_lot'),x=document.getElementById('resident_letter'),h=document.getElementById('resident_house_number'),p=document.getElementById('resident-house-preview');
function u(){let r=String(t.value||'').toLowerCase()==='resident',B=b.value.trim(),L=l.value.trim(),X=x.value.trim().toUpperCase();s.classList.toggle('d-none',!r);b.required=l.required=r;x.required=false;if(!r){h.value='';return}if(!B||!L){h.value='';p.textContent='Block-Lot-Letter';return}h.value=B+'-'+L+(X?'-'+X:'');p.textContent=h.value}
t.addEventListener('change',u);[b,l,x].forEach(e=>e.addEventListener('input',u));u()})();
</script>
<?php /* BISM4RCK/KUN3H0 2026 */ ?>
/* BISM4RCK-KUN3H0 2026 */
