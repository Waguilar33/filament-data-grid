import { SlickEditorLock, SlickGrid, SlickRowMoveManager } from '@slickgrid-universal/common'
import { cellLabel, escapeHtml, headerHtml, isBlank, renderActions, renderCell } from './cells.js'
import { DateEditor, NumberEditor, TextEditor } from './editors.js'
import { mountSelectCell } from './select-cell.js'
import { createChildRows } from './child-rows.js'

const ACTIONS_COLUMN = '__actions'
const MOVE_COLUMN = '__move'
const TOGGLE_COLUMN = '__toggle'
const TOGGLE_WIDTH = 44
const ROW_HEIGHT = 44
const FLEXIBLE_MIN_WIDTH = 240
const ACTIONS_WIDTH = 56
const MOVE_WIDTH = 36
const EDITABLE_TYPES = ['text', 'number', 'select', 'toggle', 'date', 'modal']

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

const isPlaceholder = (item) => item?.__childOf !== undefined

const INTERACTIVE = 'a, button, input, select, textarea, .fi-data-grid-row-actions, .fi-data-grid-select'

const stripHtml = (html) => {
    const element = document.createElement('div')
    element.innerHTML = html ?? ''

    return element.textContent ?? ''
}

export function createGrid(
    element,
    { payload, translations, icons, isDarkMode, onCellChange, callSchemaComponentMethod, onOpenCell, onReorder, createChild },
) {
    let rows = new Map()
    let items = []
    let frozenLength = null
    let pendingRowHeights = false
    const selects = new Map()
    const userWidths = new Map()
    const expanded = new Set()
    const seen = new Set()
    const hadChildErrors = new Map()
    const childRows = createChild
        ? createChildRows(element, { createChild, onResize: () => refreshRowHeights(), onFocus: () => yieldFocus() })
        : null

    const hasChildRows = () => Boolean(payload.hasChildRows && childRows)

    const isOpen = (key) => expanded.has(key) && Boolean(rows.get(key)?.child)

    const frozenColumns = () => (hasChildRows() ? 0 : payload.frozenColumns)

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

        if (hasChildRows()) {
            columns.unshift({
                id: TOGGLE_COLUMN,
                field: TOGGLE_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.entry.expand)}</span>`,
                fixedWidth: TOGGLE_WIDTH,
                width: TOGGLE_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-toggle-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) => formatToggle(item),
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

        const frozenCount = frozenColumns() + (payload.reorderable && frozenColumns() > 0 ? 1 : 0)

        if (frozenCount > 0) {
            columns[frozenCount - 1].cssClass += ' fi-data-grid-frozen-edge'
            columns[frozenCount - 1].headerCssClass += ' fi-data-grid-frozen-edge'
        }

        columns[0].cssClass += ' fi-data-grid-first-column'
        columns[columns.length - 1].cssClass += ' fi-data-grid-last-column'

        return columns.map((column) => {
            const format = column.formatter

            return { ...column, formatter: (...args) => (isPlaceholder(args[4]) ? '' : format(...args)) }
        })
    }

    function formatToggle(item) {
        if (!rows.get(item.__key)?.child) {
            return ''
        }

        const isExpanded = isOpen(item.__key)
        const label = isExpanded ? translations.entry.collapse : translations.entry.expand

        return `<button type="button" class="fi-data-grid-row-toggle ${isExpanded ? 'fi-data-grid-row-toggle-expanded' : ''}" aria-expanded="${isExpanded ? 'true' : 'false'}" aria-label="${escapeHtml(label)}" title="${escapeHtml(label)}">${icons.expand ?? ''}</button>`
    }

    const frozenColumnIndex = () => {
        if (frozenColumns() <= 0) {
            return -1
        }

        return frozenColumns() - 1 + (payload.reorderable ? 1 : 0)
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
            EDITABLE_TYPES.includes(type) && !cell.disabled ? 'fi-data-grid-cell-editable' : '',
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
            const isLast = index === dataProvider.getLength() - 1

            if (isPlaceholder(items[index])) {
                return {
                    height: childRows.height(items[index].__childOf),
                    cssClasses: `fi-data-grid-child-placeholder ${isLast ? 'fi-data-grid-row-last' : ''}`,
                    focusable: false,
                    selectable: false,
                    columns: { 0: { colspan: '*', editorClass: null } },
                }
            }

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
                    row?.child ? 'fi-data-grid-parent-row-expandable' : '',
                    isOpen(row?.key) ? 'fi-data-grid-parent-row-expanded' : '',
                    isLast ? 'fi-data-grid-row-last' : '',
                ].join(' '),
                columns,
            }
        },
    }

    const withPlaceholders = (rowItems) =>
        rowItems.flatMap((item) => (isOpen(item.__key) ? [item, { __childOf: item.__key }] : [item]))

    const buildItems = (localChanges) =>
        withPlaceholders(payload.rows.map((row) => {
            const item = { __key: row.key, ...row.state }

            for (const column of payload.columns) {
                const cellKey = `${row.key}.${column.field}`

                if (localChanges?.has(cellKey)) {
                    item[column.field] = localChanges.get(cellKey)
                }
            }

            return item
        }))

    const indexRows = () => {
        rows = new Map(payload.rows.map((row) => [row.key, row]))

        payload.rows.forEach((row) => {
            const errorsAppeared = row.hasChildErrors && (!seen.has(row.key) || !hadChildErrors.get(row.key))

            if (!row.child) {
                expanded.delete(row.key)
            } else if (errorsAppeared || (!seen.has(row.key) && row.expanded)) {
                expanded.add(row.key)
            }

            seen.add(row.key)
            hadChildErrors.set(row.key, Boolean(row.hasChildErrors))
        })

        ;[...seen].filter((key) => !rows.has(key)).forEach((key) => {
            seen.delete(key)
            expanded.delete(key)
            hadChildErrors.delete(key)
        })
    }

    const syncChildren = (force = false) => {
        if (!childRows) {
            return false
        }

        childRows.setPadding(payload.childTablePadding)

        return childRows.sync(hasChildRows() ? payload.rows.filter((row) => isOpen(row.key)) : [], force)
    }

    indexRows()
    items = buildItems()
    syncChildren()

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
        enableVariableRowHeight: hasChildRows(),
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
            items = withPlaceholders(items.filter((item) => !isPlaceholder(item)))
            grid.invalidate()
            onReorder(items.filter((item) => !isPlaceholder(item)).map((item) => item.__key))
        },
    })
    rowMoveManager.usabilityOverride(
        (rowIndex, item) => payload.reorderable && !isPlaceholder(item) && !rows.get(item?.__key)?.isTrailingBlank,
    )

    function positionChildren() {
        childRows?.position((key, layerTop) => {
            const index = items.findIndex((item) => item.__childOf === key)
            const canvas = grid.getCanvasNode()

            return index === -1 || !canvas ? null : canvas.getBoundingClientRect().top - layerTop + grid.getRowTop(index)
        })
    }

    function refreshRowHeights() {
        if (isBusy()) {
            pendingRowHeights = true

            return
        }

        pendingRowHeights = false
        grid.invalidateRowHeights()
        grid.updateRowCount()
        grid.resizeCanvas()
        positionChildren()
    }

    function yieldFocus() {
        if (grid.getActiveCell() && grid.getEditorLock().commitCurrentEdit()) {
            grid.resetActiveCell()
        }
    }

    function toggleChildRow(key) {
        grid.getEditorLock().commitCurrentEdit()

        if (expanded.has(key)) {
            expanded.delete(key)
        } else {
            expanded.add(key)
        }

        frozenLength = null
        items = withPlaceholders(items.filter((item) => !isPlaceholder(item)))
        syncChildren()
        refreshRowHeights()
    }

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
            positionChildren()
        })
    })
    resizeObserver.observe(element)
    element.querySelectorAll('.slick-header').forEach((header) => resizeObserver.observe(header))

    grid.onColumnsResized.subscribe((event, { triggeredByColumn }) => {
        const column = grid.getColumns().find((candidate) => candidate.id === triggeredByColumn)

        if (column?.source) {
            userWidths.set(column.field, column.width)
            column.fixedWidth = column.width
        }

        markHorizontalOverflow()
    })

    grid.onRendered.subscribe(() => {
        destroyDetachedSelects()
        positionChildren()
    })

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
            if (isBusy()) {
                return
            }

            if (pendingRowHeights) {
                frozenLength = null
                refreshRowHeights()

                return
            }

            if (frozenLength === null) {
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

    const startTyping = (rowIndex, cellIndex, character) => {
        const { cell, type } = cellAt(rowIndex, cellIndex)

        if (!['text', 'number'].includes(type) || !cell?.visible || cell.disabled) {
            return false
        }

        grid.editActiveCell()

        const input = grid.getCellEditor()?.input

        if (!input) {
            return false
        }

        input.value = character
        input.setSelectionRange?.(character.length, character.length)

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

    const toggleChildRowAt = (rowIndex, cellIndex, target) => {
        const item = items[rowIndex]
        const column = grid.getColumns()[cellIndex]

        if (!rows.get(item?.__key)?.child) {
            return false
        }

        const onToggle = Boolean(target?.closest?.('.fi-data-grid-row-toggle'))
        const onReadOnlyCell = column?.id === TOGGLE_COLUMN || column?.source?.editor.type === 'entry'

        if (!onToggle && (!onReadOnlyCell || target?.closest?.(INTERACTIVE))) {
            return false
        }

        toggleChildRow(item.__key)

        return true
    }

    grid.onClick.subscribe((event, { row, cell }) => {
        if (isPlaceholder(items[row]) || toggleChildRowAt(row, cell, event.target)) {
            return
        }

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
        let target = active ? active.row + direction : -1

        while (isPlaceholder(items[target])) {
            target += direction
        }

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

        if (
            !editing &&
            !hasModifier &&
            event.key.length === 1 &&
            event.key !== ' ' &&
            (openSelectCell(row, cell, event.key) || startTyping(row, cell, event.key))
        ) {
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
    positionChildren()

    return {
        isEditing: () => isBusy() || (childRows?.isEditing() ?? false),

        childControllers: () => childRows?.controllers() ?? [],

        setPayload(nextPayload, localChanges, force = false) {
            const columnsChanged = JSON.stringify(nextPayload.columns) !== JSON.stringify(payload.columns)
            const structureChanged =
                nextPayload.reorderable !== payload.reorderable ||
                nextPayload.hasActions !== payload.hasActions ||
                nextPayload.frozenColumns !== payload.frozenColumns ||
                Boolean(nextPayload.hasChildRows) !== Boolean(payload.hasChildRows)
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

            const heightsChanged = syncChildren(force)

            if (columnsChanged || structureChanged) {
                grid.getEditorLock().cancelCurrentEdit()
                frozenLength = null
                grid.setOptions({ frozenColumn: frozenColumnIndex(), enableVariableRowHeight: hasChildRows() })
                grid.setColumns(columnDefinitions())
                fitColumns()
                grid.invalidateRowHeights()
                grid.invalidate()
                grid.resizeCanvas()

                return
            }

            const busyIndex = busyItem ? items.findIndex((item) => item.__key === busyItem.__key) : -1
            const busyRowIndexBefore = editor && active ? active.row : busyIndex

            if (busyIndex === -1 || busyIndex !== busyRowIndexBefore) {
                if (editor) {
                    grid.getEditorLock().cancelCurrentEdit()
                }

                frozenLength = null
                pendingRowHeights = false
                grid.invalidateRowHeights()
                grid.invalidate()
                grid.resizeCanvas()

                return
            }

            pendingRowHeights ||= heightsChanged

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
            childRows?.controllers().forEach((controller) => controller.setDarkMode(isDark))
        },

        destroy() {
            childRows?.destroy()
            cancelAnimationFrame(resizeFrame)
            resizeObserver.disconnect()
            selects.forEach(({ select }) => select.destroy())
            selects.clear()
            rowMoveManager.dispose()
            grid.destroy()
        },
    }
}

