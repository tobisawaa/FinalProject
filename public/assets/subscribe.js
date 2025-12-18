// public/assets/subscribe.js

async function registerAndSubscribe(vapidPublicKey = null) {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    throw new Error('Push or Service Worker not supported');
  }

  // If VAPID not provided, fetch from server
  if (!vapidPublicKey) {
    // PERBAIKAN DISINI: Hapus tanda '/' di depan api
    const resp = await fetch('api/vapid.php'); 
    
    if (!resp.ok) throw new Error('Failed to fetch VAPID public key');
    const data = await resp.json();
    if (!data.success || !data.vapid_public_key) {
      throw new Error('VAPID public key not available from server');
    }
    vapidPublicKey = data.vapid_public_key;
  }

  // Register SW (Pastikan sw.js ada di folder public)
  const registration = await navigator.serviceWorker.register('sw.js');

  const permission = await Notification.requestPermission();
  if (permission !== 'granted') throw new Error('Notification permission denied');

  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
  });

  function arrayBufferToBase64(buffer) {
    if (!buffer) return null;
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
    return btoa(binary);
  }

  const payload = {
    endpoint: subscription.endpoint,
    keys: {
      p256dh: arrayBufferToBase64(subscription.getKey('p256dh')),
      auth: arrayBufferToBase64(subscription.getKey('auth'))
    }
  };

  // PERBAIKAN DISINI JUGA: Pastikan tidak ada '/' di depan api
  const resp = await fetch('api/subscribe.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  if (!resp.ok) {
    const txt = await resp.text();
    throw new Error('Subscribe failed: ' + txt);
  }

  return resp.json();
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
  return outputArray;
}

window.registerAndSubscribe = registerAndSubscribe;
window.urlBase64ToUint8Array = urlBase64ToUint8Array;