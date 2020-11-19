/**
 * Login JS for its controller.
 */


class RdbaLoginController {


    /**
     * Ajax login form.
     * 
     * @returns {undefined}
     */
    ajaxLogin() {
        let $ = jQuery.noConflict();

        $('#rdba-login-form').on('submit', function(e) {
            e.preventDefault();

            // reset form result placeholder.
            $('.form-result-placeholder').html('');
            // add spinner icon
            $('.login-status-icon').remove();
            $('.submit-button-row .control-wrapper').append('<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock login button
            $('.rdba-login-button').attr('disabled', 'disabled');

            let formData = $(this).serialize();
            formData += '&' + RdbaLogin.csrfName + '=' + RdbaLogin.csrfKeyPair[RdbaLogin.csrfName];
            formData += '&' + RdbaLogin.csrfValue + '=' + RdbaLogin.csrfKeyPair[RdbaLogin.csrfValue];
            formData += '&gobackUrl=' + RdbaLogin.gobackUrl;

            $.ajax({
                url: RdbaLogin.loginUrl,
                method: RdbaLogin.loginMethod,
                data: formData,
                dataType: 'json'
            })
            .done(function(data, textStatus, jqXHR) {
                let response = data;

                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    $('.form-result-placeholder').html(alertBox);
                }

                // add success icon on login success.
                $('.login-status-icon').remove();
                $('.submit-button-row .control-wrapper').append('<i class="fas fa-check fa-fw login-status-icon" aria-hidden="true"></i>');

                if (typeof(response.loggedIn) !== 'undefined' && response.loggedIn === true) {
                    // if login really success, redirect.
                    window.location.href = RdbaLogin.gobackUrl;
                } else if (typeof(response.redirectUrl) !== 'undefined') {
                    // if have to redirect (such as 2 step verification page).
                    window.location.href = response.redirectUrl;
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                let response;
                if (typeof(jqXHR) === 'object' && typeof(jqXHR.responseJSON) !== 'undefined') {
                    response = jqXHR.responseJSON;
                } else if (typeof(jqXHR) === 'object' && typeof(jqXHR.responseText) !== 'undefined') {
                    response = jqXHR.responseText;
                } else {
                    response = jqXHR;
                }
                if (typeof(response) === 'undefined' || response === null) {
                    response = {};
                }

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        let alertBox = RdbaCommon.renderAlertHtml('alert-danger', response.formResultMessage);
                        $('.form-result-placeholder').html(alertBox);
                    }
                }

                // remove login status icon
                $('.login-status-icon').remove();

                if (RdbaLogin.requireCaptcha === true) {
                    $('#rdba-login-form #captcha-reload').trigger('click');
                }
            })
            .always(function(data, textStatus, jqXHR) {
                let response;
                if (typeof(data) === 'object' && typeof(data.responseJSON) !== 'undefined') {
                    response = data.responseJSON;
                } else if (typeof(data) === 'object' && typeof(data.responseText) !== 'undefined') {
                    response = data.responseText;
                } else {
                    response = data;
                }
                if (typeof(response) === 'undefined' || response === null) {
                    response = {};
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaLogin.csrfKeyPair = response.csrfKeyPair;
                }

                // unlock login button
                $('.rdba-login-button').removeAttr('disabled');
                // remove spinner icon
                $('.loading-icon').remove();
            });
        });
    }// ajaxLogin


    /**
     * Check if require captcha then display it.
     * 
     * @returns {undefined}
     */
    isRequireCaptcha() {
        let $ = jQuery.noConflict();

        if (RdbaLogin.requireCaptcha === true) {
            let source = document.getElementById('captcha-field').innerHTML;
            let template = Handlebars.compile(source);
            let html = template(RdbaLogin);

            // display captcha from template tags.
            $('#rdba-login-form .form-group-captcha').remove();
            $('#rdba-login-form').find('.form-group-password').after(html);
            $('#rdba-login-form .form-group-captcha #captcha-image').attr('src', RdbaLogin.getCaptchaImage + '?id=' + (Math.random() + '').replace('0.', ''));
            $('#rdba-login-form .form-group-captcha #captcha-audio-player-source-wav').attr('src', RdbaLogin.getCaptchaAudio + '?id=' + (Math.random() + '').replace('0.', ''));// require random id.

            let securimage = new Securimage({
                'audioId': $('#captcha-audio-player'),
                'audioButtonId': $('#captcha-audio-controls'),
                'audioIconRef': $('.fontawesome-icon.icon-play-audio'),
                'captchaImageUrl': RdbaLogin.getCaptchaImage,
                'captchaAudioUrl': RdbaLogin.getCaptchaAudio,
                'reloadButtonId': $('#captcha-reload'),
                'reloadIconRef': $('.fontawesome-icon.icon-reload')
            });
            // Listen to audio events and display certain icon.
            securimage.audioEventsIcons();
            // On reload new captcha image.
            securimage.onReload();
            // On play captcha audio.
            securimage.onPlay();
        }
    }// isRequireCaptcha


    /**
     * Show or hide links depend on configurations.
     * 
     * @returns {undefined}
     */
    showOrHideLinks() {
        let $ = jQuery.noConflict();

        if (RdbaLogin.configDb.rdbadmin_UserRegister === '0') {
            $('#link-register-new-account').parent().hide();
        }
    }// showOrHideLinks


}// RdbaLoginController


document.addEventListener('DOMContentLoaded', function() {
    let rdbaLoginController = new RdbaLoginController;

    // check if require captcha then display it, otherwise it cannot pass the login process.
    rdbaLoginController.isRequireCaptcha();

    // show or hide links such as register.
    rdbaLoginController.showOrHideLinks();

    // ajax login.
    rdbaLoginController.ajaxLogin();

    // detect language change.
    RdbaCommonAdminPublic.listenOnChangeLanguage();
}, false);