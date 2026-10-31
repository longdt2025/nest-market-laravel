'use strict';
(() => {
 const root=document.querySelector('[data-product-workspace]');
 if(!root)return;
 const notify=(message,error=false)=>document.dispatchEvent(new CustomEvent('nest:notify',{detail:{message,error}}));
 const selected=()=>[...root.querySelectorAll('[data-product-select]:checked')].map(input=>input.value);
 const fillIds=(container,ids)=>{container.replaceChildren();ids.forEach(id=>{const input=document.createElement('input');input.type='hidden';input.name='ids[]';input.value=id;container.append(input);});};
 const selectionChanged=()=>{
   const count=selected().length,all=[...root.querySelectorAll('[data-product-select]')],master=root.querySelector('[data-select-all]');
   master.checked=all.length>0&&count===all.length;master.indeterminate=count>0&&count<all.length;
   root.querySelector('[data-selected-count]').textContent=count?'· Đã chọn '+count:'';
   root.querySelectorAll('[data-bulk]').forEach(button=>button.disabled=count===0);
 };
 root.querySelectorAll('[data-product-select]').forEach(input=>input.addEventListener('change',selectionChanged));
 root.querySelector('[data-select-all]')?.addEventListener('change',event=>{root.querySelectorAll('[data-product-select]').forEach(input=>input.checked=event.target.checked);selectionChanged();});
 root.querySelector('[data-page-size]')?.addEventListener('change',event=>event.target.form.requestSubmit());
 root.querySelectorAll('[data-bulk]').forEach(button=>button.addEventListener('click',()=>{
   const form=root.querySelector('[data-bulk-form]');form.elements.action.value=button.dataset.bulk;
   fillIds(form.querySelector('[data-bulk-ids]'),selected());form.requestSubmit();
 }));
 const toggles=[...root.querySelectorAll('[data-column-toggle]')];
 const key='nest-product-columns-v1';
 try {const stored=JSON.parse(localStorage.getItem(key));if(Array.isArray(stored))toggles.forEach(input=>input.checked=stored.includes(input.dataset.columnToggle));} catch {}
 const columnsChanged=()=>{toggles.forEach(input=>root.querySelectorAll('[data-col="'+input.dataset.columnToggle+'"]').forEach(cell=>cell.hidden=!input.checked));try{localStorage.setItem(key,JSON.stringify(toggles.filter(input=>input.checked).map(input=>input.dataset.columnToggle)));}catch{}};
 toggles.forEach(input=>input.addEventListener('change',columnsChanged));columnsChanged();
 root.querySelectorAll('.product-toolbar-menu').forEach(menu=>document.addEventListener('click',event=>{if(!menu.contains(event.target))menu.open=false;}));
 const importDialog=root.querySelector('[data-import-dialog]'),exportDialog=root.querySelector('[data-export-dialog]');
 root.querySelectorAll('[data-close-dialog]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
 [importDialog,exportDialog].forEach(dialog=>dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}}));
 root.querySelector('[data-open-import]').addEventListener('click',()=>importDialog.showModal());
 root.querySelector('[data-open-export]').addEventListener('click',()=>{
   const ids=selected(),radio=root.querySelector('[data-export-selected]');
   radio.disabled=!ids.length;root.querySelector('[data-export-count]').textContent=ids.length?'('+ids.length+')':'';
   if(!ids.length)root.querySelector('[data-export-form] input[value="filtered"]').checked=true;
   fillIds(root.querySelector('[data-export-ids]'),ids);exportDialog.showModal();
 });
 root.querySelector('[data-export-form]').addEventListener('submit',()=>exportDialog.close());
 const config=root.querySelector('[data-transfer-config]').dataset,csrf=document.querySelector('meta[name="csrf-token"]').content;
 const post=async(url,body)=>{
   const form=body instanceof FormData;
   const response=await fetch(url,{method:'POST',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf,...(form?{}:{'Content-Type':'application/json'})},body:form?body:JSON.stringify(body)});
   if(!(response.headers.get('content-type')||'').includes('application/json'))throw new Error('Phiên đăng nhập đã hết hạn. Vui lòng tải lại trang.');
   const data=await response.json();
   if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||data.message||'Không xử lý được file. Vui lòng thử lại.');
   return data;
 };
 let token=null,version=0,busy=false,valid=false;
 const fileInput=root.querySelector('[data-excel-file]'),sheet=root.querySelector('[data-import-sheet]'),check=root.querySelector('[data-check-import]'),commit=root.querySelector('[data-commit-import]'),preview=root.querySelector('[data-import-preview]');
 const invalidate=()=>{valid=false;commit.hidden=true;preview.hidden=true;};
 const scan=async file=>{
   const current=++version;token=null;invalidate();sheet.replaceChildren(new Option('--Chọn sheet--',''));sheet.disabled=true;check.disabled=true;
   if(!file)return;
   root.querySelector('[data-file-name]').textContent=file.name;
   if(!file.name.toLowerCase().endsWith('.xlsx')||file.size>5*1024*1024){notify('Chọn file .xlsx không quá 5 MB.',true);return;}
   fileInput.disabled=true;
   try{
     const body=new FormData;body.append('file',file);
     const data=await post(config.scan,body);if(current!==version)return;
     token=data.token;data.sheets.forEach(name=>sheet.add(new Option(name,name)));sheet.disabled=false;
     notify('Đã đọc file. Chọn sheet dữ liệu để kiểm tra.');
   }catch(error){notify(error.message,true);}finally{fileInput.disabled=false;}
 };
 fileInput.addEventListener('change',()=>scan(fileInput.files[0]));
 const zone=root.querySelector('[data-dropzone]');
 ['dragenter','dragover'].forEach(name=>zone.addEventListener(name,event=>{event.preventDefault();zone.classList.add('is-dragging');}));
 ['dragleave','drop'].forEach(name=>zone.addEventListener(name,event=>{event.preventDefault();zone.classList.remove('is-dragging');}));
 zone.addEventListener('drop',event=>{if(!busy)scan(event.dataTransfer.files[0]);});
 sheet.addEventListener('change',()=>{++version;invalidate();check.disabled=!sheet.value||!token;});
 check.addEventListener('click',async()=>{
   if(!token||!sheet.value||busy)return;
   busy=true;check.disabled=true;check.setAttribute('aria-busy','true');commit.hidden=true;
   const current=version;
   try{
     const data=await post(config.preview,{token,sheet:sheet.value});if(current!==version)return;
     root.querySelector('[data-preview-summary]').textContent=data.total+' dòng · Thêm mới: '+data.created+' · Cập nhật: '+data.updated+' · Dòng lỗi: '+data.errors;
     const tbody=root.querySelector('[data-preview-rows]');tbody.replaceChildren();
     data.rows.forEach(row=>{
       const tr=document.createElement('tr');
       [row.line,row.code||'Tự sinh',row.name,new Intl.NumberFormat('vi-VN').format(row.price)+'₫',row.stock,row.action==='create'?'Thêm mới':'Cập nhật',row.errors.length?row.errors.join(' '):'Hợp lệ'].forEach((value,index)=>{const td=document.createElement('td');td.textContent=value;if(index===6)td.className=row.errors.length?'text-danger':'product-valid-cell';tr.append(td);});
       tbody.append(tr);
     });
     preview.hidden=false;valid=data.errors===0;commit.hidden=!valid;
     if(!valid)notify('File có '+data.errors+' dòng lỗi. Sửa file rồi chọn lại để kiểm tra.',true);
   }catch(error){notify(error.message,true);}finally{busy=false;check.disabled=!token||!sheet.value;check.removeAttribute('aria-busy');}
 });
 commit.addEventListener('click',async()=>{
   if(!valid||!token||busy)return;
   busy=true;commit.disabled=true;commit.setAttribute('aria-busy','true');
   try{await post(config.commit,{token});location.reload();}
   catch(error){notify(error.message,true);valid=false;commit.hidden=true;}
   finally{busy=false;commit.disabled=false;commit.removeAttribute('aria-busy');}
 });
})();
