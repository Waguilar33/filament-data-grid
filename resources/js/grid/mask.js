const MAX_CACHED_TEXTS = 2000
const formatted = new Map()
const pending = new Map()

export const hasMask = (options) => Boolean(options?.mask)

export const applyMask = (input, options) => {
    input.setAttribute(options.maskDynamic ? 'x-mask:dynamic' : 'x-mask', options.mask)
    window.Alpine.initTree(input)
}

export const releaseMask = (input) => window.Alpine.destroyTree(input)

export const stripMask = (text, options) =>
    (options?.stripCharacters ?? []).reduce((result, character) => result.replaceAll(character, ''), String(text))

export const maskInputText = (value, options) => {
    const text = String(value)

    return /^-?\d+\.\d+$/.test(text) && options.stripCharacters?.includes('.') ? text.replace('.', ',') : text
}

const cacheKey = (text, options) => JSON.stringify([options.mask, Boolean(options.maskDynamic), text])

export const maskedText = (value, options) => {
    const text = maskInputText(value, options)
    const key = cacheKey(text, options)

    if (formatted.has(key)) {
        return { text: formatted.get(key), ready: null }
    }

    if (!pending.has(key)) {
        const input = document.createElement('input')
        input.value = text
        applyMask(input, options)

        // Alpine mask formats on a microtask after init, so the result is read on the next one.
        pending.set(key, new Promise((resolve) => queueMicrotask(() => {
            if (formatted.size >= MAX_CACHED_TEXTS) {
                formatted.clear()
            }

            formatted.set(key, input.value)
            pending.delete(key)
            releaseMask(input)
            resolve(input.value)
        })))
    }

    return { text: null, ready: pending.get(key) }
}
