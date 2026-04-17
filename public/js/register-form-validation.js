$(document).ready(function () {

    $('.register-form input').on('input', function () {
        $(this).removeClass('input-invalid');
        $(this).next('.js-error').remove();
    });

    $('.register-form').on('submit', function (e) {
        $('.js-error').remove();
        $('.register-form input').removeClass('input-invalid');

        let isValid = true;

        const name = $('#name').val().trim();
        if (name === '') {
            $('#name').addClass('input-invalid').after('<span class="error js-error">Name is required.</span>');
            isValid = false;
        }

        const email = $('#email').val().trim();
        if (email === '') {
            $('#email').addClass('input-invalid').after('<span class="error js-error">Email is required.</span>');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#email').addClass('input-invalid').after('<span class="error js-error">Enter a valid email.</span>');
            isValid = false;
        }

        const password = $('#password').val();
        if (password === '') {
            $('#password').addClass('input-invalid').after('<span class="error js-error">Password is required.</span>');
            isValid = false;
        } else if (password.length < 6) {
            $('#password').addClass('input-invalid').after('<span class="error js-error">Password must be at least 6 characters.</span>');
            isValid = false;
        }

        const confirm = $('#password_confirmation').val();
        if (confirm === '') {
            $('#password_confirmation').addClass('input-invalid').after('<span class="error js-error">Please confirm your password.</span>');
            isValid = false;
        } else if (confirm !== password) {
            $('#password_confirmation').addClass('input-invalid').after('<span class="error js-error">Passwords do not match.</span>');
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });

});
