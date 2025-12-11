"use strict";

module.exports = function (grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../../Gruntfile.js");

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
            prod:{
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
    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["sass:development"]);
    // The production task (running "grunt prod" in console).
    grunt.registerTask("prod", ["sass:prod"]);
};
