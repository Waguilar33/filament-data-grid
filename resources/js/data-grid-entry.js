import { createEntryGrid } from './grid/entry-adapter.js'
import { callInParallel, isDarkMode } from './grid/page.js'

const SERVER_SEARCH_DEBOUNCE_MS = 300

export default function dataGridEntry({ schemaComponent, translations, icons }) {
    return {
        grid: null,
        appliedPayload: null,
        observers: [],
        isServerSide: false,
        isMounted: false,
        isLoading: false,
        latestRequest: 0,
        searchTimeout: null,
        search: '',
        page: 1,
        pageCount: 1,
        recordsPerPage: null,
        total: 0,
        first: 0,
        last: 0,

        init() {
            this.appliedPayload = this.$refs.payload.dataset.payload

            const payload = JSON.parse(this.appliedPayload)

            this.isServerSide = payload.serverSide
            this.grid = createEntryGrid(this.$refs.grid, {
                payload,
                translations,
                icons,
                isDarkMode: isDarkMode(),
                onChange: (state) => Object.assign(this, state),
            })

            this.$refs.grid.style.removeProperty('min-height')
            this.isMounted = true
            this.$watch('search', (value) => this.searchFor(value))
            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.grid.setDarkMode(isDarkMode()))
        },

        destroy() {
            clearTimeout(this.searchTimeout)
            this.observers.forEach((observer) => observer.disconnect())
            this.grid?.destroy()
        },

        get overview() {
            const template = this.total === 1 ? translations.pagination.overview_one : translations.pagination.overview_many

            return template
                .replace(':first', this.first.toLocaleString())
                .replace(':last', this.last.toLocaleString())
                .replace(':total', this.total.toLocaleString())
        },

        get pages() {
            const pages = []

            for (let number = 1; number <= this.pageCount; number++) {
                const isEdge = number === 1 || number === this.pageCount
                const isNear = Math.abs(number - this.page) <= 2

                if (isEdge || isNear) {
                    pages.push(number)
                } else if (pages.at(-1) !== '...') {
                    pages.push('...')
                }
            }

            return pages
        },

        goToPageLabel(number) {
            return translations.pagination.go_to_page.replace(':page', number.toLocaleString())
        },

        searchFor(value) {
            clearTimeout(this.searchTimeout)

            if (!this.isServerSide) {
                this.grid.setQuery({ search: value })

                return
            }

            this.searchTimeout = setTimeout(() => this.query({ search: value }), SERVER_SEARCH_DEBOUNCE_MS)
        },

        goToPage(number) {
            if (number < 1 || number > this.pageCount || number === this.page) {
                return
            }

            this.query({ page: number })
        },

        changeRecordsPerPage(value) {
            this.query({ recordsPerPage: /^\d+$/.test(String(value)) ? Number(value) : value })
        },

        query(changes) {
            const query = this.grid.setQuery(changes)

            if (this.isServerSide) {
                this.fetchPage(query)
            }
        },

        async fetchPage({ search, page, recordsPerPage }) {
            const request = ++this.latestRequest

            this.isLoading = true

            try {
                const result = await callInParallel(this.$wire, 'callSchemaComponentMethod', [
                    schemaComponent,
                    'getGridPage',
                    [search, page, recordsPerPage],
                ])

                if (request === this.latestRequest && result) {
                    this.grid.setServerPage(result)
                }
            } finally {
                if (request === this.latestRequest) {
                    this.isLoading = false
                }
            }
        },

        applyPayload() {
            const payload = this.$refs.payload.dataset.payload

            if (payload === this.appliedPayload) {
                return
            }

            this.appliedPayload = payload

            const nextPayload = JSON.parse(payload)
            const query = this.grid.getQuery()

            this.grid.setPayload(nextPayload)

            if (this.isServerSide && (query.search !== '' || query.page !== 1 || query.recordsPerPage !== nextPayload.recordsPerPage)) {
                this.grid.setQuery(query)
                this.fetchPage(query)
            }
        },

        observe(element, attributes, callback) {
            const observer = new MutationObserver(callback)

            observer.observe(element, { attributes: true, attributeFilter: attributes })
            this.observers.push(observer)
        },
    }
}
