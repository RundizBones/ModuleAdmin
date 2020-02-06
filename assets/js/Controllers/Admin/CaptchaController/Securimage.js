/**
 * Captcha controller.
 * 
 * IE not supported.
 */


class Securimage {


    /**
     * Class constructor.
     * 
     * @param {object} options
     * @returns {Securimage}
     */
    constructor(options) {
        if (typeof(options) !== 'undefined') {
            if (options.audioId) {
                this.audioId = options.audioId;
            }
            if (options.audioButtonId) {
                this.audioButtonId = options.audioButtonId;
            }
            if (options.audioIconRef) {
                this.audioIconRef = options.audioIconRef;
            }
            if (options.captchaImageUrl) {
                this.captchaImageUrl = options.captchaImageUrl;
            }
            if (options.captchaAudioUrl) {
                this.captchaAudioUrl = options.captchaAudioUrl;
            }
            if (options.reloadButtonId) {
                this.reloadButtonId = options.reloadButtonId;
            }
            if (options.reloadIconRef) {
                this.reloadIconRef = options.reloadIconRef;
            }
        }
    }// constructor


    /**
     * Detect events of the audio and display the certain icon.
     * 
     * @link https://www.w3schools.com/tags/ref_av_dom.asp Audio events reference.
     * @returns {undefined}
     */
    audioEventsIcons() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        this.audioId.on('play', function() {
            console.log('audio is on play');
            removeFontAwesomeAudioIcons();
            thisClass.audioIconRef.addClass('fas fa-spinner fa-pulse');
        });
        this.audioId.on('playing', function() {
            console.log('audio is playing');
            removeFontAwesomeAudioIcons();
            thisClass.audioIconRef.addClass('fas fa-stop-circle');
        });
        this.audioId.on('abort ended pause', function() {
            console.log('audio is abort/ended/pause');
            removeFontAwesomeAudioIcons();
            thisClass.audioIconRef.addClass('fas fa-volume-up');
        });

        function removeFontAwesomeAudioIcons() {
            thisClass.audioIconRef.removeClass('fas far fab fa-volume-up fa-spinner fa-pulse fa-stop-circle');
        }
    }// audioEventsIcons


    /**
     * On click play audio button.
     * 
     * @link https://stackoverflow.com/a/15930356/128761 Stop audio.
     * @returns {undefined}
     */
    onPlay() {
        let thisClass = this;

        document.addEventListener('click', function(event) {
            for (let target= event.target; target && target != this; target = target.parentNode) {
                if (thisClass.audioButtonId && target.matches('#'+thisClass.audioButtonId[0].id)) {
                    event.preventDefault();
                    if (!thisClass.audioId[0].paused) {
                        // if audio is playing.
                        thisClass.stopAudio();
                        return ;
                    } else {
                        thisClass.audioId[0].play();
                    }
                    break;
                }
            }
        });
    }// onPlay


    /**
     * On click reload new captcha image.
     * 
     * @link https://stackoverflow.com/a/39334998/128761 Loaded target reference.
     * @returns {undefined}
     */
    onReload() {
        let $ = jQuery.noConflict();
        let thisClass = this;

        document.addEventListener('click', function(event) {
            for (let target= event.target; target && target != this; target = target.parentNode) {
                if (thisClass.reloadButtonId && target.matches('#'+thisClass.reloadButtonId[0].id)) {
                    event.preventDefault();

                    thisClass.reloadIconRef.addClass('fa-spin');
                    // the audio should stop immediately when reload captcha button was pressed (if it is playing).
                    thisClass.stopAudio();

                    let jQueryThis = $(target);
                    $(jQueryThis.data('target')).attr('src', thisClass.captchaImageUrl + '?' + (Math.random() + '').replace('0.', ''));
                    $(jQueryThis.data('target')).on('load', function() {
                        console.log('captcha image loaded');
                        thisClass.reloadIconRef.removeClass('fa-spin');
                        thisClass.reloadAudioSource();
                    });

                    break;
                }
            }
        });
    }// onReload


    /**
     * Reload audio source.
     * 
     * @link https://stackoverflow.com/a/9512994/128761 Switch audio reference.
     * @returns {undefined}
     */
    reloadAudioSource() {
        let $ = jQuery.noConflict();

        if (typeof(this.audioId) !== 'undefined' && typeof(this.audioId[0]) !== 'undefined') {
            // stop audio in case that it is playing.
            this.stopAudio();

            console.log('changing audio source');
            this.audioId.find('source').attr('src', this.captchaAudioUrl + '?newId=' + (Math.random() + '').replace('0.', ''));
            console.log('sending command to load audio source');
            this.audioId[0].load();
        }
    }// reloadAudioSource


    /**
     * Stop the audio if it is playing.
     * 
     * @returns {undefined}
     */
    stopAudio() {
        if (!this.audioId[0].paused) {
            console.log('audio is currently playing, going to stop it');
            this.audioId[0].pause();// pause
            this.audioId[0].currentTime = 0;// set time to zero == stop.
        }
        console.log('audio had stopped');
    }// stopAudio


}


// on dom ready --------------------------------------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    /*
    let $ = jQuery.noConflict();
    let example = new Securimage({
        'audioId': $('#rd-downloads-captcha-audio'),
        'audioButtonId': $('#rd-downloads-captcha-audio-controls'),
        'audioIconRef': $('.fontawesome-icon.icon-play-audio'),
        'reloadButtonId': $('#rd-downloads-captcha-reload'),
        'reloadIconRef': $('.fontawesome-icon.icon-reload')
    });

    // Listen to audio events and display certain icon.
    example.audioEventsIcons();
    // On reload new captcha image.
    example.onReload();
    // On play captcha audio.
    example.onPlay();
    */
}, false);