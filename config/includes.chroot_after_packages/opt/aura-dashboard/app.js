// app.js
// Simulates incoming telemetry data from the Alfred Machine API over the QGSM mesh

const telemetryFeed = document.getElementById('telemetry-feed');
const activeCases = document.getElementById('active-cases');

let caseCount = 3;

// Mock telemetry generator
function generateTelemetry() {
    const units = ['UNIT-0001', 'UNIT-0002', 'UNIT-0003', 'UNIT-0004'];
    const randomUnit = units[Math.floor(Math.random() * units.length)];
    const battery = Math.floor(Math.random() * 20) + 80; // 80-100%
    
    const item = document.createElement('div');
    item.className = 'telemetry-item';
    item.innerHTML = `
        <span class="unit-id">DID:ALFRED:${randomUnit}</span>
        <span class="unit-status">ONLINE - Bat: ${battery}%</span>
    `;
    
    telemetryFeed.prepend(item);
    
    // Keep only the 4 most recent
    if (telemetryFeed.children.length > 4) {
        telemetryFeed.removeChild(telemetryFeed.lastChild);
    }
}

// Simulate new incoming connections every 3 seconds
setInterval(generateTelemetry, 3000);

// Simulate Judiciary Cases incoming
setInterval(() => {
    if (Math.random() > 0.8) {
        caseCount++;
        activeCases.innerText = caseCount;
        activeCases.style.color = '#ff3366';
        setTimeout(() => {
            activeCases.style.color = '#00ffcc';
        }, 1000);
    }
}, 8000);

console.log("Aura Dashboard HUD Initialized. Listening to 9.9.9.9.");
