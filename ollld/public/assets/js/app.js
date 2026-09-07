/* Sandworth Homes - App JS */

document.querySelectorAll('a[href^="?page="]').forEach(function(link) {
    link.addEventListener('click', function() {
        document.body.classList.add('is-transitioning');
    });
});

document.querySelectorAll('.gallery-thumb').forEach(function(thumb) {
    thumb.addEventListener('click', function() {
        var hero = document.getElementById('gallery-hero-image');
        var src = thumb.getAttribute('data-gallery-src');

        if (!hero || !src) {
            return;
        }

        hero.setAttribute('src', src);

        document.querySelectorAll('.gallery-thumb').forEach(function(otherThumb) {
            otherThumb.classList.remove('is-active');
        });

        thumb.classList.add('is-active');
    });
});

(function initHeroTabs() {
    var tabs = document.querySelectorAll('.hero-tabs .tab-search-item');
    var pageInput = document.getElementById('hero-page-input');
    var searchField = document.getElementById('hero-search-field');
    var bedsSelect = document.getElementById('beds');
    var submitBtn = document.getElementById('hero-search-submit');

    if (!tabs.length || !pageInput || !searchField) {
        return;
    }

    var bedsOptions = [
        '<option value="">Any beds</option>',
        '<option value="1">1+ bed</option>',
        '<option value="2">2+ beds</option>',
        '<option value="3">3+ beds</option>',
        '<option value="4">4+ beds</option>'
    ].join('');

    var commercialOptions = [
        '<option value="">Any type</option>',
        '<option value="Mall">Shopping mall</option>',
        '<option value="Shop">Shop / retail unit</option>',
        '<option value="Office">Office space</option>'
    ].join('');

    var submitLabels = {
        rentals: 'Search rentals',
        homes: 'Search homes',
        commercial: 'Search spaces'
    };

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.getAttribute('data-target');
            var fieldName = tab.getAttribute('data-field');
            var fieldLabel = tab.getAttribute('data-field-label');

            tabs.forEach(function(otherTab) {
                otherTab.classList.remove('is-active');
            });

            tab.classList.add('is-active');
            pageInput.value = target;

            if (fieldName) {
                searchField.style.display = '';
                searchField.setAttribute('name', fieldName);
                searchField.setAttribute('placeholder', fieldLabel || '');
                searchField.value = '';
            } else {
                searchField.style.display = 'none';
            }

            if (bedsSelect) {
                if (target === 'commercial') {
                    bedsSelect.setAttribute('name', 'commercial_type');
                    bedsSelect.innerHTML = commercialOptions;
                } else {
                    bedsSelect.setAttribute('name', 'beds');
                    bedsSelect.innerHTML = bedsOptions;
                }
            }

            if (submitBtn) {
                submitBtn.textContent = submitLabels[target] || 'Search';
            }
        });
    });
})();

(function initAutoSubmitSort() {
    var sortSelects = document.querySelectorAll('select[name="sort"]');

    sortSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            var form = select.closest('form');

            if (form) {
                form.submit();
            }
        });
    });
})();

(function initResultCardClick() {
    document.querySelectorAll('.result-card[data-listing-id]').forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }

            var link = card.querySelector('.text-link');

            if (link && link.href) {
                document.body.classList.add('is-transitioning');
                window.location.href = link.href;
            }
        });
    });
})();

(function initFlashDismiss() {
    var flashes = document.querySelectorAll('.flash');

    flashes.forEach(function(flash) {
        setTimeout(function() {
            flash.style.transition = 'opacity .4s, max-height .4s, padding .4s, margin .4s';
            flash.style.opacity = '0';
            flash.style.maxHeight = '0';
            flash.style.padding = '0';
            flash.style.margin = '0';
        }, 4500);
    });
})();

(function initStickyFilter() {
    var filterBar = document.querySelector('.inline-filter-bar');

    if (!filterBar) {
        return;
    }

    var observer = new IntersectionObserver(function(entries) {
        var entry = entries[0];
        filterBar.classList.toggle('is-stuck', !entry.isIntersecting);
    }, {
        threshold: 0,
        rootMargin: '-80px 0px 0px 0px'
    });

    observer.observe(filterBar);
})();

(function initAdminGallery() {
    var previewCard = document.querySelector('.admin-preview-card');

    if (!previewCard) {
        return;
    }

    var mainImgInput = document.querySelector('input[name="image"]');

    if (mainImgInput) {
        mainImgInput.addEventListener('blur', function() {
            var img = previewCard.querySelector('img');

            if (img && this.value) {
                img.src = this.value;
            }
        });
    }
})();

(function initCalculator() {
    var runBtn = document.getElementById('calc-run');
    var typeSelect = document.getElementById('calc-type');
    var mortgageFields = document.getElementById('calc-mortgage-fields');

    if (!runBtn || !typeSelect) {
        return;
    }

    function fmt(n) {
        return n.toLocaleString('en-NG');
    }

    function toggleMortgageFields() {
        if (!mortgageFields) {
            return;
        }
        mortgageFields.style.display = typeSelect.value === 'buy' ? 'grid' : 'none';
    }

    typeSelect.addEventListener('change', toggleMortgageFields);
    toggleMortgageFields();

    runBtn.addEventListener('click', function() {
        var income = parseInt(document.getElementById('calc-income').value || '0', 10);
        var result = document.getElementById('calc-result');

        if (!result) {
            return;
        }

        if (!income || income <= 0) {
            result.innerHTML = '<div style="color:var(--danger,#C0392B)">Please enter a monthly income.</div>';
            return;
        }

        if (typeSelect.value === 'rent') {
            var maxAnnualRent = income * 12;
            var maxMonthlyRent = Math.round(maxAnnualRent / 12);
            var comfortAnnual = Math.round(income * 8);
            var MAX_RENT_MSG =
                '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Max rent you can afford (annual)</span><strong>&#8358;' + fmt(maxAnnualRent) + '</strong></div>' +
                '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Max monthly rent</span><strong>&#8358;' + fmt(maxMonthlyRent) + '</strong></div>' +
                '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Comfortable annual budget (&#8776;66% rule)</span><strong>&#8358;' + fmt(comfortAnnual) + '</strong></div>' +
                '<div style="margin-top:10px; padding-top:10px; border-top:1px solid var(--line-light); color:var(--ink-mid); font-size:.85rem">Tip: keep monthly rent around <strong>&#8358;' + fmt(maxMonthlyRent) + '</strong> for a safe balance.</div>';
            result.innerHTML = MAX_RENT_MSG;
            return;
        }

        var price = parseInt(document.getElementById('calc-loan').value || '0', 10);
        var downPct = parseFloat(document.getElementById('calc-down').value || '20');
        var rate = parseFloat(document.getElementById('calc-rate').value || '22');
        var term = parseInt(document.getElementById('calc-term').value || '20', 10);

        var down = Math.round(price * downPct / 100);
        var loan = Math.max(price - down, 0);
        var monthlyRate = rate / 100 / 12;
        var months = term * 12;
        var monthlyPayment = 0;

        if (loan > 0 && monthlyRate > 0 && months > 0) {
            monthlyPayment = loan * (monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
        } else if (loan > 0) {
            monthlyPayment = loan / months;
        }

        var neededMonthly = Math.round(monthlyPayment * 1.5);
        result.innerHTML =
            '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Home price</span><strong>' + fmt(price) + '</strong></div>' +
            '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Down payment (' + downPct + '%)</span><strong>' + fmt(down) + '</strong></div>' +
            '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Loan amount</span><strong>' + fmt(loan) + '</strong></div>' +
            '<div style="display:flex; justify-content:space-between"><span style="color:var(--muted)">Estimated monthly payment</span><strong>' + fmt(Math.round(monthlyPayment)) + '</strong></div>' +
            '<div style="margin-top:10px; padding-top:10px; border-top:1px solid var(--line-light); color:var(--ink-mid); font-size:.85rem">To afford this, a monthly income of about <strong>' + fmt(neededMonthly) + '</strong> is recommended.</div>';
    });
})();
