import { SlickEditorLock, SlickGrid } from '@slickgrid-universal/common'
import { headerHtml, isBlank, renderActions, renderCell, selectLabel } from './cells.js'
import { NativeSelectEditor, NumberEditor, SearchSelectEditor, TextEditor } from './editors.js'

const ACTIONS_COLUMN = '__actions'
const ROW_HEIGHT = 44
const FLEXIBLE_MIN_WIDTH = 200
const ACTIONS_WIDTH = 56

const toPixels = (width) => {
    if (!width) {
        return null
    }

    const number = parseFloat(width)

    if (String(width).endsWith('rem')) {
        return number * parseFloat(getComputedStyle(document.documentElement).fontSize)
    }

    return String(width).endsWith('px') || /^\d+$/.test(String(width)) ? number : null
}

const editorClassFor = (editor) => {
    switch (editor.type) {
        case 'text':
            return TextEditor
        case 'number':
            return NumberEditor
        case 'search':
            return SearchSelectEditor
        case 'select':
            return editor.searchable ? SearchSelectEditor : NativeSelectEditor
        default:
            return null
    }
}

export function createGrid(element, { payload, translations, isDarkMode, onCellChange, search }) {
    let rows = new Map()
    let items = []
    const rememberedLabels = new Map()

    const labelKey = (rowKey, field, value) => `${rowKey}.${field}.${value}`

    const columnDefinitions = () => {
        const columns = payload.columns.map((column) => ({
            id: column.field,
            field: column.field,
            name: headerHtml(column),
            fixedWidth: toPixels(column.width),
            width: toPixels(column.width) ?? FLEXIBLE_MIN_WIDTH,
            minWidth: 60,
            resizable: false,
            sortable: false,
            focusable: column.editor.type !== 'entry',
            cssClass: `fi-data-grid-cell fi-align-${column.alignment ?? 'start'}`,
            headerCssClass: `fi-data-grid-header fi-align-${column.alignment ?? 'start'}`,
            editorClass: editorClassFor(column.editor),
            editor: { ...column.editor, label: column.label },
            source: column,
            formatter: formatCell,
        }))

        if (payload.hasActions) {
            columns.push({
                id: ACTIONS_COLUMN,
                field: ACTIONS_COLUMN,
                name: `<span class="fi-sr-only">${translations.columns.actions}</span>`,
                fixedWidth: ACTIONS_WIDTH,
                width: ACTIONS_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-actions-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) => renderActions(rows.get(item.__key)?.actions),
            })
        }

        columns[0].cssClass += ' fi-data-grid-first-column'
        columns[columns.length - 1].cssClass += ' fi-data-grid-last-column'

        return columns
    }

    function formatCell(rowIndex, cellIndex, value, column, item) {
        const row = rows.get(item.__key)
        const cell = row?.cells?.[column.field]

        if (!cell?.visible) {
            return { html: '', addClasses: 'fi-data-grid-cell-hidden' }
        }

        const type = column.source.editor.type
        const label = ['select', 'search'].includes(type)
            ? selectLabel({
                  value,
                  cell,
                  serverValue: row.state?.[column.field],
                  rememberedLabel: rememberedLabels.get(labelKey(item.__key, column.field, value)),
              })
            : null

        const classes = [
            cell.error ? 'fi-data-grid-cell-invalid' : '',
            cell.disabled ? 'fi-data-grid-cell-disabled' : '',
            type === 'toggle' && !cell.disabled ? 'fi-data-grid-cell-clickable' : '',
        ]

        return {
            html: renderCell({
                type,
                value,
                cell,
                label,
                placeholder: row.isTrailingBlank ? (column.source.editor.placeholder ?? '') : '',
                translations,
            }),
            addClasses: classes.filter(Boolean).join(' '),
            toolTip: cell.error ?? '',
        }
    }

    let frozenLength = null

    const dataProvider = {
        getLength: () => frozenLength ?? items.length,
        getItem: (index) => items[index],
        getItemMetadata: (index) => {
            const row = rows.get(items[index]?.__key)
            const columns = {}

            for (const column of payload.columns) {
                const cell = row?.cells?.[column.field]

                if (!cell?.visible || cell.disabled) {
                    columns[column.field] = { editorClass: null }
                }
            }

            return {
                cssClasses: [
                    row?.isTrailingBlank ? 'fi-data-grid-row-trailing' : '',
                    index === dataProvider.getLength() - 1 ? 'fi-data-grid-row-last' : '',
                ].join(' '),
                columns,
            }
        },
    }

    const buildItems = (localChanges) =>
        payload.rows.map((row) => {
            const item = { __key: row.key, ...row.state }

            for (const column of payload.columns) {
                const cellKey = `${row.key}.${column.field}`

                if (localChanges?.has(cellKey)) {
                    item[column.field] = localChanges.get(cellKey)
                }
            }

            return item
        })

    const indexRows = () => {
        rows = new Map(payload.rows.map((row) => [row.key, row]))
    }

    indexRows()
    items = buildItems()

    const grid = new SlickGrid(element, dataProvider, columnDefinitions(), {
        editable: true,
        editorLock: new SlickEditorLock(),
        autoEdit: true,
        autoCommitEdit: true,
        enableCellNavigation: true,
        enableColumnReorder: false,
        enableAsyncPostRender: false,
        autoHeight: true,
        rowHeight: ROW_HEIGHT,
        showHeaderRow: false,
        showTopPanel: false,
        showPreHeaderPanel: false,
        showFooterRow: false,
        enableTextSelectionOnCells: true,
        editCommandHandler: null,
        darkMode: isDarkMode,
        showCellSelection: true,
        devMode: false,
    })

    element.classList.add('fi-data-grid-slick')

    let headerHeight = null

    const fitColumns = () => {
        const columns = grid.getColumns()
        const available = element.clientWidth
        const fixed = columns.reduce((sum, column) => sum + (column.fixedWidth ?? 0), 0)
        const flexible = columns.filter((column) => !column.fixedWidth)
        const flexibleWidth = Math.max(
            FLEXIBLE_MIN_WIDTH,
            Math.floor((available - fixed) / Math.max(flexible.length, 1)),
        )

        const currentHeaderHeight = element.querySelector('.slick-header')?.offsetHeight ?? null
        const changed =
            currentHeaderHeight !== headerHeight ||
            columns.some((column) => column.width !== (column.fixedWidth ?? flexibleWidth))

        if (!changed || grid.getEditorLock().isActive()) {
            return
        }

        headerHeight = currentHeaderHeight
        columns.forEach((column) => {
            column.width = column.fixedWidth ?? flexibleWidth
        })

        grid.setColumns(columns)
        grid.resizeCanvas()
    }

    let resizeFrame = null

    const resizeObserver = new ResizeObserver(() => {
        cancelAnimationFrame(resizeFrame)
        resizeFrame = requestAnimationFrame(() => fitColumns())
    })
    resizeObserver.observe(element)
    element.querySelectorAll('.slick-header').forEach((header) => resizeObserver.observe(header))

    grid.onBeforeEditCell.subscribe((event, { item, column }) => {
        const row = rows.get(item.__key)
        const cell = row?.cells?.[column.field]

        if (!cell?.visible || cell.disabled) {
            return false
        }

        const options = column.editor

        Object.assign(options, { min: cell.min, max: cell.max, step: cell.step })

        if (column.source.editor.type === 'select') {
            options.choices = cell.options ?? []
        }

        if (['select', 'search'].includes(column.source.editor.type)) {
            options.currentLabel = selectLabel({
                value: item[column.field],
                cell,
                serverValue: row.state?.[column.field],
                rememberedLabel: rememberedLabels.get(labelKey(item.__key, column.field, item[column.field])),
            })
            options.rememberLabel = (rowItem, value, label) =>
                rememberedLabels.set(labelKey(rowItem.__key, column.field, value), label)
            options.options = searchOptions(column, cell)
        }

        return true
    })

    const searchOptions = (column, cell) => {
        const editor = column.source.editor
        const isRemote = editor.type === 'search'
        const localChoices = () =>
            (cell.options ?? []).map((option) => ({
                label: option.label,
                value: option.value,
                isDisabled: option.isDisabled,
            }))

        return {
            minLength: isRemote ? 1 : 0,
            showOnFocus: !isRemote,
            debounceWaitMs: isRemote ? (editor.searchDebounce ?? 300) : 0,
            emptyMsg: editor.noResultsMessage,
            className: 'fi-data-grid-autocomplete',
            hideClearButton: false,
            fetch: (term, update) => {
                if (!isRemote) {
                    const needle = term.toLowerCase()

                    update(localChoices().filter((option) => option.label.toLowerCase().includes(needle)))

                    return
                }

                search(cell.key, term).then((results) => update(results ?? []))
            },
            render: (option) => {
                const element = document.createElement('div')
                element.textContent = option.label
                element.className = option.isDisabled ? 'fi-data-grid-option fi-disabled' : 'fi-data-grid-option'

                return element
            },
        }
    }

    grid.onCellChange.subscribe((event, { item, column }) => {
        const cell = rows.get(item.__key)?.cells?.[column.field]

        onCellChange(item.__key, column.field, isBlank(item[column.field]) ? null : item[column.field], Boolean(cell?.live))
    })

    const releaseFrozenLength = () =>
        queueMicrotask(() => {
            if (frozenLength === null || grid.getCellEditor()) {
                return
            }

            frozenLength = null
            grid.invalidate()
            grid.resizeCanvas()
        })

    grid.onBeforeCellEditorDestroy.subscribe(releaseFrozenLength)
    grid.onActiveCellChanged.subscribe(releaseFrozenLength)
    element.addEventListener('keyup', releaseFrozenLength)
    element.addEventListener('focusout', releaseFrozenLength)

    grid.onValidationError.subscribe((event, { cellNode, validationResults }) => {
        cellNode?.setAttribute('title', validationResults.msg ?? '')
        cellNode?.classList.add('fi-data-grid-cell-invalid')
    })

    const toggleCell = (rowIndex, cellIndex) => {
        const column = grid.getColumns()[cellIndex]
        const item = items[rowIndex]
        const cell = rows.get(item?.__key)?.cells?.[column?.field]

        if (column?.source?.editor.type !== 'toggle' || !cell?.visible || cell.disabled) {
            return false
        }

        item[column.field] = !item[column.field]
        grid.invalidateRow(rowIndex)
        grid.render()
        onCellChange(item.__key, column.field, item[column.field], Boolean(cell.live))

        return true
    }

    grid.onClick.subscribe((event, { row, cell }) => {
        if (event.target.closest?.('.fi-data-grid-row-actions')) {
            return
        }

        toggleCell(row, cell)
    })

    grid.onKeyDown.subscribe((event, { row, cell }) => {
        if (event.key === ' ' && !grid.getCellEditor() && toggleCell(row, cell)) {
            event.preventDefault()
        }
    })

    fitColumns()

    return {
        isEditing: () => grid.getEditorLock().isActive(),

        setPayload(nextPayload, localChanges) {
            const columnsChanged = JSON.stringify(nextPayload.columns) !== JSON.stringify(payload.columns)
            const editor = grid.getCellEditor()
            const active = grid.getActiveCell()
            const editedItem = editor && active ? items[active.row] : null
            const editedField = editedItem ? grid.getColumns()[active.cell]?.field : null
            const renderedLength = dataProvider.getLength()

            payload = nextPayload
            indexRows()
            items = buildItems(localChanges)

            for (const key of rememberedLabels.keys()) {
                if (!rows.has(key.split('.')[0])) {
                    rememberedLabels.delete(key)
                }
            }

            if (columnsChanged) {
                grid.getEditorLock().cancelCurrentEdit()
                frozenLength = null
                grid.setColumns(columnDefinitions())
                fitColumns()
                grid.invalidate()

                return
            }

            const editedIndex = editedItem ? items.findIndex((item) => item.__key === editedItem.__key) : -1

            if (editedIndex === -1 || editedIndex !== active.row) {
                if (editor) {
                    grid.getEditorLock().cancelCurrentEdit()
                }

                frozenLength = null
                grid.invalidate()
                grid.resizeCanvas()

                return
            }

            const keepsTypedValue = editor.isValueChanged()
            const typedFieldValue = editedItem[editedField]

            Object.keys(editedItem).forEach((key) => delete editedItem[key])
            Object.assign(editedItem, items[editedIndex])

            if (keepsTypedValue) {
                editedItem[editedField] = typedFieldValue
            }

            items[editedIndex] = editedItem

            if (renderedLength !== items.length) {
                frozenLength = renderedLength
            }

            grid.invalidateRows(
                items.map((item, index) => index).filter((index) => index !== editedIndex && index < renderedLength),
            )
            grid.getColumns().forEach((column, index) => {
                if (index !== active.cell || !keepsTypedValue) {
                    grid.updateCell(editedIndex, index)
                }
            })
            grid.render()
        },

        setDarkMode(isDark) {
            grid.setOptions({ darkMode: isDark })
            grid.invalidate()
        },

        destroy() {
            cancelAnimationFrame(resizeFrame)
            resizeObserver.disconnect()
            grid.destroy()
        },
    }
}

