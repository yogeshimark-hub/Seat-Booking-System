$(document).ready(function () {

    $('.login-form input').on('input', function () {
        $(this).removeClass('input-invalid');
        $(this).next('.js-error').remove();
    });

    $('.login-form').on('submit', function (e) {
        $('.js-error').remove();
        $('.login-form input').removeClass('input-invalid');

        let isValid = true;

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
        }

        if (!isValid) e.preventDefault();
    });

});
