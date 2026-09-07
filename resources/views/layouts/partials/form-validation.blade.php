<script>
(function () {
    var ERROR_CLASS = 'client-field-error';
    var INVALID_INPUT_CLASS = 'border-red-300';
    var INVALID_RING_CLASS = 'focus:border-red-500';
    var SKIP_IDS = ['payment-form', 'portal-form'];

    function shouldSkipForm(form) {
        if (!form || form.tagName !== 'FORM') return true;
        if ((form.method || 'get').toUpperCase() === 'GET') return true;
        if (form.hasAttribute('data-skip-validate')) return true;
        if (form.id && SKIP_IDS.indexOf(form.id) !== -1) return true;
        return false;
    }

    function fieldWrapper(field) {
        return field.closest('[data-field-wrapper]')
            || field.closest('.choice-picker')
            || field.closest('div');
    }

    function clearFieldError(field) {
        var wrapper = fieldWrapper(field);
        if (!wrapper) return;
        var existing = wrapper.querySelector('.' + ERROR_CLASS);
        if (existing) existing.remove();
        field.classList.remove(INVALID_INPUT_CLASS, INVALID_RING_CLASS, 'focus:ring-red-500');
        field.removeAttribute('aria-invalid');
    }

    function showFieldError(field, message) {
        clearFieldError(field);
        var wrapper = fieldWrapper(field);
        if (!wrapper) return;
        field.classList.add(INVALID_INPUT_CLASS, INVALID_RING_CLASS, 'focus:ring-red-500');
        field.setAttribute('aria-invalid', 'true');
        var el = document.createElement('p');
        el.className = 'mt-1 text-xs text-red-600 ' + ERROR_CLASS;
        el.setAttribute('role', 'alert');
        el.textContent = message;
        wrapper.appendChild(el);
    }

    function validationMessage(field) {
        if (field.validity.valueMissing) {
            var label = field.labels && field.labels[0] ? field.labels[0].textContent.replace('*', '').trim() : 'This field';
            return label + ' is required.';
        }
        if (field.validity.typeMismatch) {
            if (field.type === 'email') return 'Enter a valid email address.';
            if (field.type === 'url') return 'Enter a valid URL.';
        }
        if (field.validity.patternMismatch) return field.title || 'Enter a value in the correct format.';
        if (field.validity.tooShort) return 'Enter at least ' + field.minLength + ' characters.';
        if (field.validity.tooLong) return 'Enter no more than ' + field.maxLength + ' characters.';
        if (field.validity.rangeUnderflow) return 'Value must be at least ' + field.min + '.';
        if (field.validity.rangeOverflow) return 'Value must be at most ' + field.max + '.';
        if (field.validity.stepMismatch) return 'Enter a valid step value.';
        return field.validationMessage || 'This field is invalid.';
    }

    function validateChoicePickers(form) {
        var valid = true;
        form.querySelectorAll('input[type="hidden"][data-required="1"]').forEach(function (input) {
            clearFieldError(input);
            if (!input.value || input.value.trim() === '') {
                var picker = input.closest('.choice-picker');
                var labelEl = picker ? picker.querySelector('label') : null;
                var label = labelEl ? labelEl.textContent.replace('*', '').trim() : 'Selection';
                showFieldError(input, label + ' is required.');
                valid = false;
            }
        });
        return valid;
    }

    function validatePasswordConfirmation(form) {
        var password = form.querySelector('input[name="password"]:not([disabled])');
        var confirm = form.querySelector('input[name="password_confirmation"]:not([disabled])');
        if (!password || !confirm || !password.value) return true;
        clearFieldError(confirm);
        if (password.value !== confirm.value) {
            showFieldError(confirm, 'Passwords do not match.');
            return false;
        }
        return true;
    }

    function validateMinCheckedGroups(form) {
        var valid = true;
        form.querySelectorAll('[data-min-checked]').forEach(function (group) {
            var min = parseInt(group.getAttribute('data-min-checked'), 10) || 1;
            var name = group.getAttribute('data-checkbox-name');
            if (!name) return;
            var checked = form.querySelectorAll('input[type="checkbox"][name="' + name + '"]:checked').length;
            var existing = group.querySelector('.' + ERROR_CLASS);
            if (existing) existing.remove();
            if (checked < min) {
                var el = document.createElement('p');
                el.className = 'mt-2 text-xs text-red-600 ' + ERROR_CLASS;
                el.setAttribute('role', 'alert');
                el.textContent = 'Select at least ' + min + ' option' + (min === 1 ? '' : 's') + '.';
                group.appendChild(el);
                valid = false;
            }
        });
        return valid;
    }

    function validateForm(form) {
        form.querySelectorAll('.' + ERROR_CLASS).forEach(function (el) { el.remove(); });
        form.querySelectorAll('[aria-invalid="true"]').forEach(function (field) {
            field.classList.remove(INVALID_INPUT_CLASS, INVALID_RING_CLASS, 'focus:ring-red-500');
            field.removeAttribute('aria-invalid');
        });

        var valid = true;
        var fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(function (field) {
            if (field.disabled || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') return;
            if (field.type === 'checkbox' || field.type === 'radio') {
                if (field.required && !form.querySelector('[name="' + field.name + '"]:checked')) {
                    showFieldError(field, 'This option is required.');
                    valid = false;
                }
                return;
            }
            clearFieldError(field);
            if (!field.checkValidity()) {
                showFieldError(field, validationMessage(field));
                valid = false;
            }
        });

        if (!validateChoicePickers(form)) valid = false;
        if (!validatePasswordConfirmation(form)) valid = false;
        if (!validateMinCheckedGroups(form)) valid = false;

        if (!valid) {
            var firstInvalid = form.querySelector('[aria-invalid="true"]');
            if (firstInvalid) {
                firstInvalid.focus({ preventScroll: false });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return valid;
    }

    function initForms() {
        document.querySelectorAll('form').forEach(function (form) {
            if (shouldSkipForm(form)) return;
            if (!form.hasAttribute('novalidate')) form.setAttribute('novalidate', 'novalidate');
        });
    }

    document.addEventListener('input', function (e) {
        var field = e.target;
        if (!field || !field.form || shouldSkipForm(field.form)) return;
        if (field.matches('input, select, textarea')) clearFieldError(field);
    });

    document.addEventListener('change', function (e) {
        var field = e.target;
        if (!field || !field.form || shouldSkipForm(field.form)) return;
        if (field.matches('input, select, textarea')) clearFieldError(field);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (shouldSkipForm(form)) return;
        if (!validateForm(form)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initForms);
    } else {
        initForms();
    }
})();
</script>
