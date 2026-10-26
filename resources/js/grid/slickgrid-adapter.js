import { SlickEditorLock, SlickGrid, SlickRowMoveManager } from '@slickgrid-universal/common'
import { cellLabel, escapeHtml, headerHtml, isBlank, renderActions, renderCell } from './cells.js'
import { DateEditor, NumberEditor, TextEditor } from './editors.js'
import { mountSelectCell } from './select-cell.js'

const ACTIONS_COLUMN = '__actions'
const MOVE_COLUMN = '__move'
const ROW_HEIGHT = 44
const FLEXIBLE_MIN_WIDTH = 240
const ACTIONS_WIDTH = 56
const MOVE_WIDTH = 36

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

const EDITORS = { text: TextEditor, number: NumberEditor, date: DateEditor }

const stripHtml = (html) => {
    const element = document.createElement('div')
    element.innerHTML = html ?? ''

    return element.textContent ?? ''
}

export function createGrid(
    element,
    { payload, translations, icons, isDarkMode, onCellChange, callSchemaComponentMethod, onOpenCell, onReorder },
) {
    let rows = new Map()
    let items = []
    let frozenLength = null
    const selects = new Map()
    const userWidths = new Map()

    const selectKey = (rowKey, field) => `${rowKey}.${field}`

    const openSelect = () => [...selects.values()].find(({ select }) => select.isOpen) ?? null

    const destroyDetachedSelects = () => {
        for (const [key, entry] of selects) {
            if (!entry.element.isConnected) {
                entry.select.destroy()
                selects.delete(key)
            }
        }
    }

    const columnDefinitions = () => {
        const columns = payload.columns.map((column) => ({
            id: column.field,
            field: column.field,
            name: headerHtml(column),
            fixedWidth: userWidths.get(column.field) ?? toPixels(column.width),
            width: userWidths.get(column.field) ?? toPixels(column.width) ?? FLEXIBLE_MIN_WIDTH,
            minWidth: 60,
            resizable: true,
            sortable: false,
            focusable: column.editor.type !== 'entry',
            cssClass: `fi-data-grid-cell fi-data-grid-cell-${column.editor.type} fi-align-${column.alignment ?? 'start'}`,
            headerCssClass: `fi-data-grid-header fi-align-${column.alignment ?? 'start'}`,
            editorClass: EDITORS[column.editor.type] ?? null,
            editor: { ...column.editor, label: column.label },
            source: column,
            formatter: formatCell,
        }))

        if (payload.reorderable) {
            columns.unshift({
                id: MOVE_COLUMN,
                field: MOVE_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.columns.reorder)}</span>`,
                behavior: 'selectAndMove',
                fixedWidth: MOVE_WIDTH,
                width: MOVE_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-move-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) =>
                    rows.get(item.__key)?.isTrailingBlank
                        ? ''
                        : {
                              html: `<span class="fi-data-grid-move-handle" title="${escapeHtml(translations.columns.reorder)}">${icons.reorder}</span>`,
                              addClasses: 'cell-reorder dnd',
                          },
            })
        }

        if (payload.hasActions) {
            columns.push({
                id: ACTIONS_COLUMN,
                field: ACTIONS_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.columns.actions)}</span>`,
                fixedWidth: ACTIONS_WIDTH,
                width: ACTIONS_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-actions-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) => {
                    const template = payload.actionTemplates?.[rows.get(item.__key)?.actions]

                    return renderActions(template?.replaceAll(payload.rowKeyPlaceholder, item.__key))
                },
            })
        }

        const frozenCount = payload.frozenColumns + (payload.reorderable && payload.frozenColumns > 0 ? 1 : 0)

        if (frozenCount > 0) {
            columns[frozenCount - 1].cssClass += ' fi-data-grid-frozen-edge'
            columns[frozenCount - 1].headerCssClass += ' fi-data-grid-frozen-edge'
        }

        columns[0].cssClass += ' fi-data-grid-first-column'
        columns[columns.length - 1].cssClass += ' fi-data-grid-last-column'

        return columns
    }

    const frozenColumnIndex = () => {
        if (payload.frozenColumns <= 0) {
            return -1
        }

        return payload.frozenColumns - 1 + (payload.reorderable ? 1 : 0)
    }

    function formatCell(rowIndex, cellIndex, value, column, item) {
        const row = rows.get(item.__key)
        const cell = row?.cells?.[column.field]

        if (!cell?.visible) {
            return { html: '', addClasses: 'fi-data-grid-cell-hidden' }
        }

        const type = column.source.editor.type
        const classes = [
            cell.error ? 'fi-data-grid-cell-invalid' : '',
            cell.disabled ? 'fi-data-grid-cell-disabled' : '',
            ['toggle', 'modal'].includes(type) && !cell.disabled ? 'fi-data-grid-cell-clickable' : '',
        ]
            .filter(Boolean)
            .join(' ')

        if (type === 'select') {
            return { html: selectCellElement(item, column, cell, value), addClasses: classes, toolTip: cell.error ?? '' }
        }

        const label = ['date', 'modal'].includes(type)
            ? cellLabel({
                  value,
                  cell,
                  serverValue: row.state?.[column.field],
                  inputType: type === 'date' ? column.source.editor.inputType : null,
              })
            : null
        const placeholder = row.isTrailingBlank || ['date', 'modal'].includes(type) ? (column.source.editor.placeholder ?? '') : ''
        const fullText = type === 'entry' || type === 'toggle' ? '' : (label ?? (isBlank(value) ? '' : String(value)))

        return {
            html: renderCell({ type, value, cell, label, placeholder, translations, icons }),
            addClasses: classes,
            toolTip: cell.error ?? fullText,
        }
    }

    function selectCellElement(item, column, cell, value) {
        const key = selectKey(item.__key, column.field)
        const wrapper = document.createElement('div')
        wrapper.className = 'fi-data-grid-select fi-select-input'

        selects.get(key)?.select.destroy()

        const serverValue = rows.get(item.__key)?.state?.[column.field]
        const label = JSON.stringify(serverValue) === JSON.stringify(value) ? cell.label : (selects.get(key)?.label ?? null)

        const select = mountSelectCell({
            element: wrapper,
            column,
            cell,
            value,
            label,
            callSchemaComponentMethod,
            onChange: (nextValue, nextLabel, { isMultiple }) => {
                const entry = selects.get(key)

                if (entry) {
                    entry.label = nextLabel
                }

                item[column.field] = nextValue
                wrapper.title = stripHtml(nextLabel ?? '')
                onCellChange(item.__key, column.field, nextValue, Boolean(rows.get(item.__key)?.cells?.[column.field]?.live))

                if (!isMultiple && wrapper.contains(document.activeElement)) {
                    moveFromSelect(item.__key, column.field)
                }
            },
        })

        wrapper.addEventListener('mousedown', (event) => {
            event.stopPropagation()
            activateCell(item.__key, column.field)
        })
        wrapper.addEventListener('click', (event) => event.stopPropagation())
        wrapper.addEventListener('keydown', (event) => {
            if (event.key === 'Tab') {
                event.preventDefault()
                event.stopPropagation()

                if (select.isOpen) {
                    select.closeDropdown()
                }

                focusCell(item.__key, column.field)

                if (event.shiftKey) {
                    grid.navigatePrev()
                } else {
                    grid.navigateNext()
                }

                return
            }

            if (event.key === 'Escape' && !select.isOpen) {
                return
            }

            event.stopPropagation()
        })

        wrapper.title = stripHtml(label ?? '')
        selects.set(key, { select, element: wrapper, label })

        return wrapper
    }

    function activateCell(rowKey, field) {
        const rowIndex = items.findIndex((item) => item.__key === rowKey)
        const cellIndex = grid.getColumnIndex(field)
        const active = grid.getActiveCell()

        if (rowIndex === -1 || cellIndex === undefined || (active?.row === rowIndex && active?.cell === cellIndex)) {
            return
        }

        grid.getEditorLock().commitCurrentEdit()
        grid.setActiveCell(rowIndex, cellIndex, false)
    }

    function focusCell(rowKey, field) {
        const rowIndex = items.findIndex((item) => item.__key === rowKey)
        const cellIndex = grid.getColumnIndex(field)

        if (rowIndex === -1 || cellIndex === undefined) {
            return false
        }

        grid.setActiveCell(rowIndex, cellIndex)
        grid.focus()

        return true
    }

    const moveFromSelect = (rowKey, field) =>
        setTimeout(() => {
            if (focusCell(rowKey, field)) {
                grid.navigateNext()
            }
        })

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
        frozenColumn: frozenColumnIndex(),
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

    const rowMoveManager = new SlickRowMoveManager()
    grid.registerPlugin(rowMoveManager)
    rowMoveManager.setOptions({
        singleRowMove: true,
        disableRowSelection: true,
        hideRowMoveShadow: true,
        onBeforeMoveRows: (event, { rows: moving, insertBefore }) => {
            const from = moving[0]
            const trailingIndex = items.findIndex((item) => rows.get(item.__key)?.isTrailingBlank)
            const lastMovable = trailingIndex === -1 ? items.length : trailingIndex

            return !(
                rows.get(items[from]?.__key)?.isTrailingBlank ||
                insertBefore > lastMovable ||
                insertBefore === from ||
                insertBefore === from + 1
            )
        },
        onMoveRows: (event, { rows: moving, insertBefore }) => {
            const from = moving[0]
            const [moved] = items.splice(from, 1)
            items.splice(insertBefore > from ? insertBefore - 1 : insertBefore, 0, moved)
            grid.invalidate()
            onReorder(items.map((item) => item.__key))
        },
    })
    rowMoveManager.usabilityOverride((rowIndex, item) => payload.reorderable && !rows.get(item?.__key)?.isTrailingBlank)

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

        if (!changed || isBusy()) {
            return
        }

        headerHeight = currentHeaderHeight
        columns.forEach((column) => {
            column.width = column.fixedWidth ?? flexibleWidth
        })

        grid.setColumns(columns)
        grid.resizeCanvas()
    }

    function markHorizontalOverflow() {
        const overflows = [...element.querySelectorAll('.slick-viewport')].some(
            (viewport) => viewport.scrollWidth > viewport.clientWidth,
        )

        element.classList.toggle('fi-data-grid-overflows', overflows)
    }

    const isBusy = () => grid.getEditorLock().isActive() || openSelect() !== null

    let resizeFrame = null

    const resizeObserver = new ResizeObserver(() => {
        cancelAnimationFrame(resizeFrame)
        resizeFrame = requestAnimationFrame(() => {
            fitColumns()
            markHorizontalOverflow()
        })
    })
    resizeObserver.observe(element)
    element.querySelectorAll('.slick-header').forEach((header) => resizeObserver.observe(header))

    grid.onColumnsResized.subscribe(() => {
        grid.getColumns().forEach((column) => {
            if (column.source && column.width !== column.fixedWidth && column.previousWidth !== column.width) {
                userWidths.set(column.field, column.width)
                column.fixedWidth = column.width
            }
        })
        markHorizontalOverflow()
    })

    grid.onRendered.subscribe(() => destroyDetachedSelects())

    grid.onBeforeEditCell.subscribe((event, { item, column }) => {
        const cell = rows.get(item.__key)?.cells?.[column.field]

        if (!cell?.visible || cell.disabled) {
            return false
        }

        Object.assign(column.editor, { min: cell.min, max: cell.max, step: cell.step })

        return true
    })

    grid.onCellChange.subscribe((event, { item, column }) => {
        const cell = rows.get(item.__key)?.cells?.[column.field]

        onCellChange(item.__key, column.field, isBlank(item[column.field]) ? null : item[column.field], Boolean(cell?.live))
    })

    const releaseFrozenLength = () =>
        queueMicrotask(() => {
            if (frozenLength === null || isBusy()) {
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

    const cellAt = (rowIndex, cellIndex) => {
        const column = grid.getColumns()[cellIndex]
        const item = items[rowIndex]
        const cell = rows.get(item?.__key)?.cells?.[column?.field]

        return { column, item, cell, type: column?.source?.editor.type }
    }

    const toggleCell = (rowIndex, cellIndex) => {
        const { column, item, cell, type } = cellAt(rowIndex, cellIndex)

        if (type !== 'toggle' || !cell?.visible || cell.disabled) {
            return false
        }

        item[column.field] = !item[column.field]
        grid.invalidateRow(rowIndex)
        grid.render()
        onCellChange(item.__key, column.field, item[column.field], Boolean(cell.live))

        return true
    }

    const openModalCell = (rowIndex, cellIndex) => {
        const { column, item, cell, type } = cellAt(rowIndex, cellIndex)

        if (type !== 'modal' || !cell?.visible || cell.disabled) {
            return false
        }

        onOpenCell(item.__key, column.field)

        return true
    }

    const openSelectCell = (rowIndex, cellIndex, character = null) => {
        const { column, item, cell, type } = cellAt(rowIndex, cellIndex)

        if (type !== 'select' || !cell?.visible || cell.disabled) {
            return false
        }

        const select = selects.get(selectKey(item.__key, column.field))?.select

        if (!select) {
            return false
        }

        select.selectButton.focus()

        if (!select.isOpen) {
            select.openDropdown()
        }

        if (character && select.searchInput) {
            select.searchInput.value = character
            select.searchInput.dispatchEvent(new Event('input', { bubbles: true }))
        }

        return true
    }

    grid.onClick.subscribe((event, { row, cell }) => {
        if (event.target.closest?.('.fi-data-grid-row-actions, .fi-data-grid-select')) {
            return
        }

        toggleCell(row, cell) || openModalCell(row, cell)
    })

    const moveVertically = (event, direction) => {
        if (grid.getCellEditor() && !grid.getEditorLock().commitCurrentEdit()) {
            event.stopImmediatePropagation()

            return
        }

        const active = grid.getActiveCell()
        const target = active ? active.row + direction : -1

        if (target >= 0 && target < grid.getDataLength()) {
            grid.setActiveCell(target, active.cell)
        }

        event.stopImmediatePropagation()
    }

    grid.onKeyDown.subscribe((event, { row, cell }) => {
        const hasModifier = event.altKey || event.ctrlKey || event.metaKey
        const editing = Boolean(grid.getCellEditor())

        if (!editing && event.key === ' ' && toggleCell(row, cell)) {
            event.preventDefault()
            event.stopImmediatePropagation()

            return
        }

        if (!editing && !hasModifier && (event.key === 'Enter' || event.key === 'F2') && (openSelectCell(row, cell) || openModalCell(row, cell))) {
            event.preventDefault()
            event.stopImmediatePropagation()

            return
        }

        if (!editing && !hasModifier && event.key.length === 1 && event.key !== ' ' && openSelectCell(row, cell, event.key)) {
            event.preventDefault()
            event.stopImmediatePropagation()

            return
        }

        if (event.key === 'Enter' && !hasModifier) {
            moveVertically(event, event.shiftKey ? -1 : 1)
        }
    })

    fitColumns()
    markHorizontalOverflow()

    return {
        isEditing: () => isBusy(),

        setPayload(nextPayload, localChanges) {
            const columnsChanged = JSON.stringify(nextPayload.columns) !== JSON.stringify(payload.columns)
            const structureChanged =
                nextPayload.reorderable !== payload.reorderable ||
                nextPayload.hasActions !== payload.hasActions ||
                nextPayload.frozenColumns !== payload.frozenColumns
            const editor = grid.getCellEditor()
            const active = grid.getActiveCell()
            const open = openSelect()
            const busyKey = editor && active ? items[active.row]?.__key : (open ? [...selects.entries()].find(([, entry]) => entry === open)?.[0].split('.')[0] : null)
            const busyItem = busyKey ? items.find((item) => item.__key === busyKey) : null
            const busyField = editor && active ? grid.getColumns()[active.cell]?.field : null
            const renderedLength = dataProvider.getLength()

            payload = nextPayload
            indexRows()
            items = buildItems(localChanges)

            if (columnsChanged || structureChanged) {
                grid.getEditorLock().cancelCurrentEdit()
                frozenLength = null
                grid.setOptions({ frozenColumn: frozenColumnIndex() })
                grid.setColumns(columnDefinitions())
                fitColumns()
                grid.invalidate()

                return
            }

            const busyIndex = busyItem ? items.findIndex((item) => item.__key === busyItem.__key) : -1
            const busyRowIndexBefore = editor && active ? active.row : busyIndex

            if (busyIndex === -1 || busyIndex !== busyRowIndexBefore) {
                if (editor) {
                    grid.getEditorLock().cancelCurrentEdit()
                }

                frozenLength = null
                grid.invalidate()
                grid.resizeCanvas()

                return
            }

            const keepsTypedValue = editor ? editor.isValueChanged() : false
            const typedFieldValue = busyField ? busyItem[busyField] : undefined

            Object.keys(busyItem).forEach((key) => delete busyItem[key])
            Object.assign(busyItem, items[busyIndex])

            if (keepsTypedValue) {
                busyItem[busyField] = typedFieldValue
            }

            items[busyIndex] = busyItem

            if (editor && renderedLength !== items.length && !keepsTypedValue) {
                frozenLength = null
                grid.invalidate()
                grid.resizeCanvas()
                grid.setActiveCell(active.row, active.cell)
                grid.editActiveCell()

                return
            }

            if (renderedLength !== items.length) {
                if (editor) {
                    frozenLength = renderedLength
                } else {
                    grid.updateRowCount()
                    grid.resizeCanvas()
                }
            }

            grid.invalidateRows(
                items.map((item, index) => index).filter((index) => index !== busyIndex && index < dataProvider.getLength()),
            )

            if (editor) {
                grid.getColumns().forEach((column, index) => {
                    if (index !== active.cell || !keepsTypedValue) {
                        grid.updateCell(busyIndex, index)
                    }
                })
            }

            grid.render()
        },

        setDarkMode(isDark) {
            grid.setOptions({ darkMode: isDark })
            grid.invalidate()
        },

        destroy() {
            cancelAnimationFrame(resizeFrame)
            resizeObserver.disconnect()
            selects.forEach(({ select }) => select.destroy())
            selects.clear()
            rowMoveManager.dispose()
            grid.destroy()
        },
    }
}

