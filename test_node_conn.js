const https = require('https');

const url = 'https://opencode.ai';

console.log(`Testing connection to ${url}...`);

https.get(url, (res) => {
  console.log('statusCode:', res.statusCode);
  console.log('headers:', res.headers);

  res.on('data', (d) => {
    // just consume data
  });

  res.on('end', () => {
    console.log('Connection successful.');
  });

}).on('error', (e) => {
  console.error('Connection failed:', e);
});
