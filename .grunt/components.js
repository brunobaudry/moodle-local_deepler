const path = require('path');

module.exports = {
    component: 'local_deepler',

    getThirdPartyPaths: () => {
        return [];
    },

    getAmdSrcPaths: () => {
        return [path.resolve(__dirname, '../amd/src')];
    },

    getIgnoreFilesPaths: () => {
        return [];
    }
};
