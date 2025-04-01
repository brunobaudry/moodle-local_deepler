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
 * Port of the scrollspy script from Angelika Cathor (angelikatyborska) to AMD.
 * Utilizes the scroll event to update the breadcrumbs based on the current scroll position.
 *
 * @module     local_deepler/deepler
 * @file       amd/src/local/scrollspy.js
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @copyright  2025 Angelika Cathor <https://angelika.me>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/angelikatyborska
 */
define(['core/log'], (Log) => {
    let OFFSET_TOP;
    let ARTICLE;
    let CONTAINER;
    let END_OF_ARTICLE;
    let HIGHEST_LEVEL;
    let FADING_DISTANCE;
    let OFFSET_END_OF_SCOPE;
    /**
     * Injects the breadcrumbs into the container.
     *
     * @param {array} headingsPerLevel
     */
    const makeBreadcrumbs = (headingsPerLevel)=>{
        CONTAINER.innerHTML = getBreadcrumbs(headingsPerLevel, scrollTop());
        if (CONTAINER.innerHTML.trim() === '') {
            CONTAINER.style.display = 'none';
        } else {
            CONTAINER.style.display = 'block';
        }
    };

    /**
     * Returns the current scroll position.
     */
    const scrollTop = ()=>{
        return window.scrollY + OFFSET_TOP;
    };

    /**
     * Returns an array of headings per level.
     */
    const getHeadingsPerLevel = ()=> {
        const headingsPerLevel = [];
        for (let level = HIGHEST_LEVEL; level <= 6; level++) {
            let headings = Array.prototype.slice.call(ARTICLE.querySelectorAll('span.h' + level));
            headings = headings.sort((a, b)=> b.offsetTop - a.offsetTop);
            headingsPerLevel.push(headings);
        }

        return headingsPerLevel;
    };

    /**
     * Gets the breadcrumbs at position.
     *
     * @param {array} headingsPerLevel
     * @param {number} scrollTop
     * @return {string}
     */
    const getBreadcrumbs = (headingsPerLevel, scrollTop)=> {
        const breadcrumbs = [];
        const headingsInScope = findHeadingsInScope(headingsPerLevel, scrollTop);

        headingsInScope.forEach((heading)=> {
            const opacity = calculateOpacity(heading.beginningOfScope, heading.endOfScope, scrollTop);
            const html = '<a href="#' + heading.id
                + '" class="' + heading.tag
                + '" style="opacity:' + opacity
                + '; pointer-events: ' + (opacity > 0.5 ? 'auto' : 'none')
                + '">' + heading.text
                + '</a>';

            breadcrumbs.push(html);
        });
        return breadcrumbs.join('<small>&nbsp;&gt;&nbsp;</small>');
    };

    /**
     * Finds the headings in scope.
     *
     * @param {array} headingsPerLevel
     * @param {number} scrollTop
     * @return {string}
     */
    const findHeadingsInScope = (headingsPerLevel, scrollTop) =>{
        const headingsInScope = [];
        let previousHeadingOffset = 0;

        headingsPerLevel.forEach((headings, level)=> {
            const heading = headings.find((node) =>{
                return node.offsetTop < scrollTop && node.offsetTop > previousHeadingOffset;
            });
            if (heading) {
                const nextHeadingOfSameLevel = headingsPerLevel[level][headingsPerLevel[level].indexOf(heading) - 1];
                const currentHeadingOfHigherLevel = headingsInScope[headingsInScope.length - 1];
                const endOfScope = calculateEndOfScope(nextHeadingOfSameLevel, currentHeadingOfHigherLevel);

                headingsInScope.push({
                    id: heading.id,
                    tag: heading.tagName.toLowerCase(),
                    text: heading.textContent.trim(),
                    beginningOfScope: heading.offsetTop + heading.offsetHeight,
                    endOfScope: endOfScope
                });

                previousHeadingOffset = heading.offsetTop;
            } else {
                previousHeadingOffset = END_OF_ARTICLE;
            }
        });

        return headingsInScope;
    };

    /**
     * Calculates the end of the scope.
     *
     * @param {object} nextHeadingOfSameLevel
     * @param {object} currentHeadingOfHigherLevel
     * @return {number}
     */
    const calculateEndOfScope = (nextHeadingOfSameLevel, currentHeadingOfHigherLevel) => {
        let endOfScope;

        if (currentHeadingOfHigherLevel) {
            if (nextHeadingOfSameLevel) {
                endOfScope = Math.min(nextHeadingOfSameLevel.offsetTop, currentHeadingOfHigherLevel.endOfScope);
            } else {
                endOfScope = currentHeadingOfHigherLevel.endOfScope;
            }
        } else {
            if (nextHeadingOfSameLevel) {
                endOfScope = nextHeadingOfSameLevel.offsetTop;
            } else {
                endOfScope = END_OF_ARTICLE;
            }
        }

        return endOfScope;
    };

    /**
     * Calculates the opacity of the breadcrumb.
     *
     * @param {number} top
     * @param {number} bottom
     * @param {number} scrollTop
     */
    const calculateOpacity = (top, bottom, scrollTop) =>{
        const diffTop = scrollTop - top;
        const topOnFade = diffTop / FADING_DISTANCE;
        const opacityTop = diffTop > FADING_DISTANCE ? 1 : diffTop / topOnFade;
        const diffBottom = bottom - scrollTop - OFFSET_END_OF_SCOPE;
        const bottomOnFade = diffBottom / FADING_DISTANCE;
        const opacityBottom = diffBottom > FADING_DISTANCE ? 1 : bottomOnFade;
        return Math.min(opacityTop, opacityBottom);
    };
    const init = (article, breadcrumbsContainer, options) =>{
        Log.debug(`local_deepler/scrollspy/init`);
        ARTICLE = document.querySelector(article);
        CONTAINER = document.querySelector(breadcrumbsContainer);
        END_OF_ARTICLE = ARTICLE.offsetTop + ARTICLE.offsetHeight;
        HIGHEST_LEVEL = options.highestLevel || 2;
        FADING_DISTANCE = options.fadingDistance == 0 ? 1 : options.fadingDistance || 100;
        OFFSET_END_OF_SCOPE = options.offsetEndOfScope || 100;
        OFFSET_TOP = options.offsetTop || 0;
        const headingsPerLevel = getHeadingsPerLevel();
        makeBreadcrumbs(headingsPerLevel);

        window.addEventListener('scroll', () =>{
            makeBreadcrumbs(headingsPerLevel);
        });


    };
    return {
        init: init
    };
});
