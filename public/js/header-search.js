(() => {
    const form = document.querySelector('.nest-search');
    if (!form) return;
    const input = form.querySelector('input[name=q]');
    const panel = document.createElement('div');
    panel.className = 'nest-search-suggestions'; panel.id = 'header-search-results'; panel.hidden = true;
    panel.setAttribute('aria-label','Kết quả tìm kiếm sản phẩm');
    form.append(panel);
    input.maxLength = 100; input.autocomplete = 'off';
    input.setAttribute('aria-controls',panel.id); input.setAttribute('aria-expanded','false');
    let timer, pending, version = 0;
    const close = () => {
        clearTimeout(timer); pending?.abort(); ++version;
        panel.hidden = true; input.setAttribute('aria-expanded','false');
        panel.removeAttribute('aria-busy');
    };
    const message = text => {const p=document.createElement('p');p.textContent=text;panel.replaceChildren(p);};
    const search = async () => {
        const term = input.value.trim();
        if (!term) { close(); return; }
        pending?.abort(); const controller = new AbortController(); pending = controller;
        const current = ++version;
        const url = new URL(form.action); url.searchParams.set('q',term);
        panel.hidden = false; panel.setAttribute('aria-busy','true'); input.setAttribute('aria-expanded','true');
        message('Đang tìm kiếm…');
        try {
            const response = await fetch(url,{signal:controller.signal,headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok || response.redirected) throw new Error('response');
            const page = new DOMParser().parseFromString(await response.text(),'text/html');
            if (!page.querySelector('.nest-catalog')) throw new Error('content');
            if (current !== version) return;
            const cards = [...page.querySelectorAll('.nest-catalog-grid .product-card')].slice(0,6);
            panel.replaceChildren();
            if (!cards.length) message('Không tìm thấy sản phẩm phù hợp.');
            cards.forEach(card => {
                const name = card.querySelector('.product-name');
                const source = card.querySelector('.product-image img');
                const link = document.createElement('a'); link.href = name.href;
                const image = document.createElement('img'); image.src = source.src; image.alt = '';
                const info = document.createElement('span');
                const title = document.createElement('strong'); title.textContent = name.textContent;
                const price = document.createElement('small'); price.textContent = card.querySelector('.product-price strong')?.textContent;
                info.append(title,price); link.append(image,info); panel.append(link);
            });
            const all = document.createElement('a'); all.className = 'nest-search-view-all'; all.href = url;
            all.textContent = 'Xem tất cả kết quả'; panel.append(all);
        } catch (error) {
            if (error.name !== 'AbortError' && current === version) message('Không tải được kết quả. Bấm tìm kiếm để thử lại.');
        } finally { if (current === version) panel.removeAttribute('aria-busy'); }
    };
    const schedule = () => {close();if(input.value.trim()) timer=setTimeout(search,400);};
    input.addEventListener('input',event=>{if(!event.isComposing)schedule();});
    input.addEventListener('compositionend',schedule);
    form.addEventListener('submit',event=>{event.preventDefault();clearTimeout(timer);search();});
    form.addEventListener('keydown',event=>{
        if(event.key==='Escape'){close();input.focus();}
        if(event.key==='ArrowDown' && event.target===input && !panel.hidden){event.preventDefault();panel.querySelector('a')?.focus();}
    });
    document.addEventListener('click',event=>{if(!form.contains(event.target))close();});
    form.addEventListener('focusout',()=>setTimeout(()=>{if(!form.contains(document.activeElement))close();},0));
})();
