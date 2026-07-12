function switchTab(lang) {
    document.querySelectorAll('.sdk-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.sdk-tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById('tab-' + lang).classList.add('active');
    document.getElementById('content-' + lang).classList.add('active');
}

function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        }, 2000);
    });
}

function copyCode(btn) {
    const codeBlock = btn.closest('.sdk-example-code').querySelector('.sdk-code-block code');
    navigator.clipboard.writeText(codeBlock.textContent).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="fas fa-copy"></i> Copy';
        }, 2000);
    });
}

// Highlight code on load
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Prism !== 'undefined') Prism.highlightAll();
});
