import { TabulatorFull as Tabulator } from 'tabulator-tables'

export default function spikeTabulator({ rows }) {
    return {
        table: null,
        labels: new Map(),

        init() {
            rows.forEach((row) => row.product && this.labels.set(row.product, row.product_label))

            this.table = new Tabulator(this.$refs.grid, {
                data: rows.map((row) => ({ ...row })),
                layout: 'fitColumns',
                popupContainer: new URLSearchParams(location.search).has('body') ? false : (this.$root.closest('.fi-modal-window') ?? false),
                columns: [
                    {
                        title: 'Product',
                        field: 'product',
                        formatter: (cell) => this.labels.get(cell.getValue()) ?? '',
                        editor: 'list',
                        editorParams: {
                            autocomplete: true,
                            filterRemote: true,
                            filterDelay: 200,
                            listOnEmpty: true,
                            allowEmpty: true,
                            valuesLookup: (cell, term) =>
                                this.$wire.searchProducts(term ?? '').then((items) => {
                                    items.forEach((item) => this.labels.set(item.value, item.label))

                                    return items.map((item) => ({ label: item.label, value: item.value, code: item.code }))
                                }),
                            itemFormatter: (label, value, item) => `<div>${label}</div><small style="opacity:.6">${item.code}</small>`,
                        },
                    },
                    { title: 'Quantity', field: 'quantity', hozAlign: 'right', width: 120, editor: 'number', editorParams: { min: 0, max: 999, step: 0.001 } },
                ],
            })

            this.table.on('cellEdited', (cell) => console.log('[spike-tabulator] change', JSON.stringify(cell.getRow().getData())))
        },

        destroy() {
            this.table?.destroy()
        },
    }
}
