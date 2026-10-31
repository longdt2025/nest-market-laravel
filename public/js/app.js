'use strict';
(function(){
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 const toast=document.querySelector('#toast');
 const toastTemplate=document.querySelector('#toast-template');
 const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)');
 function setBusy(form,button,busy){
   form.dataset.busy=String(busy);
   if(button){button.disabled=busy;button.setAttribute('aria-busy',String(busy));}
 }
 function updateCartCount(count){
   document.querySelectorAll('[data-cart-count]').forEach(el=>{
     el.textContent=count;el.classList.add('cart-count-updated');
     setTimeout(()=>el.classList.remove('cart-count-updated'),450);
   });
 }
 function activateToast(card){
   const duration=card.dataset.type==='error'?8000:4500;
   let remaining=duration,started=0,timer=null,closing=false;
   card.style.setProperty('--toast-duration',duration+'ms');
   function close(){
     if(closing)return;
     closing=true;clearTimeout(timer);card.classList.remove('show');
     card.setAttribute('aria-hidden','true');
     setTimeout(()=>card.remove(),220);
   }
   function pause(){
     if(closing)return;
     if(timer!==null){clearTimeout(timer);timer=null;remaining=Math.max(0,remaining-(Date.now()-started));}
     card.classList.add('is-paused');
   }
   function resume(){
     if(closing||timer!==null)return;
     if(card.matches(':hover')||card.contains(document.activeElement)){pause();return;}
     card.classList.remove('is-paused');started=Date.now();timer=setTimeout(close,remaining);
   }
   card.querySelector('[data-toast-close]')?.addEventListener('click',close);
   card.addEventListener('pointerenter',pause);
   card.addEventListener('pointerleave',resume);
   card.addEventListener('focusin',pause);
   card.addEventListener('focusout',event=>{if(!card.contains(event.relatedTarget))queueMicrotask(resume);});
   requestAnimationFrame(()=>{card.classList.add('show');resume();});
 }
 function showToast(message,isError=false){
   if(!toast||!toastTemplate)return;
   const card=toastTemplate.content.firstElementChild.cloneNode(true);
   const type=isError?'error':'success';
   card.dataset.type=type;card.classList.add('toast-'+type);
   card.classList.toggle('toast-success',!isError);
   card.setAttribute('role',isError?'alert':'status');
   card.querySelector('[data-toast-title]').textContent=isError?'Vui lòng kiểm tra lại':'Thành công';
   card.querySelector('[data-toast-icon]').textContent=isError?'!':'✓';
   card.querySelector('[data-toast-message]').textContent=String(message);
   toast.appendChild(card);activateToast(card);
 }
 document.addEventListener('nest:notify',event=>showToast(event.detail.message,event.detail.error===true));
 toast?.querySelectorAll('[data-toast]').forEach(activateToast);
 const mobileToggle=document.querySelector('[data-menu-toggle]');
 const mobilePanel=document.querySelector('[data-mobile-panel]');
 let mobileAnimation;
 mobileToggle?.addEventListener('click',()=>{
   if(!mobilePanel)return;
   const open=mobileToggle.getAttribute('aria-expanded')!=='true';
   mobileToggle.setAttribute('aria-expanded',String(open));
   mobileAnimation?.cancel();
   if(open)mobilePanel.hidden=false;
   if(reducedMotion.matches||!mobilePanel.animate){mobilePanel.hidden=!open;return;}
   mobileAnimation=mobilePanel.animate(open?[{opacity:0,transform:'translateY(-8px)'},{opacity:1,transform:'translateY(0)'}]:[{opacity:1,transform:'translateY(0)'},{opacity:0,transform:'translateY(-8px)'}],{duration:180,easing:'ease-out'});
   mobileAnimation.finished.then(()=>{if(!open)mobilePanel.hidden=true;}).catch(()=>{});
 });
 const adminToggle=document.querySelector('[data-admin-toggle]');
 const adminSidebar=document.querySelector('[data-admin-sidebar]');
 const adminBackdrop=document.querySelector('[data-admin-backdrop]');
 function setAdminMenu(open){
   adminSidebar?.classList.toggle('open',open);
   adminToggle?.setAttribute('aria-expanded',String(open));
   if(adminBackdrop)adminBackdrop.hidden=!open;
 }
 adminToggle?.addEventListener('click',()=>setAdminMenu(!adminSidebar?.classList.contains('open')));
 document.querySelector('[data-admin-close]')?.addEventListener('click',()=>{setAdminMenu(false);adminToggle?.focus();});
 adminBackdrop?.addEventListener('click',()=>setAdminMenu(false));
 document.addEventListener('keydown',event=>{
   if(event.key==='Escape'&&adminSidebar?.classList.contains('open')){setAdminMenu(false);adminToggle?.focus();}
 });
 document.querySelectorAll('[data-qty-minus],[data-qty-plus]').forEach(b=>b.addEventListener('click',()=>{
   const input=b.parentElement?.querySelector('input[type="number"]');if(!input)return;
   const value=Number(input.value)||1;const min=Number(input.min)||1;const max=Number(input.max)||100;
   input.value=Math.min(max,Math.max(min,value+(b.hasAttribute('data-qty-plus')?1:-1)));
 }));
 document.querySelectorAll('form[data-ajax-cart]').forEach(form=>form.addEventListener('submit',async(event)=>{
   if(!window.fetch||!csrf)return;
   event.preventDefault(); const button=event.submitter||form.querySelector('button[type="submit"],button:not([type])');if(form.dataset.busy==='true')return;
   const buyNow=button?.hasAttribute('data-buy-now');
   setBusy(form,button,true);
   try{
     const response=await fetch(form.action,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},body:new FormData(form),credentials:'same-origin'});
     const data=await response.json();
     if(!response.ok){showToast(data.message||Object.values(data.errors||{})[0]?.[0]||'Không thể thêm sản phẩm.',true);return;}
     updateCartCount(data.cart_count);
     if(buyNow){window.location.assign(button.dataset.checkoutUrl);return;}
     showToast(data.message||'Đã thêm vào giỏ hàng.');
   }catch(_){showToast('Kết nối có vấn đề. Vui lòng thử lại.',true)}
   finally{setBusy(form,button,false);}
 }));
 document.querySelectorAll('form[data-ajax-qty]').forEach(form=>form.addEventListener('submit',async(event)=>{
   if(!window.fetch||!csrf)return;
   event.preventDefault();const button=form.querySelector('button[type="submit"]');
   if(form.dataset.busy==='true')return;
   setBusy(form,button,true);
   try{
     const response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}});
     const data=await response.json();
     if(!response.ok){showToast(data.message||Object.values(data.errors||{})[0]?.[0]||'Không thể cập nhật.',true);return;}
     if(data.cart_adjusted){window.location.reload();return;}
     const format=v=>new Intl.NumberFormat('vi-VN').format(v)+'₫';
     const line=document.querySelector(`[data-cart-line-total="${data.product_id}"]`);
     if(line){line.textContent=format(data.line_total);line.classList.add('value-updated');setTimeout(()=>line.classList.remove('value-updated'),450);}
     const sub=document.querySelector('[data-cart-subtotal]');if(sub)sub.textContent=format(data.subtotal);
     const ship=document.querySelector('[data-cart-shipping]');if(ship)ship.textContent=data.shipping===0?'Miễn phí':format(data.shipping);
     const grand=document.querySelector('[data-cart-grand-total]');if(grand)grand.textContent=format(data.total);
     const hint=document.querySelector('[data-shipping-hint]');if(hint){hint.hidden=data.subtotal>=800000;hint.textContent=`Mua thêm ${format(800000-data.subtotal)} để được miễn phí giao hàng.`;}
     updateCartCount(data.cart_count);
     showToast('Giỏ hàng đã cập nhật.');
   }catch(_){showToast('Kết nối có vấn đề. Vui lòng thử lại.',true)}finally{setBusy(form,button,false);}
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
 const confirmDialog=document.querySelector('#confirm-dialog');
 const approvedForms=new WeakSet();
 let pendingSubmission=null;
 document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{
   if(approvedForms.has(form)){approvedForms.delete(form);return;}
   if(!confirmDialog?.showModal){if(!window.confirm(form.dataset.confirm||'Bạn chắc chắn muốn thực hiện?'))event.preventDefault();return;}
   event.preventDefault();
   pendingSubmission={form,submitter:event.submitter};
   confirmDialog.querySelector('[data-confirm-message]').textContent=form.dataset.confirm||'Bạn chắc chắn muốn thực hiện?';
   confirmDialog.showModal();
 }));
 confirmDialog?.querySelector('[data-confirm-cancel]')?.addEventListener('click',()=>confirmDialog.close());
 confirmDialog?.addEventListener('cancel',()=>{pendingSubmission=null;});
 confirmDialog?.addEventListener('close',()=>{pendingSubmission=null;});
 confirmDialog?.querySelector('[data-confirm-accept]')?.addEventListener('click',()=>{
   const submission=pendingSubmission;if(!submission)return;
   pendingSubmission=null;confirmDialog.close();
   approvedForms.add(submission.form);
   submission.form.requestSubmit(submission.submitter||undefined);
 });
 const upload=document.querySelector('[data-image-preview]');
 upload?.addEventListener('change',()=>{
   const file=upload.files?.[0];const target=document.getElementById(upload.dataset.imagePreview);
   if(file&&target){if(!file.type.startsWith('image/'))return showToast('Vui lòng chọn tệp ảnh.',true);target.src=URL.createObjectURL(file);}
 });
 const onceForms=new Set([...document.querySelectorAll('[data-submit-once]')].map(btn=>btn.form).filter(Boolean));
 onceForms.forEach(form=>form.addEventListener('submit',event=>{
   if(event.defaultPrevented)return;
   if(form.dataset.submitting==='true'){event.preventDefault();return;}
   form.dataset.submitting='true';
   [...form.elements].filter(el=>el.hasAttribute('data-submit-once')).forEach(btn=>{btn.disabled=true;btn.setAttribute('aria-busy','true');});
 }));
 // Restore buttons when navigating back from a completed checkout.
 window.addEventListener('pageshow',()=>onceForms.forEach(form=>{
   delete form.dataset.submitting;
   [...form.elements].filter(el=>el.hasAttribute('data-submit-once')).forEach(btn=>{btn.disabled=false;btn.setAttribute('aria-busy','false');});
 }));
 if(!reducedMotion.matches&&'IntersectionObserver' in window){
   const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{
     if(entry.isIntersecting){
       entry.target.classList.add('is-revealed');observer.unobserve(entry.target);
       setTimeout(()=>{entry.target.classList.remove('reveal-pending','is-revealed');entry.target.style.removeProperty('--reveal-delay');},650);
     }
   }),{threshold:0.08});
   document.querySelectorAll('.product-card,.category-tile,.info-block,.stat-card,.user-order-card').forEach((el,index)=>{
     if(el.getBoundingClientRect().top>window.innerHeight){el.classList.add('reveal-pending');el.style.setProperty('--reveal-delay',(index%3)*25+'ms');observer.observe(el);}
   });
 }
})();
