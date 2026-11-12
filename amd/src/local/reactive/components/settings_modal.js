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
        // eslint-disable-next-line camelcase
        glossary_id: resolveValue(domSettings.glossary_id, globalSettings.glossaryId, ''),
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

    const setControlValue = (name, value) => {
        if (value !== undefined && value !== null) {
            const select = form.querySelector(`select[name$="${name}"], select[data-id="local_deepler/${name}"]`) ||
                (prefix ? form.querySelector(`select#${prefix}${name}`) : null) ||
                form.querySelector(`select#${name}`);
            if (select) {
                select.value = value;
                return;
            }
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

    setControlValue('model_type', settings.model_type || settings.modelType);
    setControlValue('formality', settings.formality);
    setControlValue('split_sentences', settings.split_sentences || settings.splitSentences);
    setCheckbox('preserve_formatting', settings.preserve_formatting ?? settings.preserveFormatting);
    setControlValue('glossary_id', settings.glossary_id || settings.glossaryId);
    setTextarea('local_deepler__context', settings.context);
    const tagHandling = settings.tag_handling || settings.tagHandling;
    setCheckbox('tag_handling', tagHandling ? tagHandling === 'html' : true);
    setCheckbox('outline_detection', settings.outline_detection ?? settings.outlineDetection);
    setTextarea('ignore_tags', settings.ignore_tags || settings.ignoreTags);
    setTextarea('non_splitting_tags', settings.non_splitting_tags || settings.nonSplittingTags);
    setTextarea('splitting_tags', settings.splitting_tags || settings.splittingTags);
    setControlValue('toneorstyle', settings.toneorstyle);
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
    const getControlValue = (name, defaultValue = '') => {
        const select = form.querySelector(`select[name$="${name}"], select[data-id="local_deepler/${name}"]`) ||
            (prefix ? form.querySelector(`select#${prefix}${name}`) : null) ||
            form.querySelector(`select#${name}`);
        if (select) {
            return select.value;
        }
        const checked = form.querySelector(`input[type="radio"][name$="${name}"]:checked`);
        return checked ? checked.value : defaultValue;
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
        model_type: getControlValue('model_type', 'prefer_quality_optimized'),
        formality: getControlValue('formality', 'default'),
        // eslint-disable-next-line camelcase
        split_sentences: getControlValue('split_sentences', '1'),
        // eslint-disable-next-line camelcase
        preserve_formatting: getCheckbox('preserve_formatting'),
        // eslint-disable-next-line camelcase
        glossary_id: getControlValue('glossary_id', ''),
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
        toneorstyle: getControlValue('toneorstyle', 'default'),
        latexeascape: getCheckbox('latexeascape'),
        preescape: getCheckbox('preescape'),
        hideiframes: getCheckbox('hideiframes'),
    };
};

/**
 * Build a user-friendly modal title based on the row's immediate parent title and field name.
 * Format: PARENT_TITLE > Field Name.
 *
 * @param {string} rowId The row identifier.
 * @returns {Promise<string>} The formatted title.
 */
const getRowModalTitle = async(rowId) => {
    let rowElement = null;
    if (rowId) {
        rowElement = document.querySelector(`[data-row-id="${rowId}"]`);
    }

    if (!rowElement) {
        return getString('rowsettingstitle', 'local_deepler', rowId);
    }

    const breadcrumbs = [];

    // 1. Immediate parent container (submodule, module/activity, or section).
    const parentContainer = rowElement.closest('.section-item, .local_deepler__activity');
    if (parentContainer) {
        const parentHeader = parentContainer.querySelector(
            ':scope > .course-section-header .sectionname, :scope > .sectionname'
        );
        if (parentHeader) {
            const parentText = parentHeader.textContent.trim();
            if (parentText) {
                breadcrumbs.push(parentText);
            }
        }
    }

    // 2. Field name.
    const fieldElement = rowElement.querySelector('.local_deepler__activityfield');
    const fieldText = fieldElement ? fieldElement.textContent.trim() : '';
    if (fieldText) {
        breadcrumbs.push(fieldText);
    }

    const breadcrumbString = breadcrumbs.join(' > ');
    if (breadcrumbString) {
        return getString('rowsettingstitle', 'local_deepler', breadcrumbString);
    }

    return getString('rowsettingstitle', 'local_deepler', rowId);
};

const COMPONENT = 'local_deepler';
const MODAL_PREFIX = 'modal_';

/**
 * Retrieve row data from reactive state.
 *
 * @param {object} reactive The reactive instance.
 * @param {string} rowId The row identifier.
 * @returns {object|undefined}
 */
const getRow = (reactive, rowId) => {
    return reactive.state.rows?.get(rowId) ||
        Array.from(reactive.state.rows?.values() || []).find(r => r.key === rowId || r.id === rowId);
};

/**
 * Sanitize language code string.
 *
 * @param {string} [lang=''] Language string.
 * @param {string} [fallback=''] Fallback language code.
 * @returns {string}
 */
const sanitizeLanguage = (lang = '', fallback = '') => {
    return (lang.replace(/^[®@\s]+/, '') || fallback).toLowerCase().trim();
};

/**
 * Determine source and target language pair for a row.
 *
 * @param {object} reactive The reactive instance.
 * @param {object} [row] The row object.
 * @param {string} rowId The row identifier.
 * @returns {{cleanSourceLang: string, targetLang: string}}
 */
const resolveLanguagePair = (reactive, row, rowId) => {
    const domSelectValue = rowId
        ? document.querySelector(`select[data-action="local_deepler/sourceselect"][data-key="${rowId}"]`)?.value
        : '';
    const rowSourceLang = row?.sourceLang ||
        domSelectValue ||
        reactive?.state?.globalSettings?.sourceLang ||
        reactive?.state?.config?.sourcelang ||
        'en';
    const targetLangRaw = reactive?.state?.globalSettings?.targetLang ||
        reactive?.state?.config?.targetlang ||
        '';

    return {
        cleanSourceLang: sanitizeLanguage(rowSourceLang, 'en'),
        targetLang: sanitizeLanguage(targetLangRaw, ''),
    };
};

/**
 * Build context object for settings form template.
 *
 * @param {object} reactive The reactive instance.
 * @param {object} currentSettings Merged row settings.
 * @param {string} prefix Form field prefix.
 * @returns {object}
 */
const buildTemplateContext = (reactive, currentSettings, prefix) => {
    const config = reactive?.state?.config || {};
    const rephraseSymbol = config.rephraseSymbol ?? config.rephrasesymbol ?? '';
    let supportedLangs = config.supportedLangs ?? config.supportedlangs ?? '';
    if (Array.isArray(supportedLangs)) {
        supportedLangs = supportedLangs.join(', ');
    }
    const canImprove = config.canImprove ?? config.canimprove ?? true;
    const globalGlossaryContainer = document.querySelector('.glossary-selector');
    const glossarySelectorHtml = globalGlossaryContainer ? globalGlossaryContainer.outerHTML : '';

    return {
        prefix,
        glossayselector: glossarySelectorHtml,
        canimprove: canImprove,
        rephrasesymbol: rephraseSymbol,
        supportedlangs: supportedLangs,
        escapelatexbydefault: currentSettings.latexeascape ? 'checked' : '',
        escapeprebydefault: currentSettings.preescape ? 'checked' : '',
        hideiframesdefault: currentSettings.hideiframes ? 'checked' : '',
    };
};

/**
 * Add a reset button to modal footer.
 *
 * @param {object} modal The modal instance.
 * @param {string} resetText Text for the reset button.
 */
const appendResetButton = (modal, resetText) => {
    const footer = modal.getFooter();
    if (footer && footer[0]) {
        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn btn-outline-danger me-auto';
        resetBtn.setAttribute('data-action', 'reset');
        resetBtn.textContent = resetText;
        footer[0].prepend(resetBtn);
    }
};

/**
 * Filter and populate glossary select options and handle empty state message.
 *
 * @param {HTMLElement} bodyElement Modal body DOM element.
 * @param {string} prefix Form field prefix.
 * @param {object} reactive The reactive instance.
 * @param {string} cleanSourceLang Cleaned source language.
 * @param {string} targetLang Cleaned target language.
 * @returns {Promise<void>}
 */
const populateGlossaryOptions = async(bodyElement, prefix, reactive, cleanSourceLang, targetLang) => {
    const modalGlossarySelect = bodyElement.querySelector('select[data-id="local_deepler/glossary_id"]') ||
        bodyElement.querySelector(`#${prefix}glossary`) ||
        bodyElement.querySelector('#local_deepler__glossary');

    if (!modalGlossarySelect) {
        return;
    }

    const allGlossaries = reactive?.state?.config?.glossaries || [];
    const matchingGlossaries = allGlossaries.filter(g =>
        (g.sourcelang || '').toLowerCase().trim() === cleanSourceLang &&
        (g.targetlang || '').toLowerCase().trim() === targetLang
    );

    modalGlossarySelect.innerHTML = '';
    const noGlossaryText = await getString('glossary:notselected', COMPONENT);
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = noGlossaryText;
    modalGlossarySelect.appendChild(emptyOpt);

    matchingGlossaries.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.textContent = g.name;
        modalGlossarySelect.appendChild(opt);
    });

    modalGlossarySelect.disabled = matchingGlossaries.length === 0;

    const glossaryWrapper = modalGlossarySelect.closest('.glossary-selector') || bodyElement;
    let emptyMsg = glossaryWrapper.querySelector('em');
    const linkEntries = glossaryWrapper.querySelector('[data-id="local_deepler/glossary_entriesviewer_page"]');

    if (matchingGlossaries.length === 0) {
        if (!emptyMsg) {
            emptyMsg = document.createElement('em');
            glossaryWrapper.appendChild(emptyMsg);
        }
        emptyMsg.textContent = await getString('glossary:listempty', COMPONENT);
        emptyMsg.style.display = '';
        if (linkEntries) {
            linkEntries.style.display = 'none';
        }
    } else {
        if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
        if (linkEntries) {
            linkEntries.style.display = '';
        }
    }
};

/**
 * Register event handlers for modal actions.
 *
 * @param {object} modal The modal instance.
 * @param {HTMLElement} bodyElement Modal body element.
 * @param {string} prefix Form field prefix.
 * @param {object} reactive The reactive instance.
 * @param {string} rowId The row identifier.
 */
const bindModalEvents = (modal, bodyElement, prefix, reactive, rowId) => {
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
        const row = getRow(reactive, rowId);
        const defaults = getEffectiveDefaults(reactive);
        const currentSettings = row?.customSettings ? Object.assign({}, defaults, row.customSettings) : defaults;

        const [title, resetText] = await Promise.all([
            getRowModalTitle(rowId),
            getString('resettodefaults', COMPONENT),
        ]);

        const {cleanSourceLang, targetLang} = resolveLanguagePair(reactive, row, rowId);
        const templateContext = buildTemplateContext(reactive, currentSettings, MODAL_PREFIX);
        const formHtml = await Templates.render('local_deepler/settings_form', templateContext);

        const modal = await ModalSaveCancel.create({
            title,
            body: formHtml,
            large: true,
        });

        appendResetButton(modal, resetText);

        const bodyElement = modal.getBody()[0];
        await populateGlossaryOptions(bodyElement, MODAL_PREFIX, reactive, cleanSourceLang, targetLang);
        populateForm(bodyElement, MODAL_PREFIX, currentSettings);

        bindModalEvents(modal, bodyElement, MODAL_PREFIX, reactive, rowId);

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
