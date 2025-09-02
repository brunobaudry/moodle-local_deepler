// main.js - Entry point for the refactored UI Deepler module
define([
    './eventHandlers',
    './uiHelpers',
    './translationManager',
    './settingsManager'
], function(eventHandlers, uiHelpers, translationManager, settingsManager) {
    return {
        init: function(config) {
            settingsManager.init(config);
            uiHelpers.init(config);
            translationManager.init(config);
            eventHandlers.init(config);
        }
    };
});
