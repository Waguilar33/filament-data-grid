import { createGrid } from './grid/slickgrid-adapter.js'

const isDarkMode = () => document.documentElement.classList.contains('dark')

export default function dataGrid({ statePath, translations }) {
    return {
        grid: null,
        localChanges: new Map(),
        observers: [],
        stopInterceptingMessages: null,
        wasEditingOnKeyDown: false,

        init() {
            this.grid = createGrid(this.$refs.grid, {
                payload: this.readPayload(),
                translations,
                isDarkMode: isDarkMode(),
                onCellChange: (rowKey, field, value, isLive) => this.writeCell(rowKey, field, value, isLive),
                search: (cellKey, search) =>
                    this.$wire.callSchemaComponentMethod(cellKey, 'getSearchResultsForJs', { search }),
            })

            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.grid.setDarkMode(isDarkMode()))

            this.stopInterceptingMessages = this.$wire.$interceptMessage(({ onFinish }) => {
                const sentChanges = new Map(this.localChanges)

                onFinish(() =>
                    queueMicrotask(() => {
                        sentChanges.forEach((value, cell) => {
                            if (this.localChanges.get(cell) === value) {
                                this.localChanges.delete(cell)
                            }
                        })

                        this.applyPayload()
                    }),
                )
            })
        },

        destroy() {
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

        readPayload() {
            return JSON.parse(this.$refs.payload.dataset.payload)
        },

        applyPayload() {
            this.grid.setPayload(this.readPayload(), this.localChanges)
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
