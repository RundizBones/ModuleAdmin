/**
 * Previous emails list JS for its controller.
 */


class RdbaUsersPreviousEmailsController {


    /**
     * Ajax get user data including previous emails.
     * 
     * @returns {undefined}
     */
    ajaxGetUserData() {
        RdbaCommon.XHR({
            'url': RdbaPreviousEmails.editUserPreviousEmailsUrl,
            'method': RdbaPreviousEmails.editUserPreviousEmailsMethod
        })
        .catch(function(responseObject) {
            console.error('[rdba] ', responseObject);
            let response = (responseObject ? responseObject.response : {});

            if (typeof(response) !== 'undefined') {
                if (typeof(response.formResultMessage) !== 'undefined') {
                    let alertClass = RdbaCommon.getAlertClassFromStatus(response.formResultStatus);
                    let alertBox = RdbaCommon.renderAlertHtml(alertClass, response.formResultMessage);
                    document.querySelector('.form-result-placeholder').innerHTML = alertBox;
                }
            }
        })
        .then(function(responseObject) {
            let response = (responseObject ? responseObject.response : {});
            let user = (response.user ? response.user : {});

            // config moment locale to use it later with any dates.
            if (RdbaCommon.isset(() => RdbaUIXhrCommonData.currentLocale)) {
                moment.locale(RdbaUIXhrCommonData.currentLocale);
            }
            let siteTimezone;
            if (RdbaCommon.isset(() => RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone)) {
                siteTimezone = RdbaUIXhrCommonData.configDb.rdbadmin_SiteTimezone;
            } else {
                siteTimezone = 'Asia/Bangkok';
            }

            for (let prop in user) {
                if (Object.prototype.hasOwnProperty.call(user, prop) && document.getElementById(prop)) {
                    document.getElementById(prop).innerHTML = user[prop];
                }
            }// endfor;

            // set list of changed emails.
            if (user.user_fields) {
                let source = document.getElementById('list-email-changed-history-table-row-template').innerHTML;
                let template = Handlebars.compile(source);
                Handlebars.registerHelper('formatDate', function (dateValue, options) {
                    if (typeof(dateValue) !== 'undefined') {
                        return moment(dateValue + 'Z').tz(siteTimezone).format('D MMMM YYYY HH:mm:ss Z');
                    } else {
                        return '';
                    }
                });

                for (let i = 0; i < user.user_fields.length; ++i) {
                    if (
                        RdbaCommon.isset(() => user.user_fields[i].field_name) && 
                        RdbaCommon.isset(() => user.user_fields[i].field_value) && 
                        user.user_fields[i].field_name === 'rdbadmin_uf_changeemail_history'
                    ) {
                        let html = template(user.user_fields[i]);
                        document.querySelector('#list-email-changed-history-table tbody').insertAdjacentHTML('afterbegin', html);
                        break;
                    }
                }
            }// endif;
        });
    }// ajaxGetUserData


}// RdbaUsersPreviousEmailsController


document.addEventListener('DOMContentLoaded', function() {
    let previousEmailsController = new RdbaUsersPreviousEmailsController();

    // ajax get user data.
    previousEmailsController.ajaxGetUserData();
}, false);