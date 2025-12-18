// Push subscription helper: register service worker and subscribe
(function(){
  async function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  async function getVapidPublicKey(){
    try{
      const res = await fetch('/api/vapid.php');
      if(!res.ok) return null;
      const json = await res.json();
      return json.publicKey || null;
    }catch(e){ console.warn('vapid fetch failed', e); return null; }
  }

  async function subscribeUser(){
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
    try{
      const reg = await navigator.serviceWorker.register('/sw.js');
      console.log('ServiceWorker registered', reg.scope);
      const permission = await Notification.requestPermission();
      if(permission !== 'granted') return;

      const publicKey = await getVapidPublicKey();
      if(!publicKey) { console.warn('No VAPID public key available'); return; }

      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: await urlBase64ToUint8Array(publicKey)
      });

      // Send subscription to server
      const res = await fetch('/api/subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sub)
      });
      const json = await res.json();
      console.log('subscribe response', json);
    }catch(e){ console.warn('subscribe failed', e); }
  }

  // Attempt to subscribe on load (only if user is logged in and page served over https or localhost)
  if (window.location.protocol === 'https:' || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    window.addEventListener('load', ()=>{
      subscribeUser();
    });
  }
})();
