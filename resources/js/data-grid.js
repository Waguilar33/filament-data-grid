import { createGrid } from './grid/slickgrid-adapter.js'
import { isDarkMode, whenStylesheetLoads } from './grid/page.js'

const callInParallel = (wire, method, args) =>
    typeof Livewire.fireAction === 'function'
        ? Livewire.fireAction(wire.__instance, method, args, { async: true })
        : wire[method](...args)

const afterEachRequest = (wire, startRequest) => {
    if (typeof Livewire.interceptMessage === 'function') {
        return wire.$interceptMessage(({ onFinish }) => onFinish(startRequest()))
    }

    return Livewire.hook('commit', ({ component, succeed, fail }) => {
        if (component.id !== wire.$id) {
            return
        }

        const finish = startRequest()

        succeed(finish)
        fail(finish)
    })
}

export default function dataGrid({ statePath, schemaComponent, actions, translations, icons, styleHref }) {
    return {
        grid: null,
        localChanges: new Map(),
        appliedPayload: null,
        observers: [],
        stopInterceptingMessages: null,
        wasEditingOnKeyDown: false,

        isDestroyed: false,

        init() {
            whenStylesheetLoads(styleHref).then(() => {
                if (!this.isDestroyed) {
                    this.mount()
                }
            })
        },

        mount() {
            this.appliedPayload = this.$refs.payload.dataset.payload
            this.grid = createGrid(this.$refs.grid, {
                payload: JSON.parse(this.appliedPayload),
                translations,
                icons,
                isDarkMode: isDarkMode(),
                onCellChange: (rowKey, field, value, isLive) => this.writeCell(rowKey, field, value, isLive),
                callSchemaComponentMethod: (key, method, args) =>
                    callInParallel(this.$wire, 'callSchemaComponentMethod', [key, method, args]),
                onOpenCell: (rowKey, field) =>
                    this.$wire.mountAction(actions.editCell, { item: rowKey, field }, { schemaComponent }),
                onReorder: (keys) => this.$wire.mountAction(actions.reorder, { items: keys }, { schemaComponent }),
            })

            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.grid.setDarkMode(isDarkMode()))

            this.stopInterceptingMessages = afterEachRequest(this.$wire, () => {
                const sentChanges = new Map(this.localChanges)

                return () =>
                    queueMicrotask(() => {
                        let cleared = false

                        sentChanges.forEach((value, cell) => {
                            if (this.localChanges.get(cell) === value) {
                                this.localChanges.delete(cell)
                                cleared = true
                            }
                        })

                        this.applyPayload(cleared)
                    })
            })
        },

        destroy() {
            this.isDestroyed = true
            this.stopInterceptingMessages?.()
            this.observers.forEach((observer) => observer.disconnect())
            this.grid?.destroy()
        },

        rememberEditingState() {
            this.wasEditingOnKeyDown = this.grid?.isEditing() ?? false
        },

        stopEscapeWhileEditing(event) {
            if (this.wasEditingOnKeyDown) {
                event.stopPropagation()
            }
        },

        applyPayload(force = false) {
            const payload = this.$refs.payload.dataset.payload

            if (!force && payload === this.appliedPayload) {
                return
            }

            this.appliedPayload = payload
            this.grid.setPayload(JSON.parse(payload), this.localChanges)
        },

        writeCell(rowKey, field, value, isLive) {
            this.localChanges.set(`${rowKey}.${field}`, value)
            this.$wire.$set(`${statePath}.${rowKey}.${field}`, value, isLive)
        },

        observe(element, attributes, callback) {
            const observer = new MutationObserver(callback)

            observer.observe(element, { attributes: true, attributeFilter: attributes })
            this.observers.push(observer)
        },
    }
}
