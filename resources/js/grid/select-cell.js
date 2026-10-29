import { Select } from '../../../vendor/filament/support/resources/js/utilities/select.js'

const blankToNull = (value) => (value === '' || value === undefined ? null : value)

export function mountSelectCell({ element, column, cell, value, label, callSchemaComponentMethod, onChange }) {
    const config = column.source.editor.select
    const isMultiple = Boolean(config.isMultiple)
    const state = isMultiple ? (Array.isArray(value) ? value : []) : (value ?? null)
    const callCell = (method, args = {}) => callSchemaComponentMethod(cell.key, method, args)

    const select = new Select({
        ...config,
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
        options: cell.options ?? [],
        state,
        onStateChange: (state) => {
            const nextValue = isMultiple ? state : blankToNull(state)
            const nextLabel = isMultiple ? null : (select.labelRepository?.[state] ?? null)

            onChange(nextValue, nextLabel, { isMultiple })
        },
    })

    return select
}
