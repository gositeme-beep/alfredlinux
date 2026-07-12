// security-engine.js — Accordion toggle for the Security page
(function () {
    'use strict';

    document.querySelectorAll('.sec-acc-header').forEach(function(header) {
        header.addEventListener('click', function() {
            var item = this.closest('.sec-acc-item');
            var wasOpen = item.classList.contains('open');
            // Close all siblings
            item.parentElement.querySelectorAll('.sec-acc-item').forEach(function(el) {
                el.classList.remove('open');
            });
            if (!wasOpen) item.classList.add('open');
        });
    });
})();
