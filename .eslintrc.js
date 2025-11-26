
module.exports = {
    env: {
        browser: true,
        amd: true
    },
    globals: {
        tinymce: "readonly"
    },
    extends: "eslint:recommended",
    rules: {
        "no-unused-vars": "warn",
        "no-undef": "error"
    }
};
