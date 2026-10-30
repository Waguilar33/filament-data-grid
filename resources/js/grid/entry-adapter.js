import { SlickGrid } from '@slickgrid-universal/common'
import { escapeHtml } from './cells.js'

const TOGGLE_COLUMN = '__toggle'
const ROW_HEIGHT = 44
const TOGGLE_WIDTH = 44
const FLEXIBLE_MIN_WIDTH = 160

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

const headerLabel = (column) =>
    column.headerHidden
        ? `<span class="fi-sr-only">${escapeHtml(column.label)}</span>`
        : `<span class="slick-column-name">${escapeHtml(column.label)}</span>`

const childGridTemplate = (columns) =>
    columns.map((column) => (column.width ? column.width : 'minmax(0, 1fr)')).join(' ')

const childCellHtml = (cell, column, isHeader) => {
    const classes = `fi-data-grid-child-cell fi-align-${column.alignment ?? 'start'}`

    if (isHeader) {
        return `<div class="${classes}" role="columnheader">${headerLabel(column)}</div>`
    }

    return `<div class="${classes}" role="cell">${cell?.visible === false ? '' : (cell?.html ?? '')}</div>`
}

export function createEntryGrid(element, { payload, translations, icons, isDarkMode, onChange }) {
    let rows = []
    let visible = []
    let query = ''
    let page = 1
    const expanded = new Set()

    const hasChildren = () => payload.childColumns.length > 0

    const matches = () => (query === '' ? rows : rows.filter((row) => row.search.includes(query)))

    const pageCountFor = (count) =>
        payload.recordsPerPage ? Math.max(1, Math.ceil(count / payload.recordsPerPage)) : 1

    const computeVisible = () => {
        const matching = matches()
        const pageCount = pageCountFor(matching.length)

        page = Math.min(Math.max(1, page), pageCount)

        const start = payload.recordsPerPage ? (page - 1) * payload.recordsPerPage : 0
        const pageRows = payload.recordsPerPage ? matching.slice(start, start + payload.recordsPerPage) : matching

        visible = []

        pageRows.forEach((row, index) => {
            visible.push({ kind: 'parent', row, isLastParent: index === pageRows.length - 1 })

            if (!expanded.has(row.key)) {
                return
            }

            if (row.children.length === 0) {
                visible.push({ kind: 'child-empty', row, isFirst: true, isLast: true })

                return
            }

            visible.push({ kind: 'child-header', row, isFirst: true, isLast: false })

            row.children.forEach((cells, childIndex) =>
                visible.push({ kind: 'child', row, cells, isFirst: false, isLast: childIndex === row.children.length - 1 }),
            )
        })

        onChange?.({
            page,
            pageCount,
            total: matching.length,
            first: matching.length === 0 ? 0 : start + 1,
            last: start + pageRows.length,
        })
    }

    const formatToggle = (rowIndex, cellIndex, value, column, item) => {
        if (item.kind !== 'parent') {
            return ''
        }

        const isExpanded = expanded.has(item.row.key)
        const label = isExpanded ? translations.collapse : translations.expand

        return `<button type="button" class="fi-data-grid-row-toggle ${isExpanded ? 'fi-data-grid-row-toggle-expanded' : ''}" aria-expanded="${isExpanded ? 'true' : 'false'}" aria-label="${escapeHtml(label)}" title="${escapeHtml(label)}">${icons.expand}</button>`
    }

    const formatChildRow = (item) => {
        const style = `grid-template-columns: ${childGridTemplate(payload.childColumns)}`
        const edges = `${item.isFirst ? 'fi-data-grid-child-first' : ''} ${item.isLast ? 'fi-data-grid-child-last' : ''}`

        if (item.kind === 'child-empty') {
            return `<div class="fi-data-grid-child-row fi-data-grid-child-empty ${edges}">${escapeHtml(translations.no_children)}</div>`
        }

        const isHeader = item.kind === 'child-header'
        const cells = payload.childColumns.map((column, index) => childCellHtml(item.cells?.[index], column, isHeader)).join('')

        return `<div class="fi-data-grid-child-row ${isHeader ? 'fi-data-grid-child-header' : ''} ${edges}" role="row" style="${style}">${cells}</div>`
    }

    const formatCell = (rowIndex, cellIndex, value, column, item) => {
        if (item.kind !== 'parent') {
            return cellIndex === 0 ? formatChildRow(item) : ''
        }

        const cell = item.row.cells[column.field]

        if (!cell || cell.visible === false) {
            return ''
        }

        return { html: cell.html ?? '', addClasses: 'fi-data-grid-entry' }
    }

    const columnDefinitions = () => {
        const columns = payload.columns.map((column) => ({
            id: column.field,
            field: column.field,
            name: headerLabel(column),
            fixedWidth: toPixels(column.width),
            width: toPixels(column.width) ?? FLEXIBLE_MIN_WIDTH,
            resizable: false,
            sortable: false,
            focusable: false,
            selectable: false,
            cssClass: `fi-data-grid-cell fi-align-${column.alignment ?? 'start'}`,
            headerCssClass: `fi-data-grid-header fi-align-${column.alignment ?? 'start'}`,
            formatter: formatCell,
        }))

        if (hasChildren()) {
            columns.unshift({
                id: TOGGLE_COLUMN,
                field: TOGGLE_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.expand)}</span>`,
                fixedWidth: TOGGLE_WIDTH,
                width: TOGGLE_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-toggle-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) =>
                    item.kind === 'parent' ? formatToggle(rowIndex, cellIndex, value, column, item) : formatChildRow(item),
            })
        }

        return columns
    }

    const dataProvider = {
        getLength: () => visible.length,
        getItem: (index) => visible[index],
        getItemMetadata: (index) => {
            const item = visible[index]

            if (!item) {
                return null
            }

            const isLast = index === visible.length - 1

            if (item.kind === 'parent') {
                return {
                    cssClasses: [
                        'fi-data-grid-parent-row',
                        hasChildren() ? 'fi-data-grid-parent-row-expandable' : '',
                        expanded.has(item.row.key) ? 'fi-data-grid-parent-row-expanded' : '',
                        isLast ? 'fi-data-grid-row-last' : '',
                    ].join(' '),
                }
            }

            return {
                cssClasses: `fi-data-grid-child-row-wrapper ${isLast ? 'fi-data-grid-row-last' : ''}`,
                focusable: false,
                selectable: false,
                columns: { 0: { colspan: '*' } },
            }
        },
    }

    const indexRows = () => {
        rows = payload.rows
        const keys = new Set(rows.map((row) => row.key))

        expanded.forEach((key) => {
            if (!keys.has(key)) {
                expanded.delete(key)
            }
        })
    }

    indexRows()
    computeVisible()

    const grid = new SlickGrid(element, dataProvider, columnDefinitions(), {
        editable: false,
        enableCellNavigation: false,
        enableColumnReorder: false,
        autoHeight: true,
        rowHeight: ROW_HEIGHT,
        showHeaderRow: false,
        showTopPanel: false,
        showPreHeaderPanel: false,
        showFooterRow: false,
        enableTextSelectionOnCells: true,
        darkMode: isDarkMode,
        devMode: false,
    })

    element.classList.add('fi-data-grid-slick')

    const redraw = () => {
        computeVisible()
        grid.invalidate()
        grid.updateRowCount()
        grid.resizeCanvas()
    }

    const toggle = (key) => {
        if (expanded.has(key)) {
            expanded.delete(key)
        } else {
            expanded.add(key)
        }

        redraw()
    }

    grid.onClick.subscribe((event, { row }) => {
        const item = visible[row]

        if (item?.kind !== 'parent' || !hasChildren()) {
            return
        }

        const target = event.getNativeEvent?.()?.target ?? event.target

        if (target?.closest?.('a, button:not(.fi-data-grid-row-toggle), input, select, textarea')) {
            return
        }

        toggle(item.row.key)
    })

    const fitColumns = () => {
        const columns = grid.getColumns()
        const fixed = columns.reduce((sum, column) => sum + (column.fixedWidth ?? 0), 0)
        const flexible = columns.filter((column) => !column.fixedWidth)
        const flexibleWidth = Math.max(
            FLEXIBLE_MIN_WIDTH,
            Math.floor((element.clientWidth - fixed) / Math.max(flexible.length, 1)),
        )

        if (columns.every((column) => column.width === (column.fixedWidth ?? flexibleWidth))) {
            return
        }

        columns.forEach((column) => {
            column.width = column.fixedWidth ?? flexibleWidth
        })

        grid.setColumns(columns)
        grid.resizeCanvas()
    }

    let resizeFrame = null

    const resizeObserver = new ResizeObserver(() => {
        cancelAnimationFrame(resizeFrame)
        resizeFrame = requestAnimationFrame(fitColumns)
    })

    resizeObserver.observe(element)
    fitColumns()

    return {
        search(value) {
            query = String(value ?? '').trim().toLowerCase()
            page = 1
            redraw()
        },

        goToPage(number) {
            page = number
            redraw()
        },

        toggle,

        setPayload(nextPayload) {
            const columnsChanged =
                JSON.stringify(nextPayload.columns) !== JSON.stringify(payload.columns) ||
                JSON.stringify(nextPayload.childColumns) !== JSON.stringify(payload.childColumns)

            payload = nextPayload
            indexRows()

            if (columnsChanged) {
                grid.setColumns(columnDefinitions())
                fitColumns()
            }

            redraw()
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
