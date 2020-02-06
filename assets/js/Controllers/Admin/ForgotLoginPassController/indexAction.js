/**
 * Forgot login & password JS for its controller.
 */


class RdbaForgotLoginPassController {


    /**
     * Activate captcha.
     * 
     * @returns {undefined}
     */
    activateCaptcha() {
        let $ = jQuery.noConflict();

        $('#rdba-forgot-form .form-group-captcha #captcha-image').attr('src', RdbaForgotLP.getCaptchaImage + '?id=' + (Math.random() + '').replace('0.', ''));
        $('#rdba-forgot-form .form-group-captcha #captcha-audio-player-source-wav').attr('src', RdbaForgotLP.getCaptchaAudio + '?id=' + (Math.random() + '').replace('0.', ''));// require random id.
        $('#rdba-forgot-form #captcha-audio-player')[0].load();// required this to be able to play after page load.

        let securimage = new Securimage({
            'audioId': $('#captcha-audio-player'),
            'audioButtonId': $('#captcha-audio-controls'),
            'audioIconRef': $('.fontawesome-icon.icon-play-audio'),
            'captchaImageUrl': RdbaForgotLP.getCaptchaImage,
            'captchaAudioUrl': RdbaForgotLP.getCaptchaAudio,
            'reloadButtonId': $('#captcha-reload'),
            'reloadIconRef': $('.fontawesome-icon.icon-reload')
        });
        // Listen to audio events and display certain icon.
        securimage.audioEventsIcons();
        // On reload new captcha image.
        securimage.onReload();
        // On play captcha audio.
        securimage.onPlay();
    }// activateCaptcha


    /**
     * Ajax submit request reset password.
     * 
     * @returns {undefined}
     */
    ajaxSubmit() {
        let $ = jQuery.noConflict();

        $('#rdba-forgot-form').on('submit', function(e) {
            e.preventDefault();

            // reset form result placeholder.
            $('.form-result-placeholder').html('');
            // add spinner icon
            $('.submit-status-icon').remove();
            $('.submit-button-row .control-wrapper').append('<i class="fas fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            $('.rdba-submit-button').attr('disabled', 'disabled');

            let formData = $(this).serialize();
            formData += '&' + RdbaForgotLP.csrfName + '=' + RdbaForgotLP.csrfKeyPair[RdbaForgotLP.csrfName];
            formData += '&' + RdbaForgotLP.csrfValue + '=' + RdbaForgotLP.csrfKeyPair[RdbaForgotLP.csrfValue];

            $.ajax({
                url: RdbaForgotLP.forgotLoginPassUrl,
                method: RdbaForgotLP.forgotLoginPassMethod,
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

                // add success icon on submit success.
                $('.submit-status-icon').remove();
                $('.submit-button-row .control-wrapper').append('<i class="fas fa-check fa-fw submit-status-icon" aria-hidden="true"></i>');

                if (typeof(response.forgotLoginPasswordStep1) !== 'undefined' && response.forgotLoginPasswordStep1 === 'success') {
                    setTimeout(function() {
                        $('#rdba-forgot-form').fadeOut('fast');
                        $('.rdba-hr-form-separator').fadeOut('fast');
                        $('.rdba-language-switch-form').fadeOut('fast');
                    }, 500);
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

                // remove submit status icon
                $('.submit-status-icon').remove();

                // trigger reload captcha.
                $('#rdba-forgot-form #captcha-reload').trigger('click');
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
                    RdbaForgotLP.csrfKeyPair = response.csrfKeyPair;
                }

                // unlock submit button
                $('.rdba-submit-button').removeAttr('disabled');
                // remove spinner icon
                $('.loading-icon').remove();
            });
        });
    }// ajaxSubmit


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaForgotLPController = new RdbaForgotLoginPassController();

    // activate captcha.
    rdbaForgotLPController.activateCaptcha();

    // ajax submit
    rdbaForgotLPController.ajaxSubmit();

    // detect language change.
    RdbaCommon.onChangeLanguage();
}, false);