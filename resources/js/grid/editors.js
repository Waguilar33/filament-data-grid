import { AutocompleterEditor } from '@slickgrid-universal/common'

const commitOnFocusLeave = (editor, element) => {
    element.addEventListener('focusout', (event) => {
        if (editor.args.container.contains(event.relatedTarget)) {
            return
        }

        if (event.relatedTarget?.closest?.('.fi-data-grid-autocomplete')) {
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
    }

    serializeValue() {
        const raw = this.input.value.trim().replace(',', '.')

        if (raw === '') {
            return null
        }

        const number = Number(raw)

        return Number.isFinite(number) ? number : raw
    }

    isValueChanged() {
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

export class NativeSelectEditor {
    constructor(args) {
        this.args = args
        this.field = args.column.field
        this.options = args.column.editor
        this.select = document.createElement('select')
        this.select.className = 'fi-data-grid-editor-select'

        const placeholder = new Option(this.options.placeholder ?? '', '')
        this.select.append(placeholder)

        for (const option of this.options.choices ?? []) {
            const element = new Option(option.label, option.value)
            element.disabled = Boolean(option.isDisabled)
            this.select.append(element)
        }

        this.select.addEventListener('change', () => args.grid.getEditorLock().commitCurrentEdit())
        args.container.append(this.select)
        commitOnFocusLeave(this, this.select)
        this.select.focus()
    }

    destroy() {
        this.select.remove()
    }

    focus() {
        this.select.focus()
    }

    loadValue(item) {
        this.initialValue = blankToNull(item[this.field])
        this.select.value = this.initialValue === null ? '' : String(this.initialValue)
    }

    serializeValue() {
        return blankToNull(this.select.value)
    }

    applyValue(item, value) {
        item[this.field] = value
    }

    isValueChanged() {
        return String(this.serializeValue() ?? '') !== String(this.initialValue ?? '')
    }

    validate() {
        return { valid: true, msg: null }
    }
}

export class SearchSelectEditor extends AutocompleterEditor {
    handleSelect(item) {
        if (item?.isDisabled) {
            return false
        }

        this.columnEditor.rememberLabel?.(this.args.item, item.value, item.label)

        const result = super.handleSelect(item)

        setTimeout(() => this.args.grid.navigateNext())

        return result
    }

    focus() {
        if (this._inputElm.value === this._defaultTextValue) {
            super.focus()

            return
        }

        this._inputElm.focus()
    }

    loadValue(item) {
        const value = blankToNull(item[this.columnDef.field])
        const label = value === null ? '' : (this.columnEditor.currentLabel ?? String(value))

        this._currentValue = value === null ? null : { value, label }
        this._originalValue = this._currentValue
        this._defaultTextValue = label
        this._inputElm.value = label
        this._inputElm.select()
    }

    serializeValue() {
        return blankToNull(super.serializeValue())
    }

    applyValue(item, value) {
        item[this.columnDef.field] = blankToNull(value)
    }

    validate() {
        return { valid: true, msg: '' }
    }
}
