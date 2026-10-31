import { applyMask, hasMask, maskedText, maskInputText, releaseMask, stripMask } from './mask.js'

const commitOnFocusLeave = (editor, element) => {
    element.addEventListener('focusout', (event) => {
        if (editor.args.container.contains(event.relatedTarget)) {
            return
        }

        setTimeout(() => {
            if (editor.args.grid.getCellEditor() === editor) {
                editor.args.grid.getEditorLock().commitCurrentEdit()
            }
        })
    })
}

const blankToNull = (value) => (value === '' || value === undefined ? null : value)

class InputEditor {
    constructor(args) {
        this.args = args
        this.field = args.column.field
        this.options = args.column.editor
        this.input = document.createElement('input')
        this.input.className = 'fi-data-grid-editor-input'
        this.input.type = 'text'
        this.input.autocomplete = 'off'
        this.input.placeholder = this.options.placeholder ?? ''
        this.configure(this.input)
        args.container.append(this.input)
        commitOnFocusLeave(this, this.input)
        this.input.focus()
    }

    configure() {}

    destroy() {
        this.input.remove()
    }

    focus() {
        this.input.focus()
    }

    loadValue(item) {
        this.initialValue = blankToNull(item[this.field])
        this.input.value = this.initialValue ?? ''
        this.input.select()
    }

    serializeValue() {
        return blankToNull(this.input.value)
    }

    applyValue(item, value) {
        item[this.field] = value
    }

    isValueChanged() {
        return this.serializeValue() !== this.initialValue
    }

    validate() {
        return { valid: true, msg: null }
    }
}

export class TextEditor extends InputEditor {}

export class NumberEditor extends InputEditor {
    configure(input) {
        input.inputMode = this.options.integer ? 'numeric' : 'decimal'
        input.classList.add('fi-data-grid-editor-number')

        if (hasMask(this.options)) {
            applyMask(input, this.options)
        }
    }

    destroy() {
        if (hasMask(this.options)) {
            releaseMask(this.input)
        }

        super.destroy()
    }

    loadValue(item) {
        if (!hasMask(this.options)) {
            super.loadValue(item)

            return
        }

        const load = {}

        this.load = load
        this.initialValue = blankToNull(item[this.field])
        this.loadedText = this.initialValue === null ? '' : maskInputText(this.initialValue, this.options)
        this.input.value = this.loadedText
        this.input.select()

        if (this.initialValue === null) {
            return
        }

        const { text, ready } = maskedText(this.initialValue, this.options)
        const settle = (masked) => {
            if (this.load !== load) {
                return
            }

            const untouched = this.input.value === masked

            this.loadedText = masked

            if (untouched && document.activeElement === this.input) {
                this.input.select()
            }
        }

        if (ready) {
            ready.then(settle)
        } else {
            queueMicrotask(() => settle(text))
        }
    }

    serializeValue() {
        const raw = stripMask(this.input.value, this.options).trim().replace(',', '.')

        if (raw === '') {
            return null
        }

        const number = Number(raw)

        return Number.isFinite(number) ? number : raw
    }

    isValueChanged() {
        if (hasMask(this.options)) {
            return stripMask(this.input.value, this.options) !== stripMask(this.loadedText, this.options)
        }

        return String(this.serializeValue() ?? '') !== String(this.initialValue ?? '')
    }

    validate() {
        const value = this.serializeValue()
        const { min, max, step, integer, messages, label } = this.options
        const fail = (message, replacements) => ({
            valid: false,
            msg: Object.entries({ attribute: label, ...replacements }).reduce(
                (text, [key, replacement]) => text.replaceAll(`:${key}`, replacement),
                message,
            ),
        })

        if (value === null) {
            return { valid: true, msg: null }
        }

        if (typeof value !== 'number') {
            return fail(messages.numeric)
        }

        if ((integer || Number(step) === 1) && !Number.isInteger(value)) {
            return fail(messages.integer)
        }

        if (min !== null && min !== undefined && value < Number(min)) {
            return fail(messages.min, { min })
        }

        if (max !== null && max !== undefined && value > Number(max)) {
            return fail(messages.max, { max })
        }

        return { valid: true, msg: null }
    }
}

const DATE_INPUT_LENGTH = { date: 10, 'datetime-local': 16, time: 5 }

export class DateEditor extends InputEditor {
    configure(input) {
        input.type = this.options.inputType
        input.classList.add('fi-data-grid-editor-date')

        if (this.options.step) {
            input.step = String(this.options.step)
        }

        if (this.options.min) {
            input.min = this.options.min
        }

        if (this.options.max) {
            input.max = this.options.max
        }
    }

    loadValue(item) {
        this.initialValue = blankToNull(item[this.field])
        this.input.value = this.toInputValue(this.initialValue)
    }

    serializeValue() {
        return this.fromInputValue(this.input.value)
    }

    isValueChanged() {
        return this.serializeValue() !== this.fromInputValue(this.toInputValue(this.initialValue))
    }

    validate() {
        const { messages, label } = this.options
        const replace = (message, value) =>
            message.replaceAll(':attribute', label).replaceAll(':date', value)

        if (this.input.validity.badInput) {
            return { valid: false, msg: replace(messages.date, '') }
        }

        if (this.input.validity.rangeUnderflow) {
            return { valid: false, msg: replace(messages.after_or_equal, this.options.min) }
        }

        if (this.input.validity.rangeOverflow) {
            return { valid: false, msg: replace(messages.before_or_equal, this.options.max) }
        }

        return { valid: true, msg: null }
    }

    toInputValue(value) {
        if (value === null) {
            return ''
        }

        const raw = String(value).replace(' ', 'T')
        const type = this.options.inputType
        const length = this.options.step ? DATE_INPUT_LENGTH[type] + 3 : DATE_INPUT_LENGTH[type]

        if (type === 'time') {
            return (raw.includes('T') ? raw.split('T')[1] : raw).slice(0, length)
        }

        return raw.slice(0, length)
    }

    fromInputValue(value) {
        if (value === '') {
            return null
        }

        const format = this.options.internalFormat
        const [date, time = ''] = value.split('T')

        if (this.options.inputType === 'time') {
            return format.includes(':s') && value.length === 5 ? `${value}:00` : value
        }

        if (!format.includes('H')) {
            return date
        }

        const withMinutes = (time || '00:00').slice(0, 5)
        const seconds = format.includes(':s') ? `:${(time.slice(6, 8) || '00')}` : ''

        return `${date} ${withMinutes}${seconds}`
    }
}
