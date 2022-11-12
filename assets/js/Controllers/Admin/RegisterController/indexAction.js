/* 
 * Register JS for its controller.
 */


class RdbaRegisterController {


    /**
     * Ajax submit register form.
     * 
     * @returns {undefined}
     */
    ajaxRegister() {
        let $ = jQuery.noConflict();

        $('#rdba-register-form').on('submit', function(e) {
            e.preventDefault();

            // reset form result placeholder.
            $('.form-result-placeholder').html('');
            // add spinner icon
            $('.submit-status-icon').remove();
            $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-spinner fa-pulse fa-fw loading-icon" aria-hidden="true"></i>');
            // lock submit button
            $('.rdba-submit-button').attr('disabled', 'disabled');

            let formData = $(this).serialize();
            formData += '&' + RdbaRegister.csrfName + '=' + encodeURIComponent(RdbaRegister.csrfKeyPair[RdbaRegister.csrfName]);
            formData += '&' + RdbaRegister.csrfValue + '=' + encodeURIComponent(RdbaRegister.csrfKeyPair[RdbaRegister.csrfValue]);

            $.ajax({
                url: RdbaRegister.registerUrl,
                method: RdbaRegister.registerMethod,
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

                // add success icon.
                $('.submit-status-icon').remove();
                $('.submit-button-row .control-wrapper').append('<i class="fa-solid fa-check fa-fw submit-status-icon" aria-hidden="true"></i>');
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
                        let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                        $('.form-result-placeholder').html(alertBox);
                    }
                }

                // remove submit status icon
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
                    RdbaRegister.csrfKeyPair = response.csrfKeyPair;
                }

                // unlock submit button
                $('.rdba-submit-button').removeAttr('disabled');
                // remove spinner icon
                $('.loading-icon').remove();
            });
        });
    }// ajaxRegister


}


document.addEventListener('DOMContentLoaded', function() {
    let rdbaRegisterController = new RdbaRegisterController;

    // ajax submit
    rdbaRegisterController.ajaxRegister();

    // detect language change.
    RdbaCommonAdminPublic.listenOnChangeLanguage();
}, false);