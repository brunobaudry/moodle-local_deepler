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
 * Reactive mutations for local_deepler.
 *
 * @module     local_deepler/local/reactive/mutations
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export default class {
    /**
     * Update a global setting and recalculate stats if needed.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} settingName Name of the setting.
     * @param {*} value New value.
     */
    async setGlobalSetting(stateManager, settingName, value) {
        stateManager.setReadOnly(false);
        const globalSettings = stateManager.get('globalSettings');
        if (globalSettings) {
            globalSettings[settingName] = value;
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Update custom settings for a specific row.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     * @param {object} customSettings The custom settings overrides.
     */
    async setRowCustomSettings(stateManager, rowId, customSettings) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.customSettings = customSettings;
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Reset custom settings for a specific row back to global defaults.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     */
    async resetRowCustomSettings(stateManager, rowId) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.customSettings = null;
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Set selection state for a single row and recalculate summary stats.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     * @param {boolean} isSelected True if selected.
     */
    async setRowSelection(stateManager, rowId, isSelected) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.selected = isSelected;
        }
        this._recalculateStatsInternal(stateManager);
        stateManager.setReadOnly(true);
    }

    /**
     * Batch select or deselect all rows.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {boolean} isSelected True if select all, false if deselect all.
     */
    async selectAllRows(stateManager, isSelected) {
        stateManager.setReadOnly(false);
        const rows = stateManager.get('rows');
        if (rows) {
            rows.forEach((row) => {
                row.selected = isSelected;
            });
        }
        this._recalculateStatsInternal(stateManager);
        stateManager.setReadOnly(true);
    }

    /**
     * Update translated text of a row.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     * @param {string} translatedText The new translated text.
     */
    async updateRowTranslation(stateManager, rowId, translatedText) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.translatedText = translatedText;
            row.isDirty = true;
            row.status = 'translated';
            row.errorMessage = null;
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Update original text and word/char count for a row.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     * @param {string} originalText The original text.
     * @param {number} wordCount Word count.
     * @param {number} charCount Character count.
     */
    async updateRowOriginalText(stateManager, rowId, originalText, wordCount = 0, charCount = 0) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.originalText = originalText;
            row.wordCount = wordCount;
            row.charCount = charCount;
        }
        this._recalculateStatsInternal(stateManager);
        stateManager.setReadOnly(true);
    }

    /**
     * Update execution status of a row.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {string} rowId The row identifier.
     * @param {string} status 'idle' | 'translating' | 'translated' | 'saving' | 'saved' | 'error'.
     * @param {string|null} errorMessage Optional error message.
     */
    async setRowStatus(stateManager, rowId, status, errorMessage = null) {
        stateManager.setReadOnly(false);
        const row = stateManager.get('rows', rowId);
        if (row) {
            row.status = status;
            row.errorMessage = errorMessage;
            if (status === 'saved') {
                row.isDirty = false;
            }
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Update batch operation status.
     *
     * @param {StateManager} stateManager The state manager.
     * @param {object} batchStatus New batch status properties.
     */
    async updateBatchStatus(stateManager, batchStatus) {
        stateManager.setReadOnly(false);
        const currentBatch = stateManager.get('batchStatus');
        if (currentBatch) {
            Object.assign(currentBatch, batchStatus);
        }
        stateManager.setReadOnly(true);
    }

    /**
     * Recalculate stats for all rows.
     *
     * @param {StateManager} stateManager The state manager.
     */
    async recalculateStats(stateManager) {
        stateManager.setReadOnly(false);
        this._recalculateStatsInternal(stateManager);
        stateManager.setReadOnly(true);
    }

    /**
     * Internal helper to compute stats without managing readOnly state.
     *
     * @param {StateManager} stateManager The state manager.
     */
    _recalculateStatsInternal(stateManager) {
        const rows = stateManager.get('rows');
        const stats = stateManager.get('stats');
        const selection = stateManager.get('selection');

        if (!rows || !stats || !selection) {
            return;
        }

        let totalWords = 0;
        let totalChars = 0;
        let selectedWords = 0;
        let selectedChars = 0;
        let selectedCount = 0;
        let totalCount = 0;

        rows.forEach((row) => {
            totalCount++;
            const words = Number(row.wordCount) || 0;
            const chars = Number(row.charCount) || 0;
            totalWords += words;
            totalChars += chars;

            if (row.selected) {
                selectedCount++;
                selectedWords += words;
                selectedChars += chars;
            }
        });

        stats.totalWords = totalWords;
        stats.totalChars = totalChars;
        stats.selectedWords = selectedWords;
        stats.selectedChars = selectedChars;

        selection.totalCount = totalCount;
        selection.selectedCount = selectedCount;
        selection.allSelected = totalCount > 0 && selectedCount === totalCount;
    }
}
