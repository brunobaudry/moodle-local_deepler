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
 * Settings modal reactive component for local_deepler.
 *
 * @module     local_deepler/local/reactive/components/settings_modal
 * @copyright  2025 Bruno Baudry <bruno.baudry@bfh.ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import BaseComponent from 'core/local/reactive/basecomponent';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {getString} from 'core/str';
import Log from 'core/log';

/**
 * Helper to resolve boolean setting with fallback.
 *
 * @param {*} domValue Value from DOM.
 * @param {*} stateValue Value from reactive state.
 * @param {boolean} defaultValue Default boolean value.
 * @returns {boolean}
 */
const resolveBoolean = (domValue, stateValue, defaultValue) => {
    if (domValue !== undefined) {
        return Boolean(domValue);
    }
    if (stateValue !== undefined) {
        return Boolean(stateValue);
    }
    return defaultValue;
};

/**
 * Helper to resolve string/value setting with fallback.
 *
 * @param {*} domValue Value from DOM.
 * @param {*} stateValue Value from reactive state.
 * @param {*} defaultValue Default value.
 * @returns {*}
 */
const resolveValue = (domValue, stateValue, defaultValue = '') => {
    if (domValue !== undefined && domValue !== null && domValue !== '') {
        return domValue;
    }
    if (stateValue !== undefined && stateValue !== null && stateValue !== '') {
        return stateValue;
    }
    return defaultValue;
};

/**
 * Get effective default settings from reactive state.
 *
 * @param {object} reactive The reactive instance.
 * @returns {object} Default settings.
 */
const getEffectiveDefaults = (reactive) => {
    const globalContainer = document.querySelector('#local_deepler__additionalsettings');
    const domSettings = globalContainer ? gatherFormValues(globalContainer, '') : {};
    const globalSettings = reactive?.state?.globalSettings || {};

    return {
        // eslint-disable-next-line camelcase
        model_type: resolveValue(domSettings.model_type, globalSettings.modelType, 'prefer_quality_optimized'),
        formality: resolveValue(domSettings.formality, globalSettings.formality, 'default'),
        // eslint-disable-next-line camelcase
        split_sentences: resolveValue(domSettings.split_sentences, globalSettings.splitSentences, '1'),
        // eslint-disable-next-line camelcase
        preserve_formatting: resolveBoolean(domSettings.preserve_formatting, globalSettings.preserveFormatting, false),
        context: resolveValue(domSettings.context, globalSettings.context, ''),
        // eslint-disable-next-line camelcase
        tag_handling: resolveValue(domSettings.tag_handling, globalSettings.tagHandling, 'html'),
        // eslint-disable-next-line camelcase
        outline_detection: resolveBoolean(domSettings.outline_detection, globalSettings.outlineDetection, false),
        // eslint-disable-next-line camelcase
        ignore_tags: resolveValue(domSettings.ignore_tags, globalSettings.ignoreTags, ''),
        // eslint-disable-next-line camelcase
        non_splitting_tags: resolveValue(domSettings.non_splitting_tags, globalSettings.nonSplittingTags, ''),
        // eslint-disable-next-line camelcase
        splitting_tags: resolveValue(domSettings.splitting_tags, globalSettings.splittingTags, ''),
        toneorstyle: resolveValue(domSettings.toneorstyle, globalSettings.toneorstyle, 'default'),
        latexeascape: resolveBoolean(domSettings.latexeascape, globalSettings.escapeLatex, true),
        preescape: resolveBoolean(domSettings.preescape, globalSettings.escapePre, true),
        hideiframes: resolveBoolean(domSettings.hideiframes, globalSettings.hideIframes, true),
    };
};

/**
 * Populate form values in the modal.
 *
 * @param {Element} form The form container.
 * @param {string} prefix The ID/name prefix.
 * @param {object} settings The settings to populate.
 */
const populateForm = (form, prefix, settings) => {
    if (!form || !settings) {
        return;
    }

    const setRadio = (name, value) => {
        if (value !== undefined && value !== null) {
            const radio = form.querySelector(`input[type="radio"][name$="${name}"][value="${value}"]`);
            if (radio) {
                radio.checked = true;
            }
        }
    };

    const setCheckbox = (id, value) => {
        if (value !== undefined && value !== null) {
            const cb = form.querySelector(`#${prefix}${id}`);
            if (cb) {
                cb.checked = Boolean(value);
            }
        }
    };

    const setTextarea = (id, value) => {
        if (value !== undefined && value !== null) {
            const ta = form.querySelector(`#${prefix}${id}`);
            if (ta) {
                ta.value = value;
            }
        }
    };

    setRadio('model_type', settings.model_type || settings.modelType);
    setRadio('formality', settings.formality);
    setRadio('split_sentences', settings.split_sentences || settings.splitSentences);
    setCheckbox('preserve_formatting', settings.preserve_formatting ?? settings.preserveFormatting);
    setTextarea('local_deepler__context', settings.context);
    const tagHandling = settings.tag_handling || settings.tagHandling;
    setCheckbox('tag_handling', tagHandling ? tagHandling === 'html' : true);
    setCheckbox('outline_detection', settings.outline_detection ?? settings.outlineDetection);
    setTextarea('ignore_tags', settings.ignore_tags || settings.ignoreTags);
    setTextarea('non_splitting_tags', settings.non_splitting_tags || settings.nonSplittingTags);
    setTextarea('splitting_tags', settings.splitting_tags || settings.splittingTags);
    setRadio('toneorstyle', settings.toneorstyle);
    setCheckbox('latexeascape', settings.latexeascape ?? settings.escapeLatex);
    setCheckbox('preescape', settings.preescape ?? settings.escapePre);
    setCheckbox('hideiframes', settings.hideiframes ?? settings.hideIframes);
};

/**
 * Gather form values from modal body.
 *
 * @param {Element} form The modal body element.
 * @param {string} prefix The ID/name prefix.
 * @returns {object} The collected custom settings.
 */
const gatherFormValues = (form, prefix = '') => {
    const getRadio = (name) => {
        const checked = form.querySelector(`input[type="radio"][name$="${name}"]:checked`);
        return checked ? checked.value : null;
    };
    const getCheckbox = (id) => {
        const cb = (prefix ? form.querySelector(`#${prefix}${id}`) : null) ||
            form.querySelector(`#${id}`) ||
            form.querySelector(`[data-id="local_deepler/${id}"]`);
        return cb ? cb.checked : false;
    };
    const getTextarea = (id, dataId = null) => {
        const targetDataId = dataId || id;
        const ta = (prefix ? form.querySelector(`#${prefix}${id}`) : null) ||
            form.querySelector(`#${id}`) ||
            form.querySelector(`[data-id="local_deepler/${targetDataId}"]`);
        return ta ? ta.value : '';
    };

    return {
        // eslint-disable-next-line camelcase
        model_type: getRadio('model_type') || 'prefer_quality_optimized',
        formality: getRadio('formality') || 'default',
        // eslint-disable-next-line camelcase
        split_sentences: getRadio('split_sentences') || '1',
        // eslint-disable-next-line camelcase
        preserve_formatting: getCheckbox('preserve_formatting'),
        context: getTextarea('local_deepler__context', 'context'),
        // eslint-disable-next-line camelcase
        tag_handling: getCheckbox('tag_handling') ? 'html' : 'xml',
        // eslint-disable-next-line camelcase
        outline_detection: getCheckbox('outline_detection'),
        // eslint-disable-next-line camelcase
        ignore_tags: getTextarea('ignore_tags'),
        // eslint-disable-next-line camelcase
        non_splitting_tags: getTextarea('non_splitting_tags'),
        // eslint-disable-next-line camelcase
        splitting_tags: getTextarea('splitting_tags'),
        toneorstyle: getRadio('toneorstyle') || 'default',
        latexeascape: getCheckbox('latexeascape'),
        preescape: getCheckbox('preescape'),
        hideiframes: getCheckbox('hideiframes'),
    };
};

/**
 * Open the settings modal for a specific row.
 *
 * @param {object} reactive The reactive instance.
 * @param {string} rowId The row identifier.
 * @returns {Promise<void>}
 */
export const openSettingsModal = async(reactive, rowId) => {
    try {
        const row = reactive.state.rows?.get(rowId) ||
            Array.from(reactive.state.rows?.values() || []).find(r => r.key === rowId || r.id === rowId);

        const defaults = getEffectiveDefaults(reactive);
        const currentSettings = row?.customSettings ? Object.assign({}, defaults, row.customSettings) : defaults;

        const title = await getString('rowsettingstitle', 'local_deepler', rowId);
        const resetText = await getString('resettodefaults', 'local_deepler');
        const prefix = 'modal_';

        const formHtml = await Templates.render('local_deepler/settings_form', {
            prefix: prefix,
            canimprove: true,
            rephrasesymbol: '',
            supportedlangs: 'de, en-GB, en-US',
            escapelatexbydefault: currentSettings.latexeascape ? 'checked' : '',
            escapeprebydefault: currentSettings.preescape ? 'checked' : '',
            hideiframesdefault: currentSettings.hideiframes ? 'checked' : '',
        });

        const modal = await ModalSaveCancel.create({
            title: title,
            body: formHtml,
            large: true,
        });

        const footer = modal.getFooter();
        if (footer && footer[0]) {
            const resetBtn = document.createElement('button');
            resetBtn.type = 'button';
            resetBtn.className = 'btn btn-outline-danger me-auto';
            resetBtn.setAttribute('data-action', 'reset');
            resetBtn.textContent = resetText;
            footer[0].prepend(resetBtn);
        }

        const bodyElement = modal.getBody()[0];
        populateForm(bodyElement, prefix, currentSettings);

        modal.getRoot().on(ModalEvents.save, (e) => {
            e.preventDefault();
            const customSettings = gatherFormValues(bodyElement, prefix);
            reactive.dispatch('setRowCustomSettings', rowId, customSettings);
            modal.hide();
            modal.destroy();
        });

        modal.getRoot().on('click', '[data-action="reset"]', (e) => {
            e.preventDefault();
            reactive.dispatch('resetRowCustomSettings', rowId);
            modal.hide();
            modal.destroy();
        });

        modal.getRoot().on(ModalEvents.hidden, () => {
            modal.destroy();
        });

        await modal.show();
    } catch (err) {
        Log.error('Error opening settings modal: ' + err.message);
    }
};

export default class SettingsModal extends BaseComponent {
    /**
     * Open the settings modal for a specific row.
     *
     * @param {object} reactive The reactive instance.
     * @param {string} rowId The row identifier.
     * @returns {Promise<void>}
     */
    static async open(reactive, rowId) {
        return openSettingsModal(reactive, rowId);
    }

    /**
     * Open the settings modal for a specific row (alias).
     *
     * @param {object} reactive The reactive instance.
     * @param {string} rowId The row identifier.
     * @returns {Promise<void>}
     */
    static async openSettingsModal(reactive, rowId) {
        return openSettingsModal(reactive, rowId);
    }

    /**
     * Component create lifecycle method.
     *
     * @param {object} descriptor Component descriptor.
     */
    create(descriptor) {
        this.name = 'local_deepler_settings_modal';
        this.rowId = descriptor.rowId || null;
        this.prefix = descriptor.prefix || 'modal_';
    }

    /**
     * Watchers definition.
     *
     * @returns {Array} List of watchers.
     */
    getWatchers() {
        if (!this.rowId) {
            return [];
        }
        return [
            {watch: `rows[${this.rowId}].customSettings:updated`, handler: this._settingsUpdated},
        ];
    }

    /**
     * Handler when settings are updated.
     *
     * @param {object} args Watcher arguments.
     * @param {object} args.element The updated element.
     */
    _settingsUpdated({element}) {
        if (this.element) {
            populateForm(this.element, this.prefix, element.customSettings);
        }
    }
}

SettingsModal.open = openSettingsModal;
SettingsModal.openSettingsModal = openSettingsModal;
