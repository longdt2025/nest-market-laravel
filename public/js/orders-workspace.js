(() => {
    const root = document.querySelector('[data-order-workspace]');
    if (!root) return;
    const all = root.querySelector('[data-order-all]');
    const boxes = [...root.querySelectorAll('[data-order-select]')];
    const selected = () => boxes.filter(box => box.checked).map(box => box.value);
    const sync = () => {
        const ids = selected();
        all.checked = boxes.length > 0 && ids.length === boxes.length;
        all.indeterminate = ids.length > 0 && ids.length < boxes.length;
        root.querySelector('[data-order-selected-count]').textContent = ids.length ? `· Đã chọn ${ids.length}` : '';
    };
    all.addEventListener('change', () => { boxes.forEach(box => { box.checked = all.checked; }); sync(); });
    boxes.forEach(box => box.addEventListener('change', sync));
    root.querySelector('[data-order-page-size]').addEventListener('change', () => root.querySelector('#order-filters').requestSubmit());
    let saved = {};
    try { saved = JSON.parse(localStorage.getItem('nest-order-columns-v1') || '{}') || {}; } catch (_) {}
    root.querySelectorAll('[data-order-column]').forEach(toggle => {
        const key = toggle.dataset.orderColumn;
        if (typeof saved[key] === 'boolean') toggle.checked = saved[key];
        const apply = () => root.querySelectorAll(`[data-order-col="${key}"]`).forEach(cell => { cell.hidden = !toggle.checked; });
        apply();
        toggle.addEventListener('change', () => {
            if (![...root.querySelectorAll('[data-order-column]')].some(box => box.checked)) toggle.checked = true;
            saved[key] = toggle.checked;
            try { localStorage.setItem('nest-order-columns-v1', JSON.stringify(saved)); } catch (_) {}
            apply();
        });
    });
    const exportDialog = root.querySelector('[data-order-export-dialog]');
    root.querySelector('[data-order-export]').addEventListener('click', () => {
        const ids = selected();
        root.querySelector('[data-order-selected-scope]').disabled = ids.length === 0;
        if (!ids.length) exportDialog.querySelector('[value="filtered"]').checked = true;
        root.querySelector('[data-order-export-count]').textContent = `(${ids.length})`;
        root.querySelector('[data-order-export-ids]').replaceChildren(...ids.map(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            return input;
        }));
        exportDialog.showModal();
    });
    root.querySelector('[data-order-export-form]').addEventListener('submit', () => exportDialog.close());
    root.querySelectorAll('[data-order-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    root.querySelectorAll('dialog').forEach(dialog => dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    }));
    const drawer = root.querySelector('[data-order-drawer]');
    const content = root.querySelector('[data-order-content]');
    let pending;
    drawer.addEventListener('close', () => { pending?.abort(); });
    root.querySelectorAll('[data-order-detail]').forEach(link => link.addEventListener('click', async event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        pending?.abort();
        const controller = new AbortController(); pending = controller;
        content.textContent = 'Đang tải chi tiết đơn hàng…';
        content.setAttribute('aria-busy', 'true');
        drawer.showModal();
        drawer.scrollTop = 0;
        try {
            const url = new URL(link.href); url.searchParams.set('panel', '1');
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'text/html' } });
            if (!response.ok || response.redirected) throw new Error('load');
            const html = await response.text();
            if (!controller.signal.aborted) content.innerHTML = html;
        } catch (error) {
            if (error.name === 'AbortError') return;
            content.replaceChildren(document.createTextNode('Không tải được chi tiết. '));
            const fallback = document.createElement('a'); fallback.href = link.href; fallback.textContent = 'Mở trang chi tiết'; content.append(fallback);
        } finally {
            if (pending === controller) content.removeAttribute('aria-busy');
        }
    }));
})();
