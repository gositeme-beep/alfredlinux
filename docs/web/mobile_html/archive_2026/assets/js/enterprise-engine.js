// ROI Calculator
function calculateROI() {
    const agents = parseInt(document.getElementById('roiAgents').value) || 10;
    const callsPerDay = parseInt(document.getElementById('roiCalls').value) || 30;
    const avgDuration = parseInt(document.getElementById('roiDuration').value) || 8;

    // Calculate current costs
    const avgAgentSalary = 3500; // per month per agent
    const workingDays = 22;
    const totalCallsPerMonth = agents * callsPerDay * workingDays;
    const totalHoursPerMonth = (totalCallsPerMonth * avgDuration) / 60;
    const currentMonthlyCost = agents * avgAgentSalary;

    // Alfred can automate ~60% of calls
    const automationRate = 0.60;
    const automatedCalls = Math.floor(totalCallsPerMonth * automationRate);
    const agentsNeeded = Math.ceil(agents * (1 - automationRate));
    const alfredCost = (agentsNeeded * avgAgentSalary) + (24.99 * Math.ceil(agents / 5));
    const savings = currentMonthlyCost - alfredCost;
    const hoursSaved = Math.floor(totalHoursPerMonth * automationRate);

    // Show results
    const resultEl = document.getElementById('roiResult');
    resultEl.classList.add('visible');

    document.getElementById('roiSavings').textContent = '$' + savings.toLocaleString();
    document.getElementById('roiCurrentCost').textContent = '$' + currentMonthlyCost.toLocaleString();
    document.getElementById('roiAlfredCost').textContent = '$' + Math.floor(alfredCost).toLocaleString();
    document.getElementById('roiAutomated').textContent = Math.floor(automationRate * 100) + '%';
    document.getElementById('roiTimeSaved').textContent = hoursSaved.toLocaleString() + ' hrs';
}

// Contact Form
function submitEntForm(e) {
    e.preventDefault();
    const form = document.getElementById('entContactForm');
    const formData = new FormData(form);

    // Build mailto link as a simple fallback
    const subject = encodeURIComponent('Enterprise Inquiry from ' + formData.get('company'));
    const body = encodeURIComponent(
        'Company: ' + formData.get('company') + '\n' +
        'Email: ' + formData.get('email') + '\n' +
        'Phone: ' + (formData.get('phone') || 'N/A') + '\n' +
        'Company Size: ' + formData.get('company_size') + '\n\n' +
        'Message:\n' + formData.get('message')
    );

    // Open mailto
    window.location.href = 'mailto:sales@gositeme.com?subject=' + subject + '&body=' + body;

    // Show success
    form.style.display = 'none';
    document.getElementById('entFormSuccess').classList.add('visible');
}
