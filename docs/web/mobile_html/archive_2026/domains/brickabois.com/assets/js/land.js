/**
 * The Land - Village Discovery JavaScript
 */

(function() {
    'use strict';

    // Auto-submit form on filter change
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('searchForm');
        if (!form) return;

        const selects = form.querySelectorAll('.filter-select');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                form.submit();
            });
        });

        // Debounce search input
        const searchInput = form.querySelector('.search-input');
        let searchTimeout;
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    // Only auto-submit if user stops typing for 1 second
                    if (this.value.length === 0 || this.value.length >= 3) {
                        form.submit();
                    }
                }, 1000);
            });

            // Submit on Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchTimeout);
                    form.submit();
                }
            });
        }
    });

    // Add hover effects to village cards
    document.querySelectorAll('.village-card-airbnb').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });

})();

