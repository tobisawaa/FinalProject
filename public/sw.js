self.addEventListener('push', function(event) {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'Weather App', body: 'Notifikasi baru diterima!' };
  }

  const title = data.title || 'Weather Update';
  
  // Konfigurasi Tampilan "Stylish"
  const options = {
    body: data.body || '',
    icon: data.icon || '/assets/img/icon.png',  
    badge: data.badge || '/assets/img/badge.png', 
    image: data.image || null,                  
    vibrate: [100, 50, 100],                    
    data: {
      url: data.url || '/',                  
      type: data.type
    },
    actions: data.actions || []      
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
  event.notification.close();

  // Handle klik pada tombol aksi
  if (event.action === 'close') {
    return; // Cuma tutup notif
  }

  // Handle klik pada notifikasi utama atau tombol "view"
  const urlToOpen = event.notification.data.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
      // Jika tab sudah terbuka, fokus ke sana
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === urlToOpen && 'focus' in client) {
          return client.focus();
        }
      }
      // Jika belum, buka tab baru
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});