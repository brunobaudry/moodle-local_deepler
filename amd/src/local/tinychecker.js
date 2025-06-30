define(['core/log'], (log)=> {
    /**
     * Find tinyMCEs
     *
     * @returns {*}
     */
    const checkTinyMCELoaded = ()=> {
        if (tinymce === undefined || tinymce === null) {
            window.console.log("searching tinymce !!!");
             return false;
            }
        window.console.log("tinymce !!!");
        if (tinymce.editors === undefined) {
            window.console.log(tinymce);
         return false;
        }
        window.console.log("tinymce !!!");
        return tinymce.editors.every(function(editor) {
            return editor.initialized;
        });
    };

    /**
     * Listener.
     *
     * @param {function} callback
     */
    const waitForTinyMCE = (callback)=> {
        if (checkTinyMCELoaded()) {
            callback();
        } else {
            setTimeout(function() {
                waitForTinyMCE(callback);
            }, 100);
        }
    };
    /**
     * Api to be used by the other modules.
     */
    return {
        init: function() {
            waitForTinyMCE(function() {
                log.debug('All TinyMCE instances are loaded');
                // Perform actions after all TinyMCE instances are loaded
            });
        }
    };
});
