import { createChildTables } from './grid/child-tables.js'
import { createEntryGrid } from './grid/entry-adapter.js'
import { callInParallel, isDarkMode } from './grid/page.js'

const SERVER_SEARCH_DEBOUNCE_MS = 300
const REVEAL_DURATION_MS = 150

export default function dataGridEntry({ schemaComponent, translations, icons, loadingSkeletonDelay = null }) {
    return {
        grid: null,
        childTables: null,
        appliedPayload: null,
        observers: [],
        isServerSide: false,
        isEmpty: false,
        hasEmptyState: false,
        isMounted: false,
        isLoading: false,
        hasLoadingSkeleton: loadingSkeletonDelay !== null,
        skeletonTimeout: null,
        latestRequest: 0,
        searchTimeout: null,
        search: '',
        page: 1,
        pageCount: 1,
        recordsPerPage: null,
        pageOptions: [],
        preferredRecordsPerPage: null,
        total: 0,
        first: 0,
        last: 0,
        filterDefinitions: [],
        draftFilters: {},
        activeFilters: {},

        init() {
            this.appliedPayload = this.$refs.payload.dataset.payload

            const payload = JSON.parse(this.appliedPayload)

            this.isServerSide = payload.serverSide
            this.isEmpty = payload.isEmpty
            this.hasEmptyState = payload.hasEmptyState
            this.filterDefinitions = payload.filters ?? []
            this.pageOptions = payload.recordsPerPageOptions ?? []
            this.draftFilters = this.emptyFilters()
            this.activeFilters = this.emptyFilters()
            this.childTables = this.$refs.childTables ? createChildTables(this.$refs.childTables, { onResize: () => this.grid?.redraw() }) : null
            this.grid = createEntryGrid(this.$refs.grid, {
                payload,
                childTables: this.childTables,
                translations,
                icons,
                isDarkMode: isDarkMode(),
                onChange: (state) => Object.assign(this, state),
                onReady: () => this.reveal(),
            })

            this.isMounted = true
            this.$watch('search', (value) => this.searchFor(value))
            this.$watch('total', () => this.keepRecordsPerPageShown())
            this.keepRecordsPerPageShown()
            this.observe(this.$refs.payload, ['data-payload'], () => this.applyPayload())
            this.observe(document.documentElement, ['class'], () => this.grid.setDarkMode(isDarkMode()))

            const table = this.$el.closest('.fi-ta-content-ctn')

            if (table && this.hasLoadingSkeleton) {
                this.observe(table, ['class'], () => this.showSkeleton(table.classList.contains('fi-ta-content-loading')))
            }
        },

        showSkeleton(shows) {
            this.$refs.content.classList.toggle('fi-ta-content-loading', shows)
            this.$refs.grid.classList.toggle('fi-ta-content', shows)
        },

        reveal() {
            const skeleton = this.$refs.content.querySelector(':scope > .fi-data-grid-skeleton')

            if (!skeleton?.isConnected) {
                return
            }

            const content = this.$refs.content
            const from = content.offsetHeight

            skeleton.remove()

            const to = content.offsetHeight

            if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return
            }

            const timing = { duration: REVEAL_DURATION_MS, easing: 'ease-out' }

            this.$refs.grid.animate([{ opacity: 0 }, { opacity: 1 }], timing)

            if (from !== to) {
                content.animate([{ height: `${from}px`, overflow: 'clip' }, { height: `${to}px`, overflow: 'clip' }], timing)
            }
        },

        destroy() {
            clearTimeout(this.searchTimeout)
            clearTimeout(this.skeletonTimeout)
            this.observers.forEach((observer) => observer.disconnect())
            this.childTables?.destroy()
            this.grid?.destroy()
        },

        get showsEmptyState() {
            return this.hasEmptyState && this.isEmpty && this.total === 0
        },

        get sizedPageOptions() {
            return this.pageOptions.filter((option) => typeof option === 'number').sort((a, b) => a - b)
        },

        get fitsOnSmallestPage() {
            return this.total <= Math.min(...this.sizedPageOptions)
        },

        get shownPageOptions() {
            const sizes = this.sizedPageOptions
            const coveringIndex = sizes.findIndex((size) => size >= this.total)
            const shownSizes = coveringIndex === -1 ? sizes : sizes.slice(0, coveringIndex + 1)

            return [...shownSizes, ...this.pageOptions.filter((option) => typeof option !== 'number')]
        },

        keepRecordsPerPageShown() {
            this.preferredRecordsPerPage ??= this.recordsPerPage

            const preferred = this.preferredRecordsPerPage

            if (typeof preferred !== 'number') {
                return
            }

            const shownSizes = this.shownPageOptions.filter((option) => typeof option === 'number')
            const size = shownSizes.includes(preferred) ? preferred : shownSizes.at(-1)

            if (size !== undefined && size !== this.recordsPerPage) {
                this.query({ recordsPerPage: size })
            }
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

        get activeFiltersCount() {
            return Object.values(this.activeFilters).filter((value) => this.isActiveFilterValue(value)).length
        },

        isActiveFilterValue(value) {
            return Array.isArray(value) ? value.length > 0 : value !== null && value !== '' && value !== false
        },

        emptyFilters() {
            return Object.fromEntries(
                this.filterDefinitions.map((filter) => [
                    filter.name,
                    filter.type === 'checkbox' ? false : filter.multiple ? [] : '',
                ]),
            )
        },

        applyFilters() {
            this.activeFilters = JSON.parse(JSON.stringify(this.draftFilters))
            this.query({ filters: this.activeFilters })
        },

        resetFilters() {
            this.draftFilters = this.emptyFilters()
            this.applyFilters()
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
            this.preferredRecordsPerPage = /^\d+$/.test(String(value)) ? Number(value) : value
            this.query({ recordsPerPage: this.preferredRecordsPerPage })
        },

        query(changes) {
            const query = this.grid.setQuery(changes)

            if (this.isServerSide) {
                this.fetchPage(query)
            }
        },

        async fetchPage({ search, page, recordsPerPage, filters }) {
            const request = ++this.latestRequest

            this.isLoading = true

            if (this.hasLoadingSkeleton) {
                clearTimeout(this.skeletonTimeout)
                this.skeletonTimeout = setTimeout(() => this.showSkeleton(true), loadingSkeletonDelay)
            }

            try {
                const result = await callInParallel(this.$wire, 'callSchemaComponentMethod', [
                    schemaComponent,
                    'getGridPage',
                    [search, page, recordsPerPage, filters],
                ])

                if (request === this.latestRequest && result) {
                    this.grid.setServerPage(result)
                }
            } finally {
                if (request === this.latestRequest) {
                    this.isLoading = false

                    if (this.hasLoadingSkeleton) {
                        clearTimeout(this.skeletonTimeout)
                        this.showSkeleton(false)
                    }
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

            this.isEmpty = nextPayload.isEmpty
            this.hasEmptyState = nextPayload.hasEmptyState

            const query = this.grid.getQuery()

            this.grid.setPayload(nextPayload)

            const hasActiveFilters = Object.values(query.filters).some((value) => this.isActiveFilterValue(value))

            if (this.isServerSide && (query.search !== '' || query.page !== 1 || query.recordsPerPage !== nextPayload.recordsPerPage || hasActiveFilters)) {
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
