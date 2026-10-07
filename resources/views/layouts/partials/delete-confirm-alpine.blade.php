<script>
document.addEventListener('alpine:init', function () {
    Alpine.store('deleteConfirm', {
        open: false,
        message: 'Are you sure you want to delete this item?',
        detail: '',
        _form: null,
        _callback: null,

        openWith: function (form, message, detail) {
            this._form = form || null;
            this._callback = null;
            this.message = message || 'Are you sure you want to delete this item?';
            this.detail = detail || '';
            this.open = true;
        },

        openWithCallback: function (callback, message, detail) {
            this._form = null;
            this._callback = typeof callback === 'function' ? callback : null;
            this.message = message || 'Are you sure you want to delete this item?';
            this.detail = detail || '';
            this.open = true;
        },

        cancel: function () {
            this.open = false;
            this._form = null;
            this._callback = null;
        },

        confirm: function () {
            if (this._form) {
                this._form.submit();
            } else if (this._callback) {
                this._callback();
            }
            this.cancel();
        },
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-delete-confirm]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        var form = trigger.closest('form');
        if (!form) {
            return;
        }

        var store = Alpine.store('deleteConfirm');
        if (!store) {
            return;
        }

        store.openWith(
            form,
            trigger.getAttribute('data-delete-message') || 'Are you sure you want to delete this item?',
            trigger.getAttribute('data-delete-detail') || ''
        );
    });
});

window.requestDeleteConfirmation = function (options) {
    options = options || {};
    var tryOpen = function () {
        if (typeof Alpine === 'undefined' || !Alpine.store('deleteConfirm')) {
            return false;
        }
        Alpine.store('deleteConfirm').openWithCallback(
            options.onConfirm,
            options.message,
            options.detail
        );
        return true;
    };
    if (!tryOpen()) {
        document.addEventListener('alpine:init', function () {
            tryOpen();
        }, { once: true });
    }
};
</script>
