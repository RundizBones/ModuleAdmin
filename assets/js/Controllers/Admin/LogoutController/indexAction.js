/**
 * Logout JS for its controller.
 */


class RdbaLogoutController {


    /**
     * Detect form submit and do ajax logout.
     * 
     * @returns {undefined}
     */
    ajaxLogout() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        $('#rdba-logout-form').on('submit', function(e) {
            e.preventDefault();

            // reset form result placeholder.
            $('.form-result-placeholder').html('');
            // add loggint out message.
            let loggingOutClass = RdbaCommon.getAlertClassFromStatus('info');
            let loggintOutMsg = RdbaCommon.renderAlertHtml(loggingOutClass, '<i class="fa-solid fa-spinner fa-pulse"></i> ' + RdbaLogout.txtLoggintOut);
            $('.form-result-placeholder').html(loggintOutMsg);
            // lock submit button
            $('.rdba-submit-button').attr('disabled', 'disabled');

            let xhrDeferred = thisClass.doLogout();
            xhrDeferred
            .done(function(data, textStatus, jqXHR) {
                let response = data;

                let alertClass = RdbaCommon.getAlertClassFromStatus('success');
                let alertBox = RdbaCommon.renderAlertHtml(alertClass, '<i class="fa-solid fa-right-from-bracket"></i> ' + RdbaLogout.txtYouLoggedOut);
                $('.form-result-placeholder').html(alertBox);

                if (response.loggedOut === true) {
                    if (RdbaLogout.fastLogout === true) {
                        window.location.replace(RdbaLogout.loginUrl);
                    } else {
                        setTimeout(function() {
                            window.location.href = RdbaLogout.loginUrl;
                        }, 2500);
                    }
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
                        let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                        $('.form-result-placeholder').html(alertBox);
                    }
                } else {
                    $('.form-result-placeholder').html('');// clear form result placeholder.
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

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultMessage) !== 'undefined') {
                        let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                        let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                        $('.form-result-placeholder').html(alertBox);
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaLogout.csrfKeyPair = response.csrfKeyPair;
                }

                // unlock submit button
                $('.rdba-submit-button').removeAttr('disabled');
            })// .always
            ;
        });
    }// ajaxLogout


    /**
     * Do ajax logout.
     * 
     * @private This method was called from `ajaxLogout()`.
     * @returns {undefined}
     */
    doLogout() {
        let $ = jQuery.noConflict();

        let formData = $('#rdba-logout-form').serialize();
            formData += '&' + RdbaLogout.csrfName + '=' + encodeURIComponent(RdbaLogout.csrfKeyPair[RdbaLogout.csrfName]);
            formData += '&' + RdbaLogout.csrfValue + '=' + encodeURIComponent(RdbaLogout.csrfKeyPair[RdbaLogout.csrfValue]);

        return $.ajax({
            url: RdbaLogout.logoutUrl,
            method: RdbaLogout.logoutMethod,
            data: formData,
            dataType: 'json'
        });
    }// doLogout


}// RdbaLogoutController


jQuery(window).on('load', function() {
    // on the whole page was loaded.
    let rdbaLogoutController = new RdbaLogoutController();

    // detect logout button and do ajax logout.
    rdbaLogoutController.ajaxLogout();

    // on fast logout, trigger submit logout.
    let urlParams = new URLSearchParams(location.search);
    if (urlParams.get('fastLogout') === 'true') {
        jQuery('#rdba-logout-form').trigger('submit');
    }
});