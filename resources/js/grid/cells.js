const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`)

const htmlElement = (html, className) => {
    const element = document.createElement('div')
    element.className = className
    element.innerHTML = html

    return element
}

const toggleHtml = (isOn, translations) => `
    <span class="fi-data-grid-toggle ${isOn ? 'fi-data-grid-toggle-on' : ''}" role="switch" aria-checked="${isOn ? 'true' : 'false'}">
        <span class="fi-data-grid-toggle-thumb"></span>
        <span class="fi-sr-only">${escapeHtml(isOn ? translations.toggle.on : translations.toggle.off)}</span>
    </span>`

export const isBlank = (value) => value === null || value === undefined || value === ''

export const sameValue = (a, b) => (isBlank(a) && isBlank(b)) || String(a) === String(b)

export const selectLabel = ({ value, cell, serverValue, rememberedLabel }) => {
    if (isBlank(value)) {
        return null
    }

    if (sameValue(value, serverValue) && !isBlank(cell.label)) {
        return cell.label
    }

    if (rememberedLabel !== undefined) {
        return rememberedLabel
    }

    const option = (cell.options ?? []).find((choice) => sameValue(choice.value, value))

    return option ? option.label : String(value)
}

export const renderCell = ({ type, value, cell, label, placeholder, translations }) => {
    switch (type) {
        case 'entry':
            return htmlElement(cell.html ?? '', 'fi-data-grid-entry')
        case 'toggle':
            return toggleHtml(Boolean(value), translations)
        case 'select':
        case 'search':
            return isBlank(label)
                ? `<span class="fi-data-grid-placeholder">${escapeHtml(placeholder)}</span>`
                : escapeHtml(label)
        default:
            return isBlank(value)
                ? `<span class="fi-data-grid-placeholder">${escapeHtml(placeholder)}</span>`
                : escapeHtml(value)
    }
}

export const renderActions = (html) => htmlElement(html ?? '', 'fi-data-grid-row-actions')

export const headerHtml = (column) => {
    const label = escapeHtml(column.label)
    const required = column.required ? '<sup class="fi-data-grid-required">*</sup>' : ''

    return column.headerHidden ? `<span class="fi-sr-only">${label}</span>` : `${label}${required}`
}
