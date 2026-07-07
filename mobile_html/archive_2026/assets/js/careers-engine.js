async function submitApplication(e) {
    e.preventDefault();
    const form = document.getElementById('applicationForm');
    const btn = document.getElementById('submitBtn');
    const successMsg = document.getElementById('successMsg');
    const errorMsg = document.getElementById('errorMsg');

    successMsg.style.display = 'none';
    errorMsg.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Submitting...';

    const fd = new FormData(form);
    fd.append('action', 'apply');
    fd.append('csrf_token', window.AW_CSRF_TOKEN || '');

    // Convert skills to array
    const skills = fd.get('skills');
    if (skills) {
        fd.delete('skills');
        const skillsArr = skills.split(',').map(s => s.trim()).filter(Boolean);
        fd.append('skills', JSON.stringify(skillsArr));
    }

    try {
        const res = await fetch('/api/workforce.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            successMsg.style.display = 'block';
            form.reset();
        } else {
            errorMsg.textContent = data.error || 'Something went wrong. Please try again.';
            errorMsg.style.display = 'block';
        }
    } catch (err) {
        errorMsg.textContent = 'Network error. Please check your connection and try again.';
        errorMsg.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Submit Application';
    }
}
