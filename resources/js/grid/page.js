export const isDarkMode = () => document.documentElement.classList.contains('dark')

export const callInParallel = (wire, method, args) =>
    typeof Livewire.fireAction === 'function'
        ? Livewire.fireAction(wire.__instance, method, args, { async: true })
        : wire[method](...args)
