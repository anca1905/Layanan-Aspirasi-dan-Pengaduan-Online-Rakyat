const https = require('https');
const url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=-4.7667&lon=121.9667&addressdetails=1&accept-language=id&zoom=18";
https.get(url, { headers: { 'User-Agent': 'Node.js' } }, (res) => {
    let data = '';
    res.on('data', chunk => data += chunk);
    res.on('end', () => console.log(data));
});
