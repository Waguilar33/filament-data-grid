import { SlickGrid } from '@slickgrid-universal/common'
import { escapeHtml } from './cells.js'

const TOGGLE_COLUMN = '__toggle'
const ACTIONS_COLUMN = '__actions'
const LINK_COLUMN = '__link'
const LINK_WIDTH = 44
const ROW_HEIGHT = 44
const CHILD_HEADER_HEIGHT = 32
const DEFAULT_CHILD_TABLE_PADDING = 15
const TOGGLE_WIDTH = 44
const TOGGLE_BUTTON_INSET = 4
const TOGGLE_ICON_WIDTH = 20
const UNMEASURED_WIDTH = 160
const CHILD_TABLE_BORDER = 1
const INTRINSIC_WIDTHS = ['max-content', '0px']

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

const sumOf = (numbers) => numbers.reduce((sum, number) => sum + number, 0)

const distribute = (spans, available) => {
    const minSum = sumOf(spans.map((span) => span.min))
    const maxSum = sumOf(spans.map((span) => span.max))

    if (spans.length === 0 || minSum >= available) {
        return spans.map((span) => span.min)
    }

    const widths = spans.map((span) =>
        maxSum <= available
            ? span.max + (maxSum > 0 ? ((available - maxSum) * span.max) / maxSum : (available - maxSum) / spans.length)
            : span.max - ((maxSum - available) * (span.max - span.min)) / (maxSum - minSum),
    )
    const floored = widths.map(Math.floor)

    for (let index = 0, remainder = available - sumOf(floored); remainder > 0; index = (index + 1) % floored.length, remainder--) {
        floored[index]++
    }

    return floored
}

const contentBoxes = (node, range) => {
    if (node.nodeType === Node.TEXT_NODE) {
        range.selectNodeContents(node)

        return [...range.getClientRects()]
    }

    if (node.nodeType !== Node.ELEMENT_NODE || node.classList.contains('fi-sr-only')) {
        return []
    }

    return [node.getBoundingClientRect(), ...[...node.childNodes].flatMap((child) => contentBoxes(child, range))]
}

const outerContentWidth = (cell, range) => {
    const style = getComputedStyle(cell)
    const padding = parseFloat(style.paddingLeft) + parseFloat(style.paddingRight)
    const boxes = [...cell.childNodes].flatMap((child) => contentBoxes(child, range)).filter((box) => box.width > 0)
    const content = boxes.length > 0 ? Math.max(...boxes.map((box) => box.right)) - Math.min(...boxes.map((box) => box.left)) : 0

    return Math.ceil(Math.max(cell.getBoundingClientRect().width, content + padding))
}

const probeRequest = (wrapper, classes, content) => ({ key: `${wrapper}|${classes}|${content}`, wrapper, classes, content })

const cellContent = (cell) => (cell && cell.visible !== false ? (cell.html ?? '') : '')

const childCellClasses = (column) => `fi-data-grid-child-cell fi-align-${column.alignment ?? 'start'}`

const childCellHtml = (cell, column, isHeader) => {
    const classes = childCellClasses(column)

    if (isHeader) {
        return `<div class="${classes}" role="columnheader">${headerLabel(column)}</div>`
    }

    return `<div class="${classes}" role="cell">${cellContent(cell)}</div>`
}

export function createEntryGrid(element, { payload, translations, icons, isDarkMode, onChange, childTables = null }) {
    let rows = []
    let visible = []
    let search = ''
    let page = payload.page ?? 1
    let recordsPerPage = payload.recordsPerPage ?? null
    let filters = {}
    const expanded = new Set()
    const measuredHeights = new Map()
    let measuredWidths = null
    let columnWidths = []
    const intrinsicWidths = new Map()
    const childTemplates = new Map()

    const hasNativeChildTable = () => Boolean(payload.childTable && childTables)

    const hasChildTable = () => !hasNativeChildTable() && payload.childColumns.length > 0

    const canExpand = (row) => hasNativeChildTable() || hasChildTable() || Boolean(row.above) || Boolean(row.below)

    const hasChildren = () => payload.rows.some(canExpand)

    const perPage = () => (typeof recordsPerPage === 'number' ? recordsPerPage : null)

    const pageCountFor = (count) => (perPage() ? Math.max(1, Math.ceil(count / perPage())) : 1)

    const matchesFilters = (row) =>
        (payload.filters ?? []).every((filter) => {
            const active = filters[filter.name]
            const value = row.filterValues?.[filter.name]

            if (active === undefined || active === null || active === '' || (Array.isArray(active) && active.length === 0)) {
                return true
            }

            if (filter.type === 'ternary') {
                return Boolean(value) === (active === true || active === 'true')
            }

            if (Array.isArray(active)) {
                return active.map(String).includes(String(value))
            }

            return String(value) === String(active)
        })

    const currentPage = () => {
        if (payload.serverSide) {
            return { pageRows: rows, total: payload.total ?? rows.length }
        }

        const needle = search.toLowerCase()
        const matching = rows.filter((row) => (needle === '' || row.search.includes(needle)) && matchesFilters(row))

        page = Math.min(Math.max(1, page), pageCountFor(matching.length))

        const start = perPage() ? (page - 1) * perPage() : 0

        return {
            pageRows: perPage() ? matching.slice(start, start + perPage()) : matching,
            total: matching.length,
        }
    }

    const computeVisible = () => {
        const { pageRows, total } = currentPage()
        const start = perPage() ? (page - 1) * perPage() : 0

        visible = []

        pageRows.forEach((row) => {
            visible.push({ kind: 'parent', row })

            if (!expanded.has(row.key) || !canExpand(row)) {
                return
            }

            const group = []

            if (row.above) {
                group.push({ kind: 'child-slot', slot: 'above', row })
            }

            if (hasNativeChildTable()) {
                group.push({ kind: 'child-table', row, tableStart: true, tableEnd: true })
            } else if (hasChildTable()) {
                if (row.children.length === 0) {
                    group.push({ kind: 'child-empty', row, tableStart: true, tableEnd: true })
                } else {
                    group.push({ kind: 'child-header', row, tableStart: true })

                    row.children.forEach((cells, childIndex) =>
                        group.push({ kind: 'child', row, cells, childIndex, tableEnd: childIndex === row.children.length - 1 }),
                    )
                }
            }

            if (row.below) {
                group.push({ kind: 'child-slot', slot: 'below', row })
            }

            group.forEach((item, index) => {
                item.isFirst = index === 0
                item.isLast = index === group.length - 1
                visible.push(item)
            })
        })

        layoutColumns()
        measureRows()

        onChange?.({
            page,
            pageCount: pageCountFor(total),
            recordsPerPage,
            total,
            first: total === 0 ? 0 : start + 1,
            last: start + pageRows.length,
        })
    }

    const formatToggle = (rowIndex, cellIndex, value, column, item) => {
        if (item.kind !== 'parent' || !canExpand(item.row)) {
            return ''
        }

        const isExpanded = expanded.has(item.row.key)
        const label = isExpanded ? translations.collapse : translations.expand

        return `<button type="button" class="fi-data-grid-row-toggle ${isExpanded ? 'fi-data-grid-row-toggle-expanded' : ''}" aria-expanded="${isExpanded ? 'true' : 'false'}" aria-label="${escapeHtml(label)}" title="${escapeHtml(label)}">${icons.expand}</button>`
    }

    const formatChildRow = (item) => {
        const style = `grid-template-columns: ${childTemplates.get(item.row.key) ?? childGridTemplate(payload.childColumns)}`
        const edges = [
            item.isFirst ? 'fi-data-grid-pad-top' : '',
            item.isLast ? 'fi-data-grid-pad-bottom' : '',
            item.tableStart ? 'fi-data-grid-child-first' : '',
            item.tableEnd ? 'fi-data-grid-child-last' : '',
        ].join(' ')

        if (item.kind === 'child-slot') {
            return `<div class="fi-data-grid-child-slot fi-data-grid-child-slot-${item.slot} ${edges}">${item.slot === 'above' ? item.row.above : item.row.below}</div>`
        }

        if (item.kind === 'child-table') {
            return `<div class="fi-data-grid-child-table-placeholder ${edges}"></div>`
        }

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
            width: toPixels(column.width) ?? UNMEASURED_WIDTH,
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

        if (payload.rows.some((row) => row.actions)) {
            columns.push({
                id: ACTIONS_COLUMN,
                field: ACTIONS_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.actions)}</span>`,
                fixedWidth: UNMEASURED_WIDTH,
                width: UNMEASURED_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-record-actions-cell fi-align-end',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) => (item.kind === 'parent' ? (item.row.actions ?? '') : ''),
            })
        }

        if (payload.rows.some((row) => row.link)) {
            columns.push({
                id: LINK_COLUMN,
                field: LINK_COLUMN,
                name: `<span class="fi-sr-only">${escapeHtml(translations.open_record)}</span>`,
                fixedWidth: LINK_WIDTH,
                width: LINK_WIDTH,
                resizable: false,
                sortable: false,
                focusable: false,
                selectable: false,
                cssClass: 'fi-data-grid-cell fi-data-grid-record-link-cell',
                headerCssClass: 'fi-data-grid-header',
                formatter: (rowIndex, cellIndex, value, column, item) => (item.kind === 'parent' ? (item.row.link ?? '') : ''),
            })
        }

        insetEdgeColumns(columns)

        return columns
    }

    const edgeInset = () => {
        const probe = document.createElement('div')
        probe.style.paddingInlineStart = 'var(--fi-data-grid-edge-inset, 0px)'
        element.appendChild(probe)
        const inset = parseFloat(getComputedStyle(probe).paddingInlineStart) || 0
        probe.remove()

        return inset
    }

    const insetEdgeColumns = (columns) => {
        const [first] = columns
        const last = columns.at(-1)

        if (first.id === TOGGLE_COLUMN) {
            first.cssClass += ' fi-data-grid-toggle-cell-inset'
        } else {
            first.cssClass += ' fi-data-grid-inset-start'
            first.headerCssClass += ' fi-data-grid-inset-start'
        }

        if (last.id === LINK_COLUMN) {
            last.cssClass += ' fi-data-grid-record-link-cell-inset'
        } else if (last.id !== TOGGLE_COLUMN) {
            last.cssClass += ' fi-data-grid-inset-end'
            last.headerCssClass += ' fi-data-grid-inset-end'
        }
    }

    const childTablePadding = () => payload.childTablePadding ?? DEFAULT_CHILD_TABLE_PADDING

    const measureIntrinsicWidths = (requests) => {
        const pending = [...new Map(requests.filter((request) => !intrinsicWidths.has(request.key)).map((request) => [request.key, request])).values()]

        if (pending.length === 0) {
            return
        }

        const probe = document.createElement('div')
        probe.className = 'fi-data-grid-width-probe'
        probe.innerHTML = pending
            .map(
                (request) =>
                    `<div class="${request.wrapper}">${INTRINSIC_WIDTHS.map((width) => `<div class="${request.classes}" style="width: ${width}">${request.content}</div>`).join('')}</div>`,
            )
            .join('')
        element.appendChild(probe)

        const range = document.createRange()
        const sizes = [...probe.children].map((wrapper) => [...wrapper.children].map((cell) => outerContentWidth(cell, range)))

        probe.remove()
        pending.forEach((request, index) => {
            const [max, min] = sizes[index]

            intrinsicWidths.set(request.key, { min: Math.min(min, max), max })
        })
    }

    const spanOf = (requests) =>
        requests.reduce(
            (span, request) => {
                const size = intrinsicWidths.get(request.key)

                return { min: Math.max(span.min, size.min), max: Math.max(span.max, size.max) }
            },
            { min: 0, max: 0 },
        )

    const parentRequests = (column, parents) => [
        probeRequest('', `slick-header-column ${column.headerCssClass}`, column.name),
        ...parents.map((item) => probeRequest('', `slick-cell ${column.cssClass} fi-data-grid-entry`, cellContent(item.row.cells[column.field]))),
    ]

    const actionsRequests = () =>
        payload.rows
            .filter((row) => row.actions)
            .map((row) => probeRequest('', 'slick-cell fi-data-grid-cell fi-data-grid-record-actions-cell fi-align-end', row.actions))

    const childRequests = (row, column, index) => [
        probeRequest('fi-data-grid-child-header', childCellClasses(column), headerLabel(column)),
        ...row.children.map((cells) => probeRequest('', childCellClasses(column), cellContent(cells[index]))),
    ]

    const flexibleChildColumns = () => payload.childColumns.flatMap((column, index) => (column.width ? [] : [{ column, index }]))

    const explicitChildWidths = (rowWidth) => {
        const unresolved = payload.childColumns.filter((column) => column.width && toPixels(column.width) === null)
        const resolved = []

        if (unresolved.length > 0) {
            const probe = document.createElement('div')
            probe.className = 'fi-data-grid-width-probe'
            probe.style.width = `${rowWidth}px`
            probe.innerHTML = unresolved.map((column) => `<div style="width: ${column.width}"></div>`).join('')
            element.appendChild(probe)
            resolved.push(...[...probe.children].map((child) => Math.round(child.getBoundingClientRect().width)))
            probe.remove()
        }

        return payload.childColumns.map((column) => (column.width ? (toPixels(column.width) ?? resolved.shift()) : null))
    }

    const childTemplate = (spans, available, explicitWidths) => {
        const widths = distribute(spans, available)
        let next = 0

        return explicitWidths.map((width) => `${width ?? widths[next++]}px`).join(' ')
    }

    const layoutColumns = () => {
        if (element.clientWidth === 0) {
            return false
        }

        const columns = currentColumns()
        const parents = visible.filter((item) => item.kind === 'parent')

        const inset = edgeInset()

        columns
            .filter((column) => column.id === TOGGLE_COLUMN || column.id === LINK_COLUMN)
            .forEach((column) => {
                column.fixedWidth = inset > 0 ? inset + TOGGLE_ICON_WIDTH + TOGGLE_BUTTON_INSET : (column.id === TOGGLE_COLUMN ? TOGGLE_WIDTH : LINK_WIDTH)
            })

        const openRows = hasChildTable() ? parents.map((item) => item.row).filter((row) => expanded.has(row.key) && row.children.length > 0) : []
        const flexibleColumns = columns.filter((column) => column.fixedWidth === null)
        const childColumns = flexibleChildColumns()
        const parentRequestsByColumn = flexibleColumns.map((column) => parentRequests(column, parents))
        const childRequestsByRow = openRows.map((row) => childColumns.map(({ column, index }) => childRequests(row, column, index)))
        const actions = actionsRequests()

        measureIntrinsicWidths([...parentRequestsByColumn.flat(), ...childRequestsByRow.flat(2), ...actions])

        columns
            .filter((column) => column.id === ACTIONS_COLUMN)
            .forEach((column) => {
                column.fixedWidth = spanOf(actions).max
            })

        const fixedWidth = sumOf(columns.map((column) => column.fixedWidth ?? 0))
        const available = Math.floor(element.getBoundingClientRect().width) - fixedWidth
        const childInset = (childTablePadding() + CHILD_TABLE_BORDER) * 2
        const fixedChildWidth = (rowWidth) => sumOf(explicitChildWidths(rowWidth).map((width) => width ?? 0))
        const childSpans = childRequestsByRow.map((requestsByColumn) => requestsByColumn.map(spanOf))
        const provisionalChildWidth = openRows.length > 0 ? fixedChildWidth(available + fixedWidth - childInset) : 0
        const childNeed = Math.max(0, ...childSpans.map((spans) => childInset + provisionalChildWidth + sumOf(spans.map((span) => span.min))))
        const flexibleWidths = distribute(parentRequestsByColumn.map(spanOf), Math.max(available, childNeed - fixedWidth))
        let next = 0
        const widths = columns.map((column) => column.fixedWidth ?? flexibleWidths[next++])
        const changed = columns.some((column, index) => column.width !== widths[index])

        childTemplates.clear()

        if (openRows.length > 0) {
            const childRowWidth = sumOf(widths) - childInset
            const explicitWidths = explicitChildWidths(childRowWidth)
            const childAvailable = childRowWidth - sumOf(explicitWidths.map((width) => width ?? 0))

            openRows.forEach((row, index) => childTemplates.set(row.key, childTemplate(childSpans[index], childAvailable, explicitWidths)))
        }

        columnWidths = widths
        columns.forEach((column, index) => {
            column.width = widths[index]
        })

        if (changed && grid) {
            grid.setColumns(columns)
        }

        return changed
    }

    const measureKey = (item) =>
        item.kind === 'parent'
            ? `parent:${item.row.key}`
            : item.kind === 'child-slot'
              ? `slot:${item.row.key}:${item.slot}`
              : `child:${item.row.key}:${item.childIndex}`

    const needsMeasuring = (item) =>
        (item.kind === 'child' || item.kind === 'child-slot' || (item.kind === 'parent' && typeof item.row.height !== 'number')) &&
        !measuredHeights.has(measureKey(item))

    const parentProbeHtml = (item, columns) => {
        const cells = columns
            .map((column, index) => {
                const cell = item.row.cells[column.field]
                const html = cell && cell.visible !== false ? (cell.html ?? '') : ''

                return `<div class="slick-cell ${column.cssClass} fi-data-grid-entry" style="width: ${columnWidths[index]}px">${html}</div>`
            })
            .join('')

        return `<div class="slick-row fi-data-grid-parent-row">${cells}</div>`
    }

    const childProbeHtml = (item, columns) => {
        const width = columnWidths.reduce((sum, columnWidth) => sum + columnWidth, 0)

        return `<div class="slick-row fi-data-grid-child-row-wrapper"><div class="slick-cell ${columns[0].cssClass}" style="width: ${width}px">${formatChildRow(item)}</div></div>`
    }

    const measureRows = () => {
        const signature = columnWidths.join(',')

        if (signature !== measuredWidths) {
            measuredHeights.clear()
        }

        if (element.clientWidth === 0) {
            measuredWidths = null

            return
        }

        measuredWidths = signature
        const pending = visible.filter(needsMeasuring)

        if (pending.length === 0) {
            return
        }

        const columns = currentColumns()
        const probe = document.createElement('div')
        probe.className = 'fi-data-grid-row-probe'
        probe.innerHTML = pending.map((item) => (item.kind === 'parent' ? parentProbeHtml(item, columns) : childProbeHtml(item, columns))).join('')
        element.appendChild(probe)

        const heights = [...probe.children].map((row) =>
            (row.querySelector('.fi-data-grid-child-row, .fi-data-grid-child-slot') ?? row).getBoundingClientRect().height,
        )

        probe.remove()
        pending.forEach((item, index) =>
            measuredHeights.set(measureKey(item), Math.max(item.kind === 'child-slot' ? 0 : ROW_HEIGHT, Math.ceil(heights[index]))),
        )
    }

    const parentRowHeight = (item) => item.row.height ?? measuredHeights.get(measureKey(item)) ?? ROW_HEIGHT

    const contentHeight = (item) => {
        if (item.kind === 'child-header') {
            return CHILD_HEADER_HEIGHT
        }

        if (item.kind === 'child-table') {
            return childTables.height(item.row.key)
        }

        return measuredHeights.get(measureKey(item)) ?? (item.kind === 'child-slot' ? 0 : ROW_HEIGHT)
    }

    const childRowHeight = (item) =>
        contentHeight(item) +
        (item.isFirst ? childTablePadding() : 0) +
        (item.isLast ? childTablePadding() : 0)

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
                    height: parentRowHeight(item),
                    cssClasses: [
                        'fi-data-grid-parent-row',
                        canExpand(item.row) ? 'fi-data-grid-parent-row-expandable' : '',
                        expanded.has(item.row.key) ? 'fi-data-grid-parent-row-expanded' : '',
                        isLast ? 'fi-data-grid-row-last' : '',
                    ].join(' '),
                }
            }

            return {
                height: childRowHeight(item),
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

    const initialColumns = columnDefinitions()
    let grid = null
    const currentColumns = () => grid?.getColumns() ?? initialColumns

    columnWidths = initialColumns.map((column) => column.width)

    indexRows()
    computeVisible()

    grid = new SlickGrid(element, dataProvider, initialColumns, {
        editable: false,
        enableCellNavigation: false,
        enableColumnReorder: false,
        autoHeight: true,
        rowHeight: ROW_HEIGHT,
        enableVariableRowHeight: true,
        showHeaderRow: false,
        showTopPanel: false,
        showPreHeaderPanel: false,
        showFooterRow: false,
        enableTextSelectionOnCells: true,
        darkMode: isDarkMode,
        devMode: false,
    })

    element.classList.add('fi-data-grid-slick')

    const applyChildTablePadding = () => element.style.setProperty('--fi-data-grid-child-inset', `${childTablePadding()}px`)

    applyChildTablePadding()

    const openChildTables = () => visible.filter((item) => item.kind === 'child-table')

    const positionChildTables = () => {
        if (!hasNativeChildTable() || !grid) {
            return
        }

        const items = openChildTables()

        childTables.position((key, layerTop) => {
            const item = items.find((candidate) => candidate.row.key === key)
            const canvas = grid.getCanvasNode()

            if (!item || !canvas) {
                return null
            }

            return canvas.getBoundingClientRect().top - layerTop + grid.getRowTop(visible.indexOf(item)) + (item.isFirst ? childTablePadding() : 0)
        })
    }

    const syncChildTables = () => {
        if (hasNativeChildTable()) {
            childTables.sync(
                openChildTables().map((item) => item.row.key),
                childTablePadding(),
            )
        }
    }

    const redraw = () => {
        computeVisible()
        syncChildTables()
        grid.invalidateRowHeights()
        grid.updateRowCount()
        grid.resizeCanvas()
        positionChildTables()
    }

    grid.onRendered.subscribe(positionChildTables)

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

        if (item?.kind !== 'parent') {
            return
        }

        const target = event.getNativeEvent?.()?.target ?? event.target
        const isToggle = Boolean(target?.closest?.('.fi-data-grid-row-toggle'))

        if (!isToggle && target?.closest?.('a, button, input, select, textarea, .fi-data-grid-record-actions')) {
            return
        }

        if (!isToggle && item.row.link) {
            grid.getCellNode(row, grid.getColumnIndex(LINK_COLUMN))?.querySelector('a')?.click()

            return
        }

        if (canExpand(item.row)) {
            toggle(item.row.key)
        }
    })

    const fitColumns = () => {
        if (layoutColumns() || measuredWidths !== columnWidths.join(',')) {
            redraw()
        }
    }

    let resizeFrame = null

    const resizeObserver = new ResizeObserver(() => {
        cancelAnimationFrame(resizeFrame)
        resizeFrame = requestAnimationFrame(fitColumns)
    })

    resizeObserver.observe(element)
    fitColumns()

    const remeasure = () => {
        intrinsicWidths.clear()
        measuredHeights.clear()
        redraw()
    }

    document.fonts?.addEventListener?.('loadingdone', remeasure)

    return {
        getQuery() {
            return { search, page, recordsPerPage, filters }
        },

        setQuery(changes) {
            if ('search' in changes && changes.search !== search) {
                search = String(changes.search ?? '').trim()
                page = 1

                if (payload.serverSide) {
                    expanded.clear()
                }
            }

            if ('filters' in changes) {
                filters = { ...changes.filters }
                page = 1

                if (payload.serverSide) {
                    expanded.clear()
                }
            }

            if ('recordsPerPage' in changes) {
                recordsPerPage = changes.recordsPerPage
                page = 1
            }

            if ('page' in changes) {
                page = changes.page
            }

            if (!payload.serverSide) {
                redraw()
            }

            return { search, page, recordsPerPage, filters }
        },

        setServerPage(result) {
            payload = { ...payload, rows: result.rows, total: result.total }
            page = result.page
            recordsPerPage = result.recordsPerPage
            measuredHeights.clear()
            intrinsicWidths.clear()
            indexRows()
            redraw()
        },

        toggle,

        redraw,

        setPayload(nextPayload) {
            const hadToggles = hasChildren()
            const columnsChanged =
                JSON.stringify(nextPayload.columns) !== JSON.stringify(payload.columns) ||
                JSON.stringify(nextPayload.childColumns) !== JSON.stringify(payload.childColumns) ||
                hadToggles !== nextPayload.rows.some((row) => Boolean(nextPayload.childTable) || nextPayload.childColumns.length > 0 || Boolean(row.above) || Boolean(row.below))

            payload = nextPayload
            measuredHeights.clear()
            intrinsicWidths.clear()
            indexRows()
            applyChildTablePadding()

            if (columnsChanged) {
                grid.setColumns(columnDefinitions())
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
            document.fonts?.removeEventListener?.('loadingdone', remeasure)
            grid.destroy()
            grid = null
        },
    }
}
