(function () {
    'use strict';

    var form    = document.getElementById('mova-qf-form');
    var btn     = document.getElementById('mova-qf-submit');
    var msgEl   = document.getElementById('mova-qf-message');

    if (!form || !btn || !msgEl) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Reset
        clearErrors();
        hideMessage();

        // Validation
        var errors = validate();
        if (errors.length > 0) {
            showMessage('error', errors.join('<br>'));
            return;
        }

        // Envoi AJAX
        btn.disabled = true;
        btn.classList.add('mova-qf-loading');

        var data = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', movaQuoteForm.ajaxUrl, true);

        xhr.onload = function () {
            btn.disabled = false;
            btn.classList.remove('mova-qf-loading');

            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    showMessage('success', res.data.message);
                    form.reset();
                } else {
                    showMessage('error', res.data.message || movaQuoteForm.i18n.genericError);
                }
            } catch (err) {
                showMessage('error', movaQuoteForm.i18n.retryError);
            }
        };

        xhr.onerror = function () {
            btn.disabled = false;
            btn.classList.remove('mova-qf-loading');
            showMessage('error', movaQuoteForm.i18n.connectionError);
        };

        xhr.send(data);
    });

    /* ========================
       Validation côté client
       ======================== */
    function validate() {
        var errors = [];

        var prenom   = form.querySelector('[name="prenom"]');
        var nom      = form.querySelector('[name="nom"]');
        var courriel = form.querySelector('[name="courriel"]');
        var tel      = form.querySelector('[name="telephone"]');
        var accord   = form.querySelector('[name="accord_coordonnees"]');

        if (!prenom.value.trim()) {
            errors.push(movaQuoteForm.i18n.prenomRequired);
            prenom.classList.add('mova-qf-error');
        }
        if (!nom.value.trim()) {
            errors.push(movaQuoteForm.i18n.nomRequired);
            nom.classList.add('mova-qf-error');
        }
        if (!courriel.value.trim() || !isValidEmail(courriel.value.trim())) {
            errors.push(movaQuoteForm.i18n.emailInvalid);
            courriel.classList.add('mova-qf-error');
        }
        if (!tel.value.trim()) {
            errors.push(movaQuoteForm.i18n.telRequired);
            tel.classList.add('mova-qf-error');
        }
        if (!accord.checked) {
            errors.push(movaQuoteForm.i18n.accordRequired);
        }

        return errors;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    /* ========================
       UI helpers
       ======================== */
    function clearErrors() {
        var fields = form.querySelectorAll('.mova-qf-error');
        for (var i = 0; i < fields.length; i++) {
            fields[i].classList.remove('mova-qf-error');
        }
    }

    function showMessage(type, html) {
        msgEl.className = 'mova-qf-message mova-qf-message--' + type;
        msgEl.innerHTML = html;
        msgEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideMessage() {
        msgEl.className = 'mova-qf-message';
        msgEl.innerHTML = '';
    }

    // Clear error on input
    form.addEventListener('input', function (e) {
        if (e.target.classList.contains('mova-qf-error')) {
            e.target.classList.remove('mova-qf-error');
        }
    });

})();
