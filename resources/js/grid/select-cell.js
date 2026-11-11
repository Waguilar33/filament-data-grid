import { Select } from '../../../vendor/filament/support/resources/js/utilities/select.js'

const blankToNull = (value) => (value === '' || value === undefined ? null : value)

const ABSOLUTE_POSITIONING_CONTEXT = '.fi-absolute-positioning-context'

const keepFixedPositioning = (button) => {
    const closest = button.closest.bind(button)

    // Filament's Select lets any outer absolute context (every modal) override the grid's fixed one, so the dropdown gets clipped by the grid viewport.
    button.closest = (selector) => (selector === ABSOLUTE_POSITIONING_CONTEXT ? null : closest(selector))
}

export function mountSelectCell({ element, column, cell, value, label, callSchemaComponentMethod, onChange }) {
    const config = column.source.editor.select
    const isMultiple = Boolean(config.isMultiple)
    const state = isMultiple ? (Array.isArray(value) ? value : []) : (value ?? null)
    const callCell = (method, args = {}) => callSchemaComponentMethod(cell.key, method, args)

    const options = cell.options ?? []

    const select = new Select({
        ...config,
        // The payload already holds this cell's getOptionsForJs() from the latest render, so opening needn't wait for a round trip.
        hasDynamicOptions: config.hasDynamicOptions && options.length === 0,
        ariaLabel: column.source.label,
        canSelectPlaceholder: cell.canSelectPlaceholder ?? true,
        element,
        getOptionLabelUsing: async () => callCell('getOptionLabel'),
        getOptionLabelsUsing: async () => callCell('getOptionLabelsForJs'),
        getOptionsUsing: async () => callCell('getOptionsForJs'),
        getSearchResultsUsing: async (search) => callCell('getSearchResultsForJs', { search }),
        initialOptionLabel: isMultiple ? null : (label ?? null),
        initialOptionLabels: isMultiple ? (cell.labels ?? []) : [],
        initialState: state,
        isDisabled: Boolean(cell.disabled),
        options,
        placeholder: cell.placeholder ?? config.placeholder,
        state,
        onStateChange: (state) => {
            const nextValue = isMultiple ? state : blankToNull(state)
            const nextLabel = isMultiple ? null : (select.labelRepository?.[state] ?? null)

            onChange(nextValue, nextLabel, { isMultiple })
        },
    })

    keepFixedPositioning(select.selectButton)

    return select
}
