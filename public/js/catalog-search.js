(() => {
    const root = document.querySelector('.nest-catalog');
    if (!root) return;
    let pending;
    let debounce;
    let version = 0;
    const notify = message => document.dispatchEvent(new CustomEvent('nest:notify',{detail:{message,error:true}}));
    const load = async (url, historyMode = 'push') => {
        pending?.abort();
        const controller = new AbortController(); pending = controller;
        const current = ++version;
        root.setAttribute('aria-busy','true');
        try {
            const response = await fetch(url,{signal:controller.signal,headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'}});
            if (!response.ok || response.redirected) throw new Error('response');
            const page = new DOMParser().parseFromString(await response.text(),'text/html');
            const next = page.querySelector('.nest-catalog');
            if (!next) throw new Error('content');
            if (current !== version) return;
            const focused = root.contains(document.activeElement) ? document.activeElement : null;
            const focusName = focused?.name;
            const selection = focused?.type === 'text' ? [focused.selectionStart,focused.selectionEnd] : null;
            const filterOpen = root.querySelector('.nest-filters details')?.open;
            root.innerHTML = next.innerHTML;
            if (root.querySelector('.nest-filters details')) root.querySelector('.nest-filters details').open = filterOpen;
            if (focusName) {
                const input = root.querySelector(`.nest-catalog-toolbar [name="${CSS.escape(focusName)}"]`);
                input?.focus({preventScroll:true});
                if (selection && input?.setSelectionRange) input.setSelectionRange(...selection);
            }
            document.title = page.title;
            if (historyMode === 'push') history.pushState(null,'',url);
            if (historyMode === 'replace') history.replaceState(null,'',url);
        } catch (error) {
            if (error.name !== 'AbortError' && current === version) notify('Không tải được kết quả. Vui lòng thử tìm lại.');
        } finally { if (current === version) root.removeAttribute('aria-busy'); }
    };
    const searchUrl = (form) => {
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        url.searchParams.delete('page');
        return url;
    };
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!root.contains(form) || !form.matches('.nest-catalog-toolbar,.nest-filter-form')) return;
        event.preventDefault(); clearTimeout(debounce);
        load(searchUrl(form));
    });
    root.addEventListener('input',event => {
        if (!event.target.matches('.nest-catalog-search input[name=q]') || event.isComposing) return;
        clearTimeout(debounce);
        pending?.abort(); ++version; root.removeAttribute('aria-busy');
        debounce = setTimeout(() => load(searchUrl(event.target.form),'replace'),400);
    });
    root.addEventListener('compositionend',event => { if(event.target.matches('.nest-catalog-search input[name=q]')) event.target.dispatchEvent(new Event('input',{bubbles:true})); });
    root.addEventListener('click',event => {
        const link = event.target.closest('.pagination-wrap a,.nest-clear-filter,a.btn-dark');
        if (!link || !root.contains(link) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin || !['/san-pham','/hang-moi','/uu-dai'].includes(url.pathname)) return;
        event.preventDefault(); clearTimeout(debounce); load(url);
    });
    window.addEventListener('popstate',() => {clearTimeout(debounce);load(location.href,'none');});
})();
