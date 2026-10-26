import { Editors, Slicker } from '@slickgrid-universal/vanilla-bundle'

const isDark = () => document.documentElement.classList.contains('dark')

export default function spikeSlickgrid({ rows }) {
    return {
        bundle: null,

        init() {
            const data = rows.map((row) => ({
                ...row,
                product: row.product ? { value: row.product, label: row.product_label } : null,
            }))

            const columns = [
                {
                    id: 'product',
                    name: 'Product',
                    field: 'product',
                    width: 320,
                    type: 'object',
                    dataKey: 'value',
                    labelKey: 'label',
                    formatter: (r, c, value) => value?.label ?? '',
                    editor: {
                        model: Editors.autocompleter,
                        type: 'object',
                        options: {
                            minLength: 1,
                            hideClearButton: false,
                            fetch: (term, update) => this.$wire.searchProducts(term).then(update),
                            renderItem: {
                                layout: 'twoRows',
                                templateCallback: (item) =>
                                    `<div class="autocomplete-title-label">${item.label}</div><div class="autocomplete-bottom-left">${item.code}</div>`,
                            },
                        },
                    },
                },
                {
                    id: 'quantity',
                    name: 'Quantity',
                    field: 'quantity',
                    width: 120,
                    cssClass: 'text-right',
                    editor: { model: Editors.float, minValue: 0, maxValue: 999, decimal: 3 },
                },
            ]

            this.bundle = new Slicker.GridBundle(
                this.$refs.grid,
                columns,
                {
                    editable: true,
                    autoEdit: true,
                    autoCommitEdit: true,
                    enableCellNavigation: true,
                    enableAutoResize: false,
                    autoHeight: true,
                    gridWidth: '100%',
                    rowHeight: 40,
                    darkMode: isDark(),
                    enableGridMenu: false,
                    enableHeaderMenu: false,
                    enableColumnPicker: false,
                    enableContextMenu: false,
                    enableCellMenu: false,
                    enableSorting: false,
                    enableFiltering: false,
                    showCustomFooter: false,
                    enablePagination: false,
                },
                data,
            )

            this.bundle.slickGrid.onCellChange.subscribe((e, args) => {
                console.log('[spike-slickgrid] change', JSON.stringify(args.item))
            })

            new ResizeObserver(() => {
                if (! this.bundle?.slickGrid) return
                this.bundle.slickGrid.resizeCanvas()
                this.bundle.slickGrid.autosizeColumns()
            }).observe(this.$refs.grid)

            new MutationObserver(() => {
                this.bundle.slickGrid.setOptions({ darkMode: isDark() })
                this.$refs.grid.classList.toggle('slick-dark-mode', isDark())
            }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
        },

        destroy() {
            this.bundle?.dispose()
        },
    }
}
