'use strict';
(function(){
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 const toast=document.querySelector('#toast');
 let toastTimeout;
 function showToast(message,isError=false){
   if(!toast)return;
   toast.textContent=message;toast.classList.toggle('error',isError);toast.classList.add('show');
   clearTimeout(toastTimeout);toastTimeout=setTimeout(()=>toast.classList.remove('show'),3500);
 }
 document.querySelector('[data-menu-toggle]')?.addEventListener('click',()=>{
   const panel=document.querySelector('[data-mobile-panel]');if(panel)panel.hidden=!panel.hidden;
 });
 document.querySelector('[data-admin-toggle]')?.addEventListener('click',()=>document.querySelector('[data-admin-sidebar]')?.classList.toggle('open'));
 document.querySelectorAll('[data-dismiss-notice]').forEach(b=>b.addEventListener('click',()=>b.closest('.notice')?.remove()));
 document.querySelectorAll('[data-qty-minus],[data-qty-plus]').forEach(b=>b.addEventListener('click',()=>{
   const input=b.parentElement?.querySelector('input[type="number"]');if(!input)return;
   const value=Number(input.value)||1;const min=Number(input.min)||1;const max=Number(input.max)||100;
   input.value=Math.min(max,Math.max(min,value+(b.hasAttribute('data-qty-plus')?1:-1)));
 }));
 document.querySelectorAll('form[data-ajax-cart]').forEach(form=>form.addEventListener('submit',async(event)=>{
   if(!window.fetch||!csrf)return;
   event.preventDefault(); const button=form.querySelector('button[type="submit"]');if(button?.disabled)return;
   button?.setAttribute('disabled','disabled');
   try{
     const response=await fetch(form.action,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:new FormData(form),credentials:'same-origin'});
     const data=await response.json();
     if(!response.ok){showToast(data.message||Object.values(data.errors||{})[0]?.[0]||'Không thể thêm sản phẩm.',true);return;}
     document.querySelectorAll('[data-cart-count]').forEach(el=>el.textContent=data.cart_count);
     showToast('✓ '+(data.message||'Đã thêm vào giỏ hàng.'));
   }catch(_){showToast('Kết nối có vấn đề. Vui lòng thử lại.',true)}
   finally{button?.removeAttribute('disabled');}
 }));
 document.querySelectorAll('form[data-ajax-qty]').forEach(form=>form.addEventListener('submit',async(event)=>{
   if(!window.fetch||!csrf)return;
   event.preventDefault();const button=form.querySelector('button[type="submit"]');
   if(button)button.disabled=true;
   try{
     const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}});
     const data=await response.json();
     if(!response.ok){showToast(data.message||Object.values(data.errors||{})[0]?.[0]||'Không thể cập nhật.',true);return;}
     const format=v=>new Intl.NumberFormat('vi-VN').format(v)+'₫';
     const line=document.querySelector(`[data-cart-line-total="${data.product_id}"]`);
     if(line)line.textContent=format(data.line_total);
     const sub=document.querySelector('[data-cart-subtotal]');if(sub)sub.textContent=format(data.subtotal);
     const ship=document.querySelector('[data-cart-shipping]');if(ship)ship.textContent=data.shipping===0?'Miễn phí':format(data.shipping);
     const grand=document.querySelector('[data-cart-grand-total]');if(grand)grand.textContent=format(data.total);
     const hint=document.querySelector('[data-shipping-hint]');if(hint){hint.hidden=data.subtotal>=800000;hint.textContent=`Mua thêm ${format(800000-data.subtotal)} để được miễn phí giao hàng.`;}
     document.querySelectorAll('[data-cart-count]').forEach(el=>el.textContent=data.cart_count);
     showToast('✓ Giỏ hàng đã cập nhật.');
   }catch(_){showToast('Kết nối có vấn đề. Vui lòng thử lại.',true)}finally{if(button)button.disabled=false;}
 }));
 const provinceSelect=document.querySelector('[data-province-select]');
 if(provinceSelect){
   fetch(provinceSelect.dataset.url,{headers:{Accept:'application/json'}}).then(r=>r.ok?r.json():Promise.reject()).then(data=>{
     provinceSelect.innerHTML='<option value="">Chọn tỉnh/thành (không bắt buộc)</option>';
     (data.provinces||[]).forEach(name=>{const opt=document.createElement('option');opt.value=String(name);opt.textContent=String(name);provinceSelect.appendChild(opt);});
   }).catch(()=>{provinceSelect.innerHTML='<option value="">Không tải được gợi ý — nhập địa chỉ bên dưới</option>';});
   provinceSelect.addEventListener('change',()=>{
     const field=document.querySelector('#shipping_address');if(!field||!provinceSelect.value)return;
     const current=field.value.trim();
     if(!current.includes(provinceSelect.value))field.value=current?current.replace(/[,\s]+$/,'')+', '+provinceSelect.value:provinceSelect.value;
   });
 }
 document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
   if(!window.confirm(form.dataset.confirm||'Bạn chắc chắn muốn thực hiện?'))event.preventDefault();
 }));
 const upload=document.querySelector('[data-image-preview]');
 upload?.addEventListener('change',()=>{
   const file=upload.files?.[0];const target=document.getElementById(upload.dataset.imagePreview);
   if(file&&target){if(!file.type.startsWith('image/'))return showToast('Vui lòng chọn tệp ảnh.',true);target.src=URL.createObjectURL(file);}
 });
 document.querySelectorAll('[data-submit-once]').forEach(btn=>btn.closest('form')?.addEventListener('submit',()=>{
   // Cho phép validation HTML chạy trước, sau đó khóa thao tác gửi lặp.
   btn.disabled=true;setTimeout(()=>{btn.disabled=false},8000);
 }));
})();
