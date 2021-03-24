/**
 * JS file for common admin where expose to public such as login, register, forgot password, etc.
 */


/**
 * JS Admin class that use with the page expose to public.
 * 
 * @since 1.0.12
 */
class RdbaCommonAdminPublic {


    /**
     * Listen on change language and set new language.
     * 
     * Should contain these elements to work.
     * <pre>
     * &lt;select id=&quot;rundizbones-languages-selectbox&quot; class=&quot;rundizbones-languages-selectbox&quot; name=&quot;rundizbones-languages&quot;&gt;
     * &lt;option value=&quot;[LANGUAGE ID]&quot;&gt;[LANGUAGE NAME]&lt;/option&gt;
     * &lt;/select&gt;
     * &lt;input id=&quot;rdbaCurrentUrl&quot; type=&quot;hidden&quot; name=&quot;currentUrl&quot; value=&quot;[YOUR URL]&quot;&gt;
     * &lt;input id=&quot;rdbaSetLanguage_url&quot; type=&quot;hidden&quot; name=&quot;setLanguage_url&quot; value=&quot;[URL TO SET LANGUAGE]&quot;&gt;
     * &lt;input id=&quot;rdbaSetLanguage_method&quot; type=&quot;hidden&quot; name=&quot;setLanguage_method&quot; value=&quot;[SET LANGUAGE METHOD]&quot;&gt;
     * </pre>
     * 
     * @return {undefined}
     */
    static listenOnChangeLanguage() {
        let languageSelectbox = document.getElementById('rundizbones-languages-selectbox');

        if (languageSelectbox) {
            languageSelectbox.addEventListener('change', function(event) {
                event.preventDefault();

                let setLanguageUrl = document.getElementById('rdbaSetLanguage_url');
                let setLanguageMethod = document.getElementById('rdbaSetLanguage_method');
                let currentUrl = document.getElementById('rdbaCurrentUrl');
                let currentLanguageID = document.getElementById('rdbaCurrentLanguageID');

                RdbaCommon.XHR({
                    'url': (setLanguageUrl ? setLanguageUrl.value : ''),
                    'method': (setLanguageMethod ? setLanguageMethod.value : ''),
                    data: 'currentUrl=' + (currentUrl ? currentUrl.value : '') 
                        + '&rundizbones-languages=' + languageSelectbox.value 
                        + '&currentLanguageID=' + (currentLanguageID ? currentLanguageID.value : ''),
                    dataType: 'json'
                })
                .catch(function(responseObject) {
                    // XHR failed.
                    console.error(responseObject);
                })
                .then(function(responseObject) {
                    // XHR success.
                    let response = responseObject.response;
                    if (typeof(response.redirectUrl) !== 'undefined') {
                        window.location.href = response.redirectUrl;
                    }
                });
            });
        }
    }// listenOnChangeLanguage


}// RdbaCommonAdminPublic