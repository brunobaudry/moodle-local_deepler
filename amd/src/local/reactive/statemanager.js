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
 * State Manager and Reactive core for local_deepler.
 *
 * @module     local_deepler/local/reactive/statemanager
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Reactive from 'core/local/reactive/reactive';
import DefaultMutations from 'local_deepler/local/reactive/mutations';

// Map with all the state manager instances.
const stateManagerMap = new Map();

/**
 * Trigger a state changed custom event.
 *
 * @param {object} detail The full state or changes.
 * @param {EventTarget} target The custom event target (document if none provided).
 */
function dispatchStateChangedEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(new CustomEvent('local_deepler/stateChanged', {
        bubbles: true,
        detail: detail,
    }));
}

/**
 * DeepLer Reactive class extending core/local/reactive/reactive.
 */
export class DeeplerReactive extends Reactive {
    /**
     * Initializes the reactive state manager.
     *
     * @param {object} initialData Initial configuration and state.
     */
    init(initialData = {}) {
        let supportedLangs = initialData.supportedLangs ?? initialData.supportedlangs ?? initialData.config?.supportedlangs ?? '';
        if (Array.isArray(supportedLangs)) {
            supportedLangs = supportedLangs.join(', ');
        }
        const config = Object.assign({}, initialData.config || {}, {
            rephraseSymbol: initialData.rephraseSymbol ?? initialData.rephrasesymbol ?? initialData.config?.rephrasesymbol ?? '',
            canImprove: initialData.canImprove !== undefined ? Boolean(initialData.canImprove) :
                (initialData.canimprove !== undefined ? Boolean(initialData.canimprove) :
                (initialData.config?.canimprove !== undefined ? Boolean(initialData.config.canimprove) : true)),
            supportedLangs: supportedLangs,
        });

        const state = {
            config: config,
            globalSettings: {
                sourceLang: initialData.sourceLang || '',
                targetLang: initialData.targetLang || 'fr',
                formality: initialData.formality || 'default',
                tagHandling: initialData.tagHandling || 'html',
                splitSentences: initialData.splitSentences || '1',
                preserveFormatting: Boolean(initialData.preserveFormatting),
                escapeLatex: initialData.escapeLatex !== undefined ? Boolean(initialData.escapeLatex) : true,
                escapePre: initialData.escapePre !== undefined ? Boolean(initialData.escapePre) : true,
                hideIframes: initialData.hideIframes !== undefined ? Boolean(initialData.hideIframes) : true,
                glossaryId: initialData.glossaryId || '',
                modelType: initialData.modelType || 'default',
                toneorstyle: initialData.toneorstyle || 'default',
            },
            rows: initialData.rows || [],
            selection: {
                id: 'selection',
                allSelected: true,
                selectedCount: 0,
                totalCount: 0,
            },
            stats: {
                id: 'stats',
                totalWords: 0,
                totalChars: 0,
                selectedWords: 0,
                selectedChars: 0,
            },
            batchStatus: {
                id: 'batchStatus',
                isTranslating: false,
                isSaving: false,
                progress: 0,
            }
        };

        this.setInitialState(state);
    }
}

/**
 * Get or create the Deepler reactive instance.
 *
 * @param {string} instanceId Unique instance identifier (e.g. courseid or 'main').
 * @param {object} initialData Initial data to populate state.
 * @returns {DeeplerReactive}
 */
export const getStateManager = (instanceId = 'main', initialData = null) => {
    if (!stateManagerMap.has(instanceId)) {
        const reactive = new DeeplerReactive({
            name: `local_deepler_${instanceId}`,
            eventName: 'local_deepler/stateChanged',
            eventDispatch: dispatchStateChangedEvent,
            mutations: new DefaultMutations(),
        });
        if (initialData) {
            reactive.init(initialData);
        }
        stateManagerMap.set(instanceId, reactive);
    }
    return stateManagerMap.get(instanceId);
};

export default {
    getStateManager,
    DeeplerReactive,
};
