/* jshint strict:false */
"use strict";
/* globals module: false */
// eslint-disable-next-line no-redeclare
/* global module */
/* eslint no-undef: "error"*/
module.exports = function(grunt) {

    // Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-sass");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");

    grunt.initConfig({
        watch: {
            // If any .scss file changes in directory "scss" then run the "sass" task.
            files: "scss/*.scss",
            tasks: ["sass"]
        },
        sass: {
            // Production config is also available.
            development: {
                options: {
                    // Saas output style.
                    style: "expanded",
                },
                files: {
                    "styles.css": "scss/styles.scss"
                }
            },
            prod: {
                options: {
                    // Saas output style.
                    style: "compressed",
                },
                files: {
                    "styles.css": "scss/styles.scss"
                }
            }
        }
    });

    // Note: Do not override Moodle's standard grunt tasks (e.g., "amd", "stylelint").
    // Moodle Plugin CI provides these tasks. Overriding them here would prevent
    // the CI from generating AMD build files and cause failures.

    // The default task (running "grunt" in the console).
    grunt.registerTask("default", ["sass:development"]);
    // Development task (running "grunt dev" in console).
    grunt.registerTask("dev", ["sass:development"]);
    // The production task (running "grunt prod" in the console).
    grunt.registerTask("prod", ["sass:prod"]);
};
