// languages-engine.js — Search filter for the Languages page
(function () {
    'use strict';

    const langSearchInput  = document.getElementById('langSearchInput');
    const langVisibleCount = document.getElementById('langVisibleCount');
    const langNoResults    = document.getElementById('langNoResults');
    const langSections     = document.querySelectorAll('.lang-cat');
    const langTotalLangs   = parseInt(langSearchInput.dataset.langTotal || '0', 10);

    if (!langSearchInput) return;

    langSearchInput.addEventListener('input', () => {
        const q = langSearchInput.value.trim().toLowerCase();
        let total = 0;

        langSections.forEach(section => {
            const tags = section.querySelectorAll('.lang-tag');
            let sectionVisible = 0;

            tags.forEach(tag => {
                const name = tag.dataset.name;
                const match = !q || name.includes(q);
                tag.style.display = match ? '' : 'none';
                tag.classList.toggle('highlight', q && match);
                if (match) sectionVisible++;
            });

            total += sectionVisible;
            section.classList.toggle('hidden', sectionVisible === 0);
        });

        langVisibleCount.textContent = total;
        langNoResults.style.display = total === 0 ? 'block' : 'none';
    });
})();
