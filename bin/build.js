import * as esbuild from 'esbuild'

const isWatching = process.argv.includes('--watch')

const entries = [
    { in: 'resources/js/data-grid.js', out: 'data-grid' },
    { in: 'resources/css/data-grid.css', out: 'data-grid' },
]

const context = await esbuild.context({
    entryPoints: entries,
    outdir: 'resources/dist',
    bundle: true,
    minify: !isWatching,
    format: 'esm',
    platform: 'browser',
    target: ['es2022'],
    logLevel: 'info',
    loader: { '.svg': 'dataurl', '.woff': 'dataurl', '.woff2': 'dataurl' },
})

if (isWatching) {
    await context.watch()
} else {
    await context.rebuild()
    await context.dispose()
}
