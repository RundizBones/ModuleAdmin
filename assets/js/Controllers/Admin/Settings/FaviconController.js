/**
 * Favicon settings page JS for its controller.
 */


class RdbaSettingsFaviconController {


    constructor() {
        this.uploadFaviconDisabled = false;
    }// constructor


    /**
     * Listen click delete favicon, if confirmed then delete it.
     * 
     * @returns {undefined}
     */
    #listenClickDelete() {
        let thisClass = this;
        let deleteButtonId = 'prog-delete-favicon-button';

        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.getAttribute('id') === deleteButtonId) {
                event.preventDefault();

                let confirmVal = confirm(RdbaSettings.txtConfirmDelete);
                if (true === confirmVal) {
                    // if confirmed.
                    let uploadStatusPlaceholder = document.getElementById('rdbadmin-favicon-upload-status-placeholder');
                    let chooseFileBtn = document.getElementById('rdbadmin-favicon-choose-files-button');
                    let inputFile = document.getElementById('rdbadmin_SiteFavicon');
                    let dropzone = document.getElementById('rdbadmin-favicon-dropzone');

                    // disable form fields.
                    thisClass.uploadFaviconDisabled = true;
                    thisTarget.disabled = true;
                    thisTarget.classList.add('disabled');
                    chooseFileBtn.disabled = true;
                    chooseFileBtn.classList.add('disabled');
                    dropzone.disabled = true;
                    dropzone.classList.add('disabled');
                    inputFile.disabled = true;
                    // add loading icon.
                    uploadStatusPlaceholder.innerHTML = '&nbsp;<i class="fa-solid fa-spinner fa-pulse loading-icon"></i> '

                    let formData = new FormData();
                    formData.append(RdbaSettings.csrfName, RdbaSettings.csrfKeyPair[RdbaSettings.csrfName]);
                    formData.append(RdbaSettings.csrfValue, RdbaSettings.csrfKeyPair[RdbaSettings.csrfValue]);

                    RdbaCommon.XHR({
                        'url': RdbaSettings.urls.deleteFaviconUrl,
                        'method': RdbaSettings.urls.deleteFaviconMethod,
                        'contentType': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'data': new URLSearchParams(_.toArray(formData)).toString(),
                        'dataType': 'json',
                    })
                    .then((responseObject) => {
                        // XHR success.
                        let response = responseObject.response;

                        if (typeof(response) !== 'undefined') {
                            if (typeof(response.formResultMessage) !== 'undefined') {
                                RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                            }
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                        }

                        thisClass.displayFavicon('');

                        return Promise.resolve(responseObject);
                    })
                    .catch((responseObject) => {
                        // XHR failed.
                        let response = responseObject.response;
                        console.error(responseObject);

                        if (response.formResultMessage) {
                            RDTAAlertDialog.alert({
                                'html': response.formResultMessage,
                                'type': 'danger'
                            });
                        }

                        if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                            RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                        }

                        return Promise.reject(responseObject);
                    })
                    .finally(function() {
                        // restore disabled form fields
                        thisClass.uploadFaviconDisabled = false;
                        thisTarget.disabled = false;
                        thisTarget.classList.remove('disabled');
                        chooseFileBtn.disabled = false;
                        chooseFileBtn.classList.remove('disabled');
                        dropzone.disabled = false;
                        dropzone.classList.remove('disabled');
                        inputFile.disabled = false;
                        // remove loading icon.
                        uploadStatusPlaceholder.innerHTML = '';
                    });
                }
            }
        });
    }// listenClickDelete


    /**
     * Listen drag and drop file if drop file(s) are in drop zone then set files value to input file and then  trigger `change` event on input file.
     * 
     * @returns {undefined}
     */
    #listenDragDropFile() {
        let thisClass = this;
        const dropZoneClassName = 'rdbadmin-file-dropzone';

        // prevent drag & drop image file outside drop zone. --------------------------------------------------
        function preventDragEnter(event) {
            event.preventDefault();// prevent redirect page to show dropped image.

            let thisTarget = event.target;
            // must use common class name for drop zone to let other module that hook into this page can control their drop zone.
            // the common class name is `rdbadmin-file-dropzone`.
            let closestElement = thisTarget.closest('.' + dropZoneClassName);

            if (closestElement === '' || closestElement === null) {
                event.dataTransfer.effectAllowed = 'none';
                event.dataTransfer.dropEffect = 'none';
            }
        }// preventDragEnter

        window.addEventListener('dragenter', preventDragEnter, false);
        window.addEventListener('dragover', preventDragEnter);
        // end prevent drag & drop image file outside drop zone. ---------------------------------------------

        window.addEventListener('drop', (event) => {
            event.preventDefault();

            let thisTarget = event.target;
            let closestElement = thisTarget.closest('.' + dropZoneClassName);

            if (closestElement !== '' && closestElement !== null) {
                // if dropped in drop zone or input file.
                let inputFileElement = closestElement.querySelector('input[type="file"]');// always call this, not use declared on the top to force get new data.
                inputFileElement.files = event.dataTransfer.files;
                inputFileElement.dispatchEvent(new Event('change', { 'bubbles': true }));
            } else {
                // if not dropped in drop zone and input file.
                event.dataTransfer.effectAllowed = 'none';
                event.dataTransfer.dropEffect = 'none';
            }
        });
    }// listenDragDropFile


    /**
     * Listen favicon file change, drop file and then start upload.
     * 
     * @returns {undefined}
     */
    #listenFileChange() {
        let thisClass = this;
        let inputFileId = 'rdbadmin_SiteFavicon';
        let inputFileElement = document.querySelector('#' + inputFileId);

        document.addEventListener('rdta.custominputfile.change', (event) => {
            let uploadStatusPlaceholder = document.getElementById('rdbadmin-favicon-upload-status-placeholder');

            inputFileElement = event.target;// force get new data.
            if (inputFileElement.getAttribute('id') !== inputFileId) {
                // if not matched input file id for RdbAdmin favicon file.
                // not working here.
                return ;
            }

            if (thisClass.uploadFaviconDisabled === true) {
                // if upload form functional is disabled.
                // not working here.
                return ;
            }

            if (inputFileElement.files.length > 1) {
                // if too many files were selected.
                // alert and stop working here.
                RDTAAlertDialog.alert({
                    'type': 'error',
                    'text': RdbaSettings.txtPleaseChooseOneFile
                });
                return ;
            }

            // add loading icon.
            uploadStatusPlaceholder.innerHTML = '&nbsp;<i class="fa-solid fa-spinner fa-pulse loading-icon"></i> ' + RdbaSettings.txtUploading;

            let formData = new FormData();
            formData.append(RdbaSettings.csrfName, RdbaSettings.csrfKeyPair[RdbaSettings.csrfName]);
            formData.append(RdbaSettings.csrfValue, RdbaSettings.csrfKeyPair[RdbaSettings.csrfValue]);
            formData.append('rdbadmin_SiteFavicon', inputFileElement.files[0]);

            RdbaCommon.XHR({
                'url': RdbaSettings.urls.uploadFaviconUrl,
                'method': RdbaSettings.urls.uploadFaviconMethod,
                //'contentType': 'multipart/form-data',// do not set `contentType` because it is already set in `formData`.
                'data': formData,
                'dataType': 'json',
            })
            .then(function(responseObject) {
                // XHR success.
                let response = responseObject.response;

                if (typeof(response) !== 'undefined') {
                    if (typeof(response.formResultStatus) !== 'undefined' && response.formResultStatus === 'warning') {
                        RDTAAlertDialog.alert({
                            'type': response.formResultStatus,
                            'text': response.formResultMessage
                        });
                    } else {
                        if (typeof(response.formResultMessage) !== 'undefined') {
                            RdbaCommon.displayAlertboxFixed(response.formResultMessage, response.formResultStatus);
                        }
                    }

                    if (typeof(response.uploadResult) !== 'undefined' && response.uploadResult === true) {
                        // if there is at least one file uploaded successfully.
                        // reset input file.
                        inputFileElement.value = '';
                    }

                    thisClass.displayFavicon(response.uploadedUrl);// working
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.resolve(responseObject);
            })
            .catch(function(responseObject) {
                // XHR failed.
                let response = responseObject.response;

                if (response && response.formResultMessage) {
                    RDTAAlertDialog.alert({
                        'type': 'danger',
                        'text': response.formResultMessage
                    });
                } else {
                    if (RdbaCommon.isset(() => responseObject.status) && responseObject.status === 500) {
                        RDTAAlertDialog.alert({
                            'type': 'danger',
                            'text': 'Internal Server Error'
                        });
                    }
                }

                if (typeof(response) !== 'undefined' && typeof(response.csrfKeyPair) !== 'undefined') {
                    RdbaSettings.csrfKeyPair = response.csrfKeyPair;
                }

                return Promise.reject(responseObject);
            })
            .finally(function() {
                // remove loading icon and upload status text.
                uploadStatusPlaceholder.innerHTML = '';
            });
        });// end document.addEventListener;
    }// listenFileChange


    /**
     * Display favicon image.
     * 
     * Also display delete favicon button if there is an image URL, otherwise hide this button.
     * 
     * @param {string} imgSrc The image URL to use in `&lt;img src="..."&gt;`.
     * @returns {undefined}
     */
    displayFavicon(imgSrc) {
        let previewElement = document.getElementById('current-favicon-preview');
        let deleteFaviconBtn = document.getElementById('prog-delete-favicon-button');

        if (imgSrc !== '') {
            previewElement.innerHTML = '<a href="' + imgSrc + '" target="_blnak"><img class="rdbadmin-favicon-preview" src="' + imgSrc + '" alt=""></a>';
            deleteFaviconBtn.classList.remove('rd-hidden');
        } else {
            previewElement.innerHTML = '';
            deleteFaviconBtn.classList.add('rd-hidden');
        }
    }// displayFavicon


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        // listen drag and drop file to trigger `change` event on input file.
        this.#listenDragDropFile();
        // listen favicon file change, drop file and then start upload.
        this.#listenFileChange();
        // listen click delete favicon, if confirmed then delete it.
        this.#listenClickDelete();
    }// init


}// RdbaSettingsFaviconController


document.addEventListener('DOMContentLoaded', () => {
    let rdbaSettingsFaviconController = new RdbaSettingsFaviconController();

    // initialize class.
    rdbaSettingsFaviconController.init();
});