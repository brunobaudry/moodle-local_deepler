define(['core/log', 'editor_tiny/editor'], (Log, tinymce)=> {
    /**
     * Find tinyMCEs
     *
     * @returns {*}
     */
    const checkTinyMCELoaded = ()=> {
        if (tinymce === undefined || tinymce === null) {
            Log.info("searching tinymce !!!");
             return false;
            }
        Log.info("tinymce !!!");
        if (tinymce.editors === undefined) {
            Log.info(tinymce);
         return false;
        }
        Log.info("tinymce !!!");
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
                Log.debug('All TinyMCE instances are loaded');
                // Perform actions after all TinyMCE instances are loaded
            });
        }
    };
});
