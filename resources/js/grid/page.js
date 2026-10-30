export const isDarkMode = () => document.documentElement.classList.contains('dark')

const STYLESHEET_TIMEOUT_MS = 5000

export const whenStylesheetLoads = (href) =>
    new Promise((resolve) => {
        const url = new URL(href, window.location.href).href
        const startedAt = performance.now()

        const check = () => {
            const isLoaded = [...document.styleSheets].some((sheet) => sheet.href === url)

            if (isLoaded || performance.now() - startedAt > STYLESHEET_TIMEOUT_MS) {
                resolve()

                return
            }

            requestAnimationFrame(check)
        }

        check()
    })
