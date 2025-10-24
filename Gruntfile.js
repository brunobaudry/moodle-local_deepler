// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Run it with `npx grunt [task]`.
 *
 * @param {object} grunt
 * @module     'local_deepler'; // Full name of the plugin (used for diagnostics)./Gruntfile.js
 * @copyright  2025  <>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
"use strict";
// eslint-disable-next-line no-undef
module.exports = function(grunt) {
    // require("grunt-load-gruntfile")(grunt);
    // grunt.loadGruntfile("../../../Gruntfile.js");
        grunt.loadNpmTasks('grunt-contrib-clean');
        grunt.loadNpmTasks('grunt-contrib-copy');
        grunt.loadNpmTasks('grunt-contrib-uglify');
        grunt.loadNpmTasks('grunt-eslint');
        grunt.loadNpmTasks('grunt-shell');
    grunt.loadNpmTasks("grunt-contrib-sass");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-contrib-clean");

        grunt.initConfig({
            clean: {
                build: ['amd/build']
            },
            copy: {
                build: {
                    files: [{
                        expand: true,
                        cwd: 'amd/src',
                        src: ['**/*.js'],
                        dest: 'amd/build'
                    }]
                }
            },
            uglify: {
                build: {
                    files: [{
                        expand: true,
                        cwd: 'amd/build',
                        src: ['**/*.js'],
                        dest: 'amd/build',
                        ext: '.min.js'
                    }]
                }
            },
            eslint: {
                target: ['amd/src/**/*.js']
            },
            shell: {
                behat: {
                    command: 'vendor/bin/behat --tags=@local_deepler'
                }
            },
            watch: {
                // If any .scss file changes in directory "scss" then run the "sass" task.
                files: "scss/*.scss",
                tasks: ["sass"]
            },
            sass: {
                // Production config is also available.
                dev: {
                    options: {
                        // Saas output style.
                        style: "expanded"
                        // Specifies directories to scan for @import directives when parsing.
                        // The default value is the directory of the source, which is probably what you want.
                       // loadPath: ["myOtherImports/"]
                    },
                    files: {
                        "styles.css": "scss/styles.scss"
                    }
                },
                prod:{
                    options: {
                        // Saas output style.
                        style: "compressed"
                        // Specifies directories to scan for @import directives when parsing.
                        // The default value is the directory of the source, which is probably what you want.
                        //loadPath: ["myOtherImports/"]
                    },
                    files: {
                        "styles.css": "scss/styles.scss"
                    }
                }
            }

        });

        grunt.registerTask('default', ['clean', 'copy', 'uglify', 'eslint']);
        grunt.registerTask('dev', ['clean', 'copy', 'uglify', 'eslint', "sass:dev"]);
        // grunt.registerTask('dev', ['shell:behat','clean', 'copy', 'uglify', 'eslint', "sass:dev"]);
        grunt.registerTask('prod', ['clean', 'copy', 'uglify', 'eslint', "sass:prod"]);
};
