const DEFAULT_HEIGHT = 120

export function createChildTables(layer, { onResize }) {
    const heights = new Map()
    const observed = new WeakSet()
    let padding = 0
    let frame = null

    const hosts = () => new Map([...layer.children].filter((host) => host.dataset.rowKey !== undefined).map((host) => [host.dataset.rowKey, host]))

    const measure = () => {
        let changed = false

        hosts().forEach((host, key) => {
            const height = host.style.display === 'none' ? 0 : host.offsetHeight

            if (height > 0 && height !== heights.get(key)) {
                heights.set(key, height)
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

    const observeHosts = () =>
        hosts().forEach((host) => {
            if (!observed.has(host)) {
                observed.add(host)
                resizeObserver.observe(host)
            }
        })

    const mutationObserver = new MutationObserver(() => {
        observeHosts()
        onResize()
    })

    observeHosts()
    mutationObserver.observe(layer, { childList: true })

    return {
        has: (key) => hosts().has(key),

        height: (key) => heights.get(key) ?? DEFAULT_HEIGHT,

        sync(openKeys, inset) {
            const open = new Set(openKeys)

            padding = inset
            hosts().forEach((host, key) => {
                host.style.display = open.has(key) ? '' : 'none'
                host.style.insetInline = `${padding}px`
            })

            return measure()
        },

        position(topFor) {
            const layerTop = layer.getBoundingClientRect().top

            hosts().forEach((host, key) => {
                if (host.style.display === 'none') {
                    return
                }

                const top = topFor(key, layerTop)

                host.style.visibility = top === null ? 'hidden' : ''
                host.style.top = `${top ?? 0}px`
            })
        },

        destroy() {
            cancelAnimationFrame(frame)
            resizeObserver.disconnect()
            mutationObserver.disconnect()
        },
    }
}
