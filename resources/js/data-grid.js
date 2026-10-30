import { createGridController } from './grid/grid-controller.js'
import { isDarkMode } from './grid/page.js'

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

export default function dataGrid({ statePath, schemaComponent, actions, translations, icons }) {
    return {
        controller: null,
        observers: [],
        stopInterceptingMessages: null,
        wasEditingOnKeyDown: false,

        init() {
            this.controller = createGridController(this.$refs.grid, {
                config: { statePath, schemaComponent, actions },
                payload: this.$refs.payload.dataset.payload,
                wire: this.$wire,
                translations,
                icons,
            })

            this.$refs.grid.style.removeProperty('min-height')
            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.controller.setDarkMode(isDarkMode()))

            this.stopInterceptingMessages = afterEachRequest(this.$wire, () => {
                const settle = this.controller.beginRequest()

                return () => queueMicrotask(() => this.applyPayload(settle()))
            })
        },

        destroy() {
            this.stopInterceptingMessages?.()
            this.observers.forEach((observer) => observer.disconnect())
            this.controller?.destroy()
        },

        rememberEditingState() {
            this.wasEditingOnKeyDown = this.controller?.isEditing() ?? false
        },

        stopEscapeWhileEditing(event) {
            if (this.wasEditingOnKeyDown) {
                event.stopPropagation()
            }
        },

        applyPayload(force = false) {
            this.controller.apply(this.$refs.payload.dataset.payload, force)
        },

        observe(element, attributes, callback) {
            const observer = new MutationObserver(callback)

            observer.observe(element, { attributes: true, attributeFilter: attributes })
            this.observers.push(observer)
        },
    }
}
