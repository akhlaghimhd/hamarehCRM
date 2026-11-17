(function () {
  'use strict';
  const map = {'0':'۰','1':'۱','2':'۲','3':'۳','4':'۴','5':'۵','6':'۶','7':'۷','8':'۸','9':'۹',
               '٠':'۰','١':'۱','٢':'۲','٣':'۳','٤':'۴','٥':'۵','٦':'۶','٧':'۷','٨':'۸','٩':'۹'};
  function toFa(s){ return String(s).replace(/[0-9٠-٩]/g, d => map[d] ?? d); }
  function skip(el){
    if(!el||el.disabled) return true;
    const t=(el.getAttribute('type')||'text').toLowerCase();
    if(['email','password','url','hidden','file','checkbox','radio','color','range','number'].includes(t)) return true;
    if(el.closest('.fi-fo-date-time-picker-panel')) return true;
    return false;
  }
  function convertInput(el){
    if(skip(el)||typeof el.value!=='string'||!/[0-9]/.test(el.value)) return;
    const next=toFa(el.value); if(next===el.value) return;
    const s=el.selectionStart,e=el.selectionEnd,a=document.activeElement===el;
    el.value=next;
    try{ if(a&&s!=null) el.setSelectionRange(s,e); }catch(_){}
  }
  function bind(el){
    if(!el||el.dataset.faBound==='1'||skip(el)) return;
    el.dataset.faBound='1';
    convertInput(el);
    ['input','blur','change','keyup'].forEach(ev=>el.addEventListener(ev,()=>convertInput(el),true));
  }
  function days(panel){
    if(!panel) return;
    panel.querySelectorAll('button,span,div').forEach(el=>{
      if(el.tagName==='INPUT'||el.querySelector?.('input')) return;
      if(el.children.length===0 && el.textContent && /[0-9]/.test(el.textContent)){
        const t=toFa(el.textContent); if(t!==el.textContent) el.textContent=t;
      }
    });
  }
  function scan(){
    document.querySelectorAll(
      'input:not([type]),input[type="text"],input[type="search"],input[type="tel"],input[inputmode="numeric"],textarea,.fi-fo-date-time-picker input'
    ).forEach(bind);
    document.querySelectorAll('.fi-fo-date-time-picker-panel').forEach(days);
  }
  let watch=null;
  function startWatch(){
    if(watch) return;
    watch=setInterval(()=>{
      const panels=document.querySelectorAll('.fi-fo-date-time-picker-panel');
      if(!panels.length){ clearInterval(watch); watch=null; return; }
      panels.forEach(days);
      document.querySelectorAll('.fi-fo-date-time-picker input').forEach(el=>{
        if(!el.closest('.fi-fo-date-time-picker-panel')) convertInput(el);
      });
    },100);
  }
  function boot(){
    scan();
    new MutationObserver(()=>{ scan(); if(document.querySelector('.fi-fo-date-time-picker-panel')) startWatch(); })
      .observe(document.body,{childList:true,subtree:true});
    document.addEventListener('click',e=>{
      if(e.target.closest?.('.fi-fo-date-time-picker')){
        startWatch();
        [0,50,150,300].forEach(ms=>setTimeout(()=>{
          document.querySelectorAll('.fi-fo-date-time-picker-panel').forEach(days);
          document.querySelectorAll('.fi-fo-date-time-picker input').forEach(el=>{
            if(!el.closest('.fi-fo-date-time-picker-panel')) convertInput(el);
          });
        },ms));
      }
    },true);
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',boot); else boot();
  document.addEventListener('livewire:navigated',scan);
})();
