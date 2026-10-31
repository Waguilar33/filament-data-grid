const DEFAULT_PADDING = 15
const DEFAULT_HEIGHT = 88

export function createChildRows(element, { createChild, onResize, onFocus }) {
    const layer = document.createElement('div')
    layer.className = 'fi-data-grid-child-layer'
    layer.addEventListener('focusin', onFocus)
    element.after(layer)

    const children = new Map()
    let padding = DEFAULT_PADDING
    let frame = null

    const measure = () => {
        let changed = false

        children.forEach((child) => {
            const height = child.host.offsetHeight

            if (height > 0 && height !== child.height) {
                child.height = height
                changed = true
            }
        })

        return changed
    }

    const resizeObserver = new ResizeObserver(() => {
        cancelAnimationFrame(frame)
        frame = requestAnimationFrame(() => {
            if (measure()) {
                onResize()
            }
        })
    })

    const remove = (key) => {
        const child = children.get(key)

        resizeObserver.unobserve(child.host)
        child.controller?.destroy()
        child.host.remove()
        children.delete(key)
    }

    const identityOf = (row) => row.child?.config.statePath ?? null

    const renderedSlots = new WeakMap()

    const syncSlot = (element, html) => {
        if (renderedSlots.get(element) !== (html ?? '')) {
            renderedSlots.set(element, html ?? '')
            element.innerHTML = html ?? ''
        }

        element.hidden = !html
    }

    const createSlot = (host, slot, html) => {
        const element = document.createElement('div')
        element.className = `fi-data-grid-child-host-slot fi-data-grid-child-host-slot-${slot}`
        syncSlot(element, html)
        host.append(element)

        return element
    }

    const create = (key, row) => {
        const host = document.createElement('div')
        const above = createSlot(host, 'above', row.above)
        let controller = null

        host.className = 'fi-data-grid-child-host'
        host.style.insetInline = `${padding}px`

        if (row.child) {
            const canvas = document.createElement('div')
            canvas.className = 'fi-data-grid-canvas'
            host.append(canvas)
            controller = createChild(canvas, row.child)
        }

        const below = createSlot(host, 'below', row.below)
        layer.append(host)

        children.set(key, {
            host,
            above,
            below,
            identity: identityOf(row),
            payload: row.child ? JSON.stringify(row.child.payload) : null,
            controller,
            height: null,
        })

        resizeObserver.observe(host)
    }

    return {
        height: (key) => (children.get(key)?.height ?? DEFAULT_HEIGHT) + padding * 2,

        setPadding(value) {
            padding = value ?? DEFAULT_PADDING
            layer.style.setProperty('--fi-data-grid-child-inset', `${padding}px`)
            children.forEach(({ host }) => {
                host.style.insetInline = `${padding}px`
            })
        },

        sync(openRows, force = false) {
            const open = new Map(openRows.map((row) => [row.key, row]))

            ;[...children.keys()].forEach((key) => {
                if (!open.has(key) || children.get(key).identity !== identityOf(open.get(key))) {
                    remove(key)
                }
            })

            open.forEach((row, key) => {
                const child = children.get(key)

                if (!child) {
                    create(key, row)

                    return
                }

                syncSlot(child.above, row.above)
                syncSlot(child.below, row.below)

                if (!row.child) {
                    return
                }

                const payload = JSON.stringify(row.child.payload)

                if (force || payload !== child.payload) {
                    child.payload = payload
                    child.controller.apply(payload, force)
                }
            })

            return measure()
        },

        position(rowTop) {
            const layerTop = layer.getBoundingClientRect().top

            children.forEach(({ host }, key) => {
                const top = rowTop(key, layerTop)

                host.style.visibility = top === null ? 'hidden' : ''
                host.style.top = `${(top ?? 0) + padding}px`
            })
        },

        controllers: () => [...children.values()].flatMap(({ controller }) => (controller ? [controller] : [])),

        isEditing: () => [...children.values()].some(({ controller }) => controller?.isEditing()),

        destroy() {
            cancelAnimationFrame(frame)
            resizeObserver.disconnect()
            ;[...children.keys()].forEach(remove)
            layer.remove()
        },
    }
}
