define(['core/log'], function(log) {
    /**
     *
     */
    function checkTinyMCELoaded() {

        if (tinymce === undefined || tinymce === null) {
            window.console.log("searching tinymce !!!");
             return;
            }
        window.console.log("tinymce !!!");
        if (tinymce.editors === undefined) {
            window.console.log(tinymce);
         return;
        }
        window.console.log("tinymce !!!");
        return tinymce.editors.every(function(editor) {
            return editor.initialized;
        });
    }

    /**
     *
     * @param {function} callback
     */
    function waitForTinyMCE(callback) {
        if (checkTinyMCELoaded()) {
            callback();
        } else {
            setTimeout(function() {
                waitForTinyMCE(callback);
            }, 100);
        }
    }

    return {
        init: function() {
            waitForTinyMCE(function() {
                log.debug('All TinyMCE instances are loaded');
                // Perform actions after all TinyMCE instances are loaded
            });
        }
    };
});
