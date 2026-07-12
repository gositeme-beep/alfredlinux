document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('intSearch');
    const tabs = document.querySelectorAll('.int-tab');
    const cards = document.querySelectorAll('.int-card');
    const noResults = document.getElementById('intNoResults');
    let activeCategory = 'all';

    function filterCards() {
        const query = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name || '';
            const cat = card.dataset.cat || '';
            const text = card.textContent.toLowerCase();
            const matchesSearch = !query || name.includes(query) || text.includes(query);
            const matchesCat = activeCategory === 'all' || cat === activeCategory;

            if (matchesSearch && matchesCat) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterCards);

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            activeCategory = this.dataset.cat;
            filterCards();
        });
    });

    // Request form — posts to API so data is actually saved
    const form = document.getElementById('intRequestForm');
    const msg = document.getElementById('intReqMsg');
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const platform = document.getElementById('intReqPlatform').value.trim();
        const email = document.getElementById('intReqEmail').value.trim();
        const useCase = document.getElementById('intReqUseCase').value.trim();

        if (!platform || !email) return;

        try {
            const res = await fetch('/api/integration-request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ platform, email, use_case: useCase })
            });
            const data = await res.json();
            if (data.success) {
                msg.className = 'int-request-msg success';
                msg.textContent = 'Thank you! We\'ve logged your request for "' + platform + '". We\'ll notify you at ' + email + ' when it\'s available.';
                form.reset();
            } else {
                msg.className = 'int-request-msg error';
                msg.textContent = data.error || 'Something went wrong. Please try again.';
            }
        } catch (err) {
            msg.className = 'int-request-msg error';
            msg.textContent = 'Network error. Please try again later.';
        }

        msg.style.display = '';
        setTimeout(() => { msg.style.display = 'none'; msg.className = 'int-request-msg'; }, 8000);
    });
});
