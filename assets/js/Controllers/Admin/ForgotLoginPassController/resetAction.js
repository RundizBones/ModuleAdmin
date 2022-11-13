/**
 * Reset password.
 */


class RdbaForgotLoginPassResetController {


    /**
     * Ajax hidden login form.
     * 
     * @returns {undefined}
     */
    ajaxLogin() {
        let $ = jQuery.noConflict();

        $('#rdba-login-form').on('submit', function(e) {
            e.preventDefault();

            // add spinner icon
            $('.loading-icon').remove();
            $('.submit-status-icon').remove();
            $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            $('.rdba-submit-button').attr('disabled', 'disabled');

            let formData = new FormData($(this)[0]);
            formData.append(RdbaForgotLPR.csrfName, RdbaForgotLPR.csrfKeyPair[RdbaForgotLPR.csrfName]);
            formData.append(RdbaForgotLPR.csrfValue, RdbaForgotLPR.csrfKeyPair[RdbaForgotLPR.csrfValue]);
            formData = new URLSearchParams(formData).toString();

            $.ajax({
                url: RdbaForgotLPR.loginUrl,
                method: RdbaForgotLPR.loginMethod,
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
                $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-check fa-fw submit-status-icon" aria-hidden="true"></i>');

                if (typeof(response.loggedIn) !== 'undefined' && response.loggedIn === true) {
                    // if login really success, redirect.
                    window.location.href = RdbaForgotLPR.gobackUrl;
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
                $('.submit-status-icon').remove();
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
                    RdbaForgotLPR.csrfKeyPair = response.csrfKeyPair;
                }

                // unlock submit button
                $('.rdba-submit-button').removeAttr('disabled');
                // remove spinner icon
                $('.loading-icon').remove();
            });
        });
    }// ajaxLogin


    /**
     * Ajax submit change new password.
     * 
     * @returns {undefined}
     */
    ajaxSubmit() {
        let $ = jQuery.noConflict();

        $('#rdba-forgot-form-reset').on('submit', function(e) {
            e.preventDefault();

            // reset form result placeholder.
            $('.form-result-placeholder').html('');
            // add spinner icon
            $('.submit-status-icon').remove();
            $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            $('.rdba-submit-button').attr('disabled', 'disabled');
            // copy password to hidden login form.
            $('#user_password').val($('#new_password').val());

            let formData = new FormData($(this)[0]);
            formData.append(RdbaForgotLPR.csrfName, RdbaForgotLPR.csrfKeyPair[RdbaForgotLPR.csrfName]);
            formData.append(RdbaForgotLPR.csrfValue, RdbaForgotLPR.csrfKeyPair[RdbaForgotLPR.csrfValue]);
            formData = new URLSearchParams(formData).toString();

            $.ajax({
                url: RdbaForgotLPR.forgotLoginPassUrl,
                method: RdbaForgotLPR.forgotLoginPassMethod,
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
                $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-check fa-fw submit-status-icon" aria-hidden="true"></i>');

                if (typeof(response.forgotLoginPasswordStep2) !== 'undefined' && response.forgotLoginPasswordStep2 === 'success') {
                    $('#rdba-forgot-form-reset').addClass('rd-hidden');
                    $('#rdba-login-form').removeClass('rd-hidden');
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
                $('.submit-status-icon').remove();
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
                    RdbaForgotLPR.csrfKeyPair = response.csrfKeyPair;
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
    let rdbaForgotLPRController = new RdbaForgotLoginPassResetController();

    // ajax submit.
    rdbaForgotLPRController.ajaxSubmit();

    // ajax login.
    rdbaForgotLPRController.ajaxLogin();

    // detect language change.
    RdbaCommonAdminPublic.listenOnChangeLanguage();
}, false);