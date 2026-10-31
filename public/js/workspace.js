'use strict';
(() => {
    const menus=[...document.querySelectorAll('[data-account-menu]')];
    document.addEventListener('click',event=>menus.forEach(menu=>{if(!menu.contains(event.target))menu.open=false;}));
    document.addEventListener('keydown',event=>{if(event.key==='Escape')menus.forEach(menu=>{if(menu.open){menu.open=false;menu.querySelector('summary')?.focus();}});});
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input=>{
        const preview=document.createElement('div');
        preview.className='workspace-image-preview';
        input.after(preview);
        let urls=[];
        input.addEventListener('change',()=>{
            urls.forEach(URL.revokeObjectURL);
            urls=[];
            preview.replaceChildren();
            [...input.files].slice(0,6).filter(file=>file.type.startsWith('image/')).forEach(file=>{
                const img=document.createElement('img');
                img.alt='Ảnh đã chọn: '+file.name;
                img.src=URL.createObjectURL(file);
                urls.push(img.src);
                preview.append(img);
            });
        });
    });
})();

document.querySelectorAll('[data-report-filter]').forEach(form=>{
    form.querySelectorAll('[data-report-date]').forEach(input=>input.addEventListener('change',()=>{
        form.querySelector('[data-report-period]').value='custom';
    }));
});
(() => {const price=document.querySelector('input[name="price"]');const cost=document.querySelector('input[name="cost_price"]');if(!price||!cost)return;const note=document.createElement('small');note.className='text-warning';note.setAttribute('role','status');note.textContent='Giá bán đang thấp hơn giá vốn. Bạn vẫn có thể lưu nếu đây là giá khuyến mãi.';price.closest('label').append(note);const update=()=>{note.hidden=!(price.value!==''&&cost.value!==''&&Number(price.value)<Number(cost.value));};price.addEventListener('input',update);cost.addEventListener('input',update);update();})();
