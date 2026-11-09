export const toPixels = (width) => {
    if (!width) {
        return null
    }

    const number = parseFloat(width)

    if (String(width).endsWith('rem')) {
        return number * parseFloat(getComputedStyle(document.documentElement).fontSize)
    }

    return String(width).endsWith('px') || /^\d+$/.test(String(width)) ? number : null
}

export const sumOf = (numbers) => numbers.reduce((sum, number) => sum + number, 0)

export const distribute = (spans, available) => {
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

const horizontalPadding = (element) => {
    const style = getComputedStyle(element)

    return parseFloat(style.paddingLeft) + parseFloat(style.paddingRight)
}

const outerContentWidth = (cell, range) => {
    const text = cell.querySelector(':scope > .fi-ta-col > .fi-ta-text')
    const padding = horizontalPadding(cell) + (text ? horizontalPadding(text) : 0)
    const boxes = [...cell.childNodes].flatMap((child) => contentBoxes(child, range)).filter((box) => box.width > 0)
    const content = boxes.length > 0 ? Math.max(...boxes.map((box) => box.right)) - Math.min(...boxes.map((box) => box.left)) : 0

    return Math.ceil(Math.max(cell.getBoundingClientRect().width, content + padding))
}

export const probeRequest = (wrapper, classes, content, edge = '') => ({ key: `${wrapper}|${classes}|${edge}|${content}`, wrapper, classes, content, edge })

export function createWidthProbe(element, { wraps = true } = {}) {
    const probeWidths = wraps ? ['max-content', '0px'] : ['max-content']
    const sizes = new Map()

    const measure = (requests) => {
        const pending = [...new Map(requests.filter((request) => !sizes.has(request.key)).map((request) => [request.key, request])).values()]

        if (pending.length === 0) {
            return
        }

        const probe = document.createElement('div')
        probe.className = 'fi-data-grid-width-probe'
        probe.innerHTML = pending
            .flatMap((request) =>
                probeWidths.map(
                    (width) =>
                        `<div class="${request.wrapper}">${request.edge.includes('start') ? '' : '<div hidden></div>'}<div class="${request.classes}" style="width: ${width}">${request.content}</div>${request.edge.includes('end') ? '' : '<div hidden></div>'}</div>`,
                ),
            )
            .join('')
        element.appendChild(probe)

        const range = document.createRange()
        const widths = [...probe.children].map((wrapper) => outerContentWidth(wrapper.querySelector(':scope > :not([hidden])'), range))

        probe.remove()
        pending.forEach((request, index) => {
            const [max, min = max] = widths.slice(index * probeWidths.length, (index + 1) * probeWidths.length)

            sizes.set(request.key, { min: Math.min(min, max), max })
        })
    }

    const spanOf = (requests) =>
        requests.reduce(
            (span, request) => {
                const size = sizes.get(request.key)

                return size ? { min: Math.max(span.min, size.min), max: Math.max(span.max, size.max) } : span
            },
            { min: 0, max: 0 },
        )

    return { measure, spanOf, clear: () => sizes.clear(), size: () => sizes.size }
}
