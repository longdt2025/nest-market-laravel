(() => {
    const watcher = document.querySelector('[data-payment-watch]');
    if (!watcher) return;
    let stopped = false;
    let failures = 0;
    const poll = async () => {
        if (stopped) return;
        if (document.hidden) { setTimeout(poll, 8000); return; }
        try {
            const response = await fetch(watcher.dataset.statusUrl, {headers:{Accept:'application/json'},cache:'no-store'});
            if ([401,403,404].includes(response.status) || response.redirected) { stopped = true; return; }
            if (!response.ok) throw new Error('unavailable');
            const data = await response.json();
            failures = 0;
            if (data.status === 'paid') {
                stopped = true;
                document.dispatchEvent(new CustomEvent('nest:notify',{detail:{message:'Thanh toán thành công! Cửa hàng đã xác nhận nhận tiền.',type:'success'}}));
                setTimeout(() => location.reload(), 2200);
                return;
            }
            if (data.status !== watcher.dataset.initialStatus) { location.reload(); return; }
        } catch (_) { failures++; }
        setTimeout(poll, Math.min(30000,8000 * (failures+1)));
    };
    setTimeout(poll, 8000);
})();
