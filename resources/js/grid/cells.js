export const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (character) => `&#${character.charCodeAt(0)};`)

export const htmlElement = (html, className) => {
    const element = document.createElement('div')
    element.className = className
    element.innerHTML = html

    return element
}

export const isBlank = (value) => value === null || value === undefined || value === ''

export const sameValue = (a, b) => (isBlank(a) && isBlank(b)) || JSON.stringify(a) === JSON.stringify(b) || String(a) === String(b)

const toggleHtml = (isOn, translations) => `
    <span class="fi-data-grid-toggle ${isOn ? 'fi-data-grid-toggle-on' : ''}" role="switch" aria-checked="${isOn ? 'true' : 'false'}">
        <span class="fi-data-grid-toggle-thumb"></span>
        <span class="fi-sr-only">${escapeHtml(isOn ? translations.toggle.on : translations.toggle.off)}</span>
    </span>`

const textWithIcon = (text, placeholder, iconHtml) => `
    <span class="fi-data-grid-cell-text ${isBlank(text) ? 'fi-data-grid-placeholder' : ''}">${escapeHtml(isBlank(text) ? placeholder : text)}</span>
    <span class="fi-data-grid-cell-icon">${iconHtml ?? ''}</span>`

const formatDate = (value, inputType) => {
    const [datePart, timePart = ''] = String(value).split(' ')
    const [year, month, day] = datePart.split('-').map(Number)
    const [hours = 0, minutes = 0] = timePart.split(':').map(Number)

    if (inputType === 'time') {
        return String(value).slice(0, 5)
    }

    if (!year || !month || !day) {
        return String(value)
    }

    const date = new Date(year, month - 1, day, hours, minutes)
    const options = inputType === 'datetime-local' ? { dateStyle: 'medium', timeStyle: 'short' } : { dateStyle: 'medium' }

    return new Intl.DateTimeFormat(document.documentElement.lang || undefined, options).format(date)
}

export const cellLabel = ({ value, cell, serverValue, inputType = null }) => {
    if (isBlank(value)) {
        return null
    }

    if (sameValue(value, serverValue) && !isBlank(cell.label)) {
        return cell.label
    }

    return inputType ? formatDate(value, inputType) : String(value)
}

export const renderCell = ({ type, value, cell, label, placeholder, translations, icons }) => {
    switch (type) {
        case 'entry':
            return htmlElement(cell.html ?? '', 'fi-data-grid-entry')
        case 'toggle':
            return toggleHtml(Boolean(value), translations)
        case 'date':
            return textWithIcon(label, placeholder, icons.date)
        case 'modal':
            return textWithIcon(label, placeholder, icons.edit)
        default:
            return isBlank(value)
                ? `<span class="fi-data-grid-placeholder">${escapeHtml(placeholder)}</span>`
                : `<span class="fi-data-grid-cell-text">${escapeHtml(value)}</span>`
    }
}

export const renderActions = (html) => {
    const element = htmlElement(html ?? '', 'fi-data-grid-row-actions')

    element.addEventListener('mousedown', (event) => event.stopPropagation())

    return element
}

export const headerHtml = (column) => {
    const label = escapeHtml(column.label)
    const required = column.required ? '<sup class="fi-data-grid-required">*</sup>' : ''

    return column.headerHidden ? `<span class="fi-sr-only">${label}</span>` : `${label}${required}`
}
