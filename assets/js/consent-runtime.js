/**
 * Piratez Cookie Consent - Runtime. Only gtag('consent','update'). Default set in head by PHP.
 */
(function(w,d){
  'use strict';
  if(w.pccConsent) return;
  var config=w.pccConfig||{storageKey:'pcc_consent',debug:false};
  function debug(msg,data){ if(config.debug&&console&&console.log) console.log('[PCC] '+msg,data||''); }
  function readState(){
    try{
      var s=localStorage.getItem(config.storageKey);
      if(s){ var p=JSON.parse(s); if(typeof p==='object'&&typeof p.analytics==='boolean'&&typeof p.ads==='boolean') return p; }
    }catch(e){ debug('Error reading state',e); }
    return {analytics:false,ads:false};
  }
  function hasStoredChoice(){
    try{
      var s=localStorage.getItem(config.storageKey);
      if(!s) return false;
      var p=JSON.parse(s);
      return typeof p==='object'&&typeof p.analytics==='boolean'&&typeof p.ads==='boolean';
    }catch(e){ return false; }
  }
  function writeState(state){
    try{ localStorage.setItem(config.storageKey,JSON.stringify(state)); debug('State saved',state); }catch(e){ debug('Error saving',e); }
  }
  function updateGCM(state){
    if(typeof gtag!=='function'){ debug('gtag not available'); return; }
    try{
      gtag('consent','update',{
        'analytics_storage':state.analytics?'granted':'denied',
        'ad_storage':state.ads?'granted':'denied',
        'ad_user_data':state.ads?'granted':'denied',
        'ad_personalization':state.ads?'granted':'denied'
      });
      debug('GCM updated',state);
    }catch(e){ debug('Error updating GCM',e); }
  }
  function dispatch(state){
    try{ d.dispatchEvent(new CustomEvent('pcc:changed',{detail:state,bubbles:true,cancelable:false})); }catch(e){ debug('Error dispatch',e); }
  }
  function updateConsent(state){ writeState(state); updateGCM(state); dispatch(state); }
  w.pccConsent={
    current:readState,
    acceptAll:function(){ updateConsent({analytics:true,ads:true}); },
    acceptAnalyticsOnly:function(){ updateConsent({analytics:true,ads:false}); },
    declineAll:function(){ updateConsent({analytics:false,ads:false}); },
    reset:function(){ try{ localStorage.removeItem(config.storageKey); }catch(e){} var def={analytics:false,ads:false}; updateGCM(def); dispatch(def); }
  };
  /* Per Google docs: only call update when user has a stored choice (subsequent pages). First visit: default only. */
  if(hasStoredChoice()){ updateGCM(readState()); }
  debug('Runtime init',readState());
})(window,document);
