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

const valueHtml = (text, placeholder, isEditable) => {
    const blank = isBlank(text)
    const classes = [
        'fi-data-grid-cell-text',
        blank ? 'fi-data-grid-placeholder' : '',
        isEditable ? 'fi-data-grid-editable-value' : '',
        isEditable && blank && isBlank(placeholder) ? 'fi-data-grid-editable-value-empty' : '',
    ]

    return `<span class="${classes.filter(Boolean).join(' ')}">${escapeHtml(blank ? placeholder : text)}</span>`
}

const iconHtml = (html) => (html ? `<span class="fi-data-grid-cell-icon">${html}</span>` : '')

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

export const renderCell = ({ type, value, cell, label, placeholder, translations, icons, isEditable = false, cellIcon = null }) => {
    switch (type) {
        case 'entry':
            return htmlElement(cell.html ?? '', 'fi-data-grid-entry')
        case 'toggle':
            return toggleHtml(Boolean(value), translations)
        case 'date':
            return valueHtml(label, placeholder, isEditable) + iconHtml(cellIcon ?? icons.date)
        case 'modal':
            return valueHtml(label, placeholder, isEditable) + iconHtml(cellIcon ?? icons.edit)
        default:
            return valueHtml(value, placeholder, isEditable) + iconHtml(cellIcon)
    }
}

export const renderActions = (html) => {
    const element = htmlElement(html ?? '', 'fi-data-grid-row-actions')

    element.addEventListener('mousedown', (event) => event.stopPropagation())

    return element
}

export const headerHtml = (column, editIcon = null) => {
    const label = escapeHtml(column.label)
    const required = column.required ? '<sup class="fi-data-grid-required">*</sup>' : ''

    if (column.headerHidden) {
        return `<span class="fi-sr-only">${label}</span>`
    }

    return editIcon
        ? `<span class="fi-data-grid-header-label">${label}${required}</span><span class="fi-data-grid-header-edit" aria-hidden="true">${editIcon}</span>`
        : `${label}${required}`
}

const fittedIcons = new Map()

export const fitIconHtml = (html, align, host) => {
    if (!html || !html.includes('<svg')) {
        return html
    }

    const cacheKey = `${align}|${html}`

    if (fittedIcons.has(cacheKey)) {
        return fittedIcons.get(cacheKey)
    }

    const container = document.createElement('div')
    container.style.cssText = 'position: absolute; visibility: hidden; pointer-events: none'
    container.innerHTML = html
    host.appendChild(container)

    const svg = container.querySelector('svg')
    const viewBox = svg?.viewBox?.baseVal
    const box = svg && viewBox?.width ? svg.getBBox() : null

    if (box?.width) {
        svg.setAttribute('viewBox', `${box.x} ${viewBox.y} ${box.width} ${viewBox.height}`)
        svg.setAttribute('preserveAspectRatio', `${{ start: 'xMin', center: 'xMid', end: 'xMax' }[align]}YMid meet`)
    }

    container.remove()

    const fitted = box?.width ? container.innerHTML : html

    if (box?.width) {
        fittedIcons.set(cacheKey, fitted)
    }

    return fitted
}

const MAX_GLYPH_OFFSET = 4

const glyphLeft = (svg) => {
    const shapes = [...svg.querySelectorAll('path, circle, rect, line, polyline, polygon, ellipse')]

    return shapes.length > 0 ? Math.min(...shapes.map((shape) => shape.getBoundingClientRect().left)) : svg.getBoundingClientRect().left
}

export const headerGlyphOffset = (element) => {
    for (let card = element.closest('.fi-section, .fi-ta-ctn'); card; card = card.parentElement?.closest('.fi-section, .fi-ta-ctn')) {
        const icon = card.querySelector(':scope > .fi-section-header > svg, :scope > .fi-ta-main > .fi-ta-header-ctn .fi-ta-header svg, :scope > .fi-ta-main > .fi-ta-header svg')

        if (icon) {
            return Math.min(MAX_GLYPH_OFFSET, Math.max(0, glyphLeft(icon) - icon.getBoundingClientRect().left))
        }
    }

    return 0
}
