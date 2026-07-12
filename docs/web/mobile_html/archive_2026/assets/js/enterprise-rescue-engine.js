// enterprise-rescue-engine.js — ROI calculator + form handler
(function () {
    'use strict';

    function calcROI() {
        const infra = parseInt(document.getElementById('roiInfra').value) || 0;
        const saas = parseInt(document.getElementById('roiSaas').value) || 0;
        const consult = parseInt(document.getElementById('roiConsult').value) || 0;
        const employees = parseInt(document.getElementById('roiEmployees').value) || 0;
        const totalCurrent = infra + saas + consult;
        const metadomeCost = employees * 120 * 12; // $120/employee/month estimate
        const saved = Math.max(0, totalCurrent - metadomeCost);
        const agents = employees; // 1:1 agent deployment
        const gsm = employees * 5; // 5 GSM per employee integration
        const months = saved > 0 ? Math.max(1, Math.ceil(metadomeCost / (saved / 12))) : 0;
        document.getElementById('roiSaved').textContent = '$' + (saved / 1e6).toFixed(1) + 'M';
        document.getElementById('roiAgents').textContent = agents.toLocaleString();
        document.getElementById('roiGsm').textContent = gsm.toLocaleString();
        document.getElementById('roiRecoup').textContent = months > 0 ? months + ' mo' : 'Instant';
    }

    function submitRescue(e) {
        e.preventDefault();
        document.getElementById('rescueForm').style.display = 'none';
        document.getElementById('rescueSuccess').style.display = 'block';
        return false;
    }

    window.calcROI = calcROI;
    window.submitRescue = submitRescue;
})();
