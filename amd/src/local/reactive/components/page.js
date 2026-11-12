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
 * Page reactive component for local_deepler.
 *
 * @module     local_deepler/local/reactive/components/page
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core/local/reactive/basecomponent';

export default class extends BaseComponent {
    /**
     * Component create lifecycle method.
     */
    create() {
        this.name = 'local_deepler_page';
        this.selectors = {
            selectAllBtn: '[data-action="local_deepler/select-all"]',
            saveAllBtn: '[data-action="local_deepler/saveall-btn"]',
            autoTranslateBtn: '[data-action="local_deepler/autotranslate-btn"]',
            wordCount: '#local_deepler__wc',
            charNumWithSpace: '#local_deepler__wsc',
            charNumWithOutSpace: '#local_deepler__wosc',
            rowCheckbox: '[data-action="local_deepler/checkbox"]',
        };
    }

    /**
     * Initial state ready handler.
     *
     * @param {object} state The state data.
     */
    stateReady(state) {
        if (state.stats) {
            this._updateStatsUI(state.stats);
        }
        if (state.selection) {
            this._updateSelectionUI(state.selection);
        }
        if (state.batchStatus) {
            this._updateBatchStatusUI(state.batchStatus);
        }
    }

    /**
     * Watchers definition.
     *
     * @returns {Array} List of watchers.
     */
    getWatchers() {
        return [
            {watch: `stats:updated`, handler: this._statsUpdated},
            {watch: `selection:updated`, handler: this._selectionUpdated},
            {watch: `batchStatus:updated`, handler: this._batchStatusUpdated},
        ];
    }

    /**
     * Handler when stats are updated in state.
     *
     * @param {object} args Watcher event argument.
     * @param {object} args.element The updated stats element.
     */
    _statsUpdated({element}) {
        this._updateStatsUI(element);
    }

    /**
     * Handler when selection is updated in state.
     *
     * @param {object} args Watcher event argument.
     * @param {object} args.element The updated selection element.
     */
    _selectionUpdated({element}) {
        this._updateSelectionUI(element);
    }

    /**
     * Handler when batch status is updated in state.
     *
     * @param {object} args Watcher event argument.
     * @param {object} args.element The updated batch status element.
     */
    _batchStatusUpdated({element}) {
        this._updateBatchStatusUI(element);
    }

    /**
     * Update stats numbers in DOM.
     *
     * @param {object} stats Stats object from state.
     */
    _updateStatsUI(stats) {
        const format = new Intl.NumberFormat();
        const wcEl = this.getElement(this.selectors.wordCount);
        if (wcEl) {
            wcEl.innerText = format.format(stats.selectedWords ?? stats.totalWords ?? 0);
        }

        const wscEl = this.getElement(this.selectors.charNumWithSpace);
        if (wscEl) {
            wscEl.innerText = format.format(stats.selectedChars ?? stats.totalChars ?? 0);
        }

        const woscEl = this.getElement(this.selectors.charNumWithOutSpace);
        if (woscEl) {
            woscEl.innerText = format.format(stats.selectedChars ?? stats.totalChars ?? 0);
        }
    }

    /**
     * Update selection UI (e.g. select-all button and action buttons).
     *
     * @param {object} selection Selection state object.
     */
    _updateSelectionUI(selection) {
        const selectAllBtn = this.getElement(this.selectors.selectAllBtn);
        if (selectAllBtn) {
            selectAllBtn.setAttribute('data-all-selected', selection.allSelected ? '1' : '0');
        }

        const autoTranslateBtn = this.getElement(this.selectors.autoTranslateBtn);
        if (autoTranslateBtn) {
            if (selection.selectedCount === 0) {
                autoTranslateBtn.setAttribute('disabled', 'disabled');
                autoTranslateBtn.classList.add('disabled');
            } else {
                autoTranslateBtn.removeAttribute('disabled');
                autoTranslateBtn.classList.remove('disabled');
            }
        }
    }

    /**
     * Update UI based on batch translation / save status.
     *
     * @param {object} batchStatus Batch status object.
     */
    _updateBatchStatusUI(batchStatus) {
        const saveAllBtn = this.getElement(this.selectors.saveAllBtn);
        const autoTranslateBtn = this.getElement(this.selectors.autoTranslateBtn);

        const isBusy = batchStatus.isTranslating || batchStatus.isSaving;

        if (saveAllBtn) {
            if (isBusy) {
                saveAllBtn.setAttribute('disabled', 'disabled');
            } else {
                saveAllBtn.removeAttribute('disabled');
            }
        }

        if (autoTranslateBtn) {
            if (isBusy) {
                autoTranslateBtn.setAttribute('disabled', 'disabled');
            }
        }
    }
}
