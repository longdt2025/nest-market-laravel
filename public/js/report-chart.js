'use strict';
document.querySelectorAll('[data-report-chart]').forEach(root=>{
    const svg=root.querySelector('svg');
    const mask=svg.querySelector('.report-reveal-mask');
    const reduced=matchMedia('(prefers-reduced-motion: reduce)');
    if(!reduced.matches && mask?.animate) {
        mask.style.transformOrigin='0px 0px';
        mask.animate([{transform:'scaleX(0)'},{transform:'scaleX(1)'}],{duration:1100,easing:'cubic-bezier(.22,1,.36,1)'});
    }
    const points=[...svg.querySelectorAll('[data-report-point]')].map(el=>({el,x:Number(el.dataset.x),y:Number(el.dataset.y),label:el.dataset.label,value:Number(el.dataset.value)}));
    const guide=svg.querySelector('.report-crosshair');
    const tooltip=root.querySelector('[data-report-tooltip]');
    const formatter=new Intl.NumberFormat('vi-VN');
    const hide=()=>{guide.setAttribute('hidden','');tooltip.hidden=true;};
    const show=point=>{
        guide.removeAttribute('hidden');
        const vx=guide.querySelector('[data-guide-x]'),hy=guide.querySelector('[data-guide-y]'),dot=guide.querySelector('[data-guide-dot]');
        vx.setAttribute('x1',point.x);vx.setAttribute('x2',point.x);
        hy.setAttribute('y1',point.y);hy.setAttribute('y2',point.y);
        dot.setAttribute('cx',point.x);dot.setAttribute('cy',point.y);
        tooltip.querySelector('strong').textContent=point.label;
        tooltip.querySelector('span').textContent=root.dataset.seriesLabel+': '+formatter.format(point.value)+' '+root.dataset.unit;
        tooltip.hidden=false;
        const scale=svg.getBoundingClientRect().width/1000;
        const left=Math.max(root.scrollLeft+8,Math.min(point.x*scale+18,root.scrollLeft+root.clientWidth-tooltip.offsetWidth-8));
        tooltip.style.left=left+'px';
        tooltip.style.top=Math.max(8,point.y*scale-tooltip.offsetHeight-18)+'px';
    };
    const move=event=>{
        const matrix=svg.getScreenCTM();
        if(!matrix)return;
        const cursor=svg.createSVGPoint();cursor.x=event.clientX;cursor.y=event.clientY;
        const position=cursor.matrixTransform(matrix.inverse());
        if(position.x<80||position.x>980||position.y<30||position.y>320){hide();return;}
        let nearest=points[0];
        points.forEach(point=>{if(Math.abs(point.x-position.x)<Math.abs(nearest.x-position.x))nearest=point;});
        if(nearest)show(nearest);
    };
    svg.addEventListener('pointermove',move,{passive:true});
    svg.addEventListener('pointerdown',move,{passive:true});
    svg.addEventListener('pointerleave',hide);
    points.forEach(point=>point.el.addEventListener('focus',()=>show(point)));
    svg.addEventListener('focusout',event=>{if(!svg.contains(event.relatedTarget))hide();});
    svg.addEventListener('keydown',event=>{if(event.key==='Escape')hide();});
});
