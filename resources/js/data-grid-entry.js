import { createEntryGrid } from './grid/entry-adapter.js'
import { isDarkMode, whenStylesheetLoads } from './grid/page.js'

export default function dataGridEntry({ translations, icons, styleHref }) {
    return {
        grid: null,
        appliedPayload: null,
        observers: [],
        isDestroyed: false,
        isMounted: false,
        search: '',
        page: 1,
        pageCount: 1,
        total: 0,
        first: 0,
        last: 0,

        init() {
            whenStylesheetLoads(styleHref).then(() => {
                if (!this.isDestroyed) {
                    this.mount()
                }
            })
        },

        mount() {
            this.appliedPayload = this.$refs.payload.dataset.payload
            this.grid = createEntryGrid(this.$refs.grid, {
                payload: JSON.parse(this.appliedPayload),
                translations,
                icons,
                isDarkMode: isDarkMode(),
                onChange: (state) => Object.assign(this, state),
            })

            this.$refs.grid.style.removeProperty('min-height')
            this.isMounted = true
            this.$watch('search', (value) => this.grid.search(value))
            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.grid.setDarkMode(isDarkMode()))
        },

        destroy() {
            this.isDestroyed = true
            this.observers.forEach((observer) => observer.disconnect())
            this.grid?.destroy()
        },

        get overview() {
            return translations.pagination.overview
                .replace(':first', this.first)
                .replace(':last', this.last)
                .replace(':total', this.total)
        },

        previousPage() {
            this.grid?.goToPage(this.page - 1)
        },

        nextPage() {
            this.grid?.goToPage(this.page + 1)
        },

        applyPayload() {
            const payload = this.$refs.payload.dataset.payload

            if (payload === this.appliedPayload) {
                return
            }

            this.appliedPayload = payload
            this.grid.setPayload(JSON.parse(payload))
        },

        observe(element, attributes, callback) {
            const observer = new MutationObserver(callback)

            observer.observe(element, { attributes: true, attributeFilter: attributes })
            this.observers.push(observer)
        },
    }
}
