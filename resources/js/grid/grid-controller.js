import { createGrid } from './slickgrid-adapter.js'
import { callInParallel, isDarkMode } from './page.js'

export function createGridController(element, { config, payload, wire, translations, icons }) {
    const { statePath, schemaComponent, actions } = config
    const localChanges = new Map()
    let appliedPayload = payload

    const writeCell = (rowKey, field, value, isLive) => {
        localChanges.set(`${rowKey}.${field}`, value)
        wire.$set(`${statePath}.${rowKey}.${field}`, value, isLive)
    }

    const grid = createGrid(element, {
        payload: JSON.parse(payload),
        translations,
        icons: { ...icons, expand: config.expandIcon },
        isDarkMode: isDarkMode(),
        onCellChange: writeCell,
        callSchemaComponentMethod: (key, method, args) =>
            callInParallel(wire, 'callSchemaComponentMethod', [key, method, args]),
        onOpenCell: (rowKey, field) => wire.mountAction(actions.editCell, { item: rowKey, field }, { schemaComponent }),
        onReorder: (keys) => wire.mountAction(actions.reorder, { items: keys }, { schemaComponent }),
        createChild: (childElement, child) =>
            createGridController(childElement, {
                config: child.config,
                payload: JSON.stringify(child.payload),
                wire,
                translations,
                icons,
            }),
    })

    return {
        apply(nextPayload, force = false) {
            if (!force && nextPayload === appliedPayload) {
                return
            }

            appliedPayload = nextPayload
            grid.setPayload(JSON.parse(nextPayload), localChanges, force)
        },

        beginRequest() {
            const sentChanges = new Map(localChanges)
            const children = grid.childControllers().map((child) => child.beginRequest())

            return () => {
                let cleared = false

                sentChanges.forEach((value, cell) => {
                    if (localChanges.get(cell) === value) {
                        localChanges.delete(cell)
                        cleared = true
                    }
                })

                return children.reduce((anyCleared, settle) => settle() || anyCleared, cleared)
            }
        },

        isEditing: () => grid.isEditing(),

        setDarkMode: (isDark) => grid.setDarkMode(isDark),

        destroy: () => grid.destroy(),
    }
}
