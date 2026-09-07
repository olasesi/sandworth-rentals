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
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.tagName === 'BUTTON' || e.target.closest('button')) {
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

(function initShareButtons() {
    var buttons = document.querySelectorAll('.share-button[data-share-url]');

    function isMobileLikeDevice() {
        return /Android|iPhone|iPad|iPod|Mobile|Opera Mini|IEMobile/i.test(navigator.userAgent || '')
            || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
    }

    function openWhatsAppFallback(button) {
        var whatsAppUrl = button.getAttribute('data-whatsapp-url');

        if (!whatsAppUrl || !isMobileLikeDevice()) {
            return false;
        }

        window.location.href = whatsAppUrl;

        return true;
    }

    function setButtonState(button, label, isError) {
        button.setAttribute('data-feedback', label);
        button.classList.toggle('is-error', !!isError);
        button.classList.toggle('is-success', !isError);
        button.setAttribute('title', label);
        button.setAttribute('aria-label', label);

        window.setTimeout(function() {
            button.removeAttribute('data-feedback');
            button.classList.remove('is-error');
            button.classList.remove('is-success');
            var originalTitle = button.getAttribute('data-default-title') || 'Share property';
            button.setAttribute('title', originalTitle);
            button.setAttribute('aria-label', originalTitle);
        }, 2200);
    }

    buttons.forEach(function(button) {
        button.setAttribute('data-default-title', button.getAttribute('title') || 'Share property');

        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            var shareUrl = button.getAttribute('data-share-url') || window.location.href;

            if (navigator.share) {
                navigator.share({
                    url: shareUrl
                }).catch(function(error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    if (openWhatsAppFallback(button)) {
                        return;
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(shareUrl).then(function() {
                            setButtonState(button, 'Link copied');
                        }).catch(function() {
                            setButtonState(button, 'Copy failed', true);
                        });
                    }
                });

                return;
            }

            if (openWhatsAppFallback(button)) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shareUrl).then(function() {
                    setButtonState(button, 'Link copied');
                }).catch(function() {
                    setButtonState(button, 'Copy failed', true);
                });

                return;
            }

            window.prompt('Copy this property link:', shareUrl);
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
