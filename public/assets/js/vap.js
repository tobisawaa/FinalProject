const webpush = require('web-push');
const vapidKeys = webpush.generateVAPIDKeys();
console.log(vapidKeys);
// { publicKey: 'BN...', privateKey: 'zX...' }
webpush.setVapidDetails(
  'mailto:if24.muhamadmaharandi@mhs.ubpkarawang.ac.id',
  process.env.VAPID_PUBLIC_KEY,
  process.env.VAPID_PRIVATE_KEY
);
