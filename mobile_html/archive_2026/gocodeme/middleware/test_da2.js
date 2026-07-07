require('dotenv').config();
const { createDAClient } = require('./src/directadmin/client');
async function test() {
  try {
    const client = createDAClient('gositeme');
    console.log("Sending request...");
    const res = await client.get('/CMD_API_FILE_MANAGER', { params: { action: 'list', path: 'domains' } });
    console.log("SUCCESS length:", res.data.length);
  } catch (err) {
    console.error("ERROR 500 Data:", err.response ? err.response.data : err.message);
  }
}
test().catch(console.error);
