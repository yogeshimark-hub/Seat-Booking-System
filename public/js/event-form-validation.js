$(document).ready(function () {

    function showError($field, message) {
        $field.addClass('input-invalid');
        if ($field.next('.js-error').length) {
            $field.next('.js-error').text(message);
        } else {
            $field.after('<span class="error js-error">' + message + '</span>');
        }
    }

    function clearError($field) {
        $field.removeClass('input-invalid');
        $field.next('.js-error').remove();
    }

    function validateField($field) {
        const id = $field.attr('id');
        const val = $field.val() ? $field.val().toString().trim() : '';

        if (id === 'event_name') {
            if (val === '') { showError($field, 'Event name is required.'); return false; }
        }
        if (id === 'event_venue') {
            if (val === '') { showError($field, 'Venue is required.'); return false; }
        }
        if (id === 'event_date') {
            if (val === '') { showError($field, 'Event date is required.'); return false; }
            if (new Date(val) <= new Date()) { showError($field, 'Event date must be in the future.'); return false; }
        }
        if (id === 'total_rows') {
            const rows = parseInt(val, 10);
            if (!rows || rows < 1) { showError($field, 'Rows must be at least 1.'); return false; }
            if (rows > 26) { showError($field, 'Maximum 26 rows allowed.'); return false; }
        }
        if (id === 'total_columns') {
            const cols = parseInt(val, 10);
            if (!cols || cols < 1) { showError($field, 'Columns must be at least 1.'); return false; }
            if (cols > 50) { showError($field, 'Maximum 50 columns allowed.'); return false; }
        }

        clearError($field);
        return true;
    }

    // Live validation: clear / re-check as the user types or changes a field
    $('.event-form').on('input change', 'input', function () {
        validateField($(this));
    });

    // Submit handler: validate all fields, block submit if any invalid
    $('.event-form').on('submit', function (e) {
        let isValid = true;
        const fields = ['#event_name', '#event_venue', '#event_date', '#total_rows', '#total_columns'];

        fields.forEach(function (sel) {
            const $f = $(sel);
            if ($f.length && !validateField($f)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            $('.event-form').find('.input-invalid').first().focus();
        }
    });

});
