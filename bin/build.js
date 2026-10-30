import * as esbuild from 'esbuild'
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs'

const isWatching = process.argv.includes('--watch')

const entries = [
    { in: 'resources/js/data-grid.js', out: 'data-grid' },
    { in: 'resources/js/data-grid-entry.js', out: 'data-grid-entry' },
    { in: 'resources/css/data-grid.css', out: 'data-grid' },
]

const context = await esbuild.context({
    entryPoints: entries,
    outdir: 'resources/dist',
    bundle: true,
    minify: !isWatching && !process.argv.includes("--dev"),
    format: 'esm',
    platform: 'browser',
    target: ['es2022'],
    logLevel: 'info',
    loader: { '.svg': 'dataurl', '.woff': 'dataurl', '.woff2': 'dataurl' },
    metafile: true,
})

const FILAMENT_NOTICE = `MIT License

Copyright (c) Filament

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.`

const licenseText = (directory) => {
    const file = readdirSync(directory).find((name) => /^licen[cs]e/i.test(name))

    if (file) {
        return readFileSync(`${directory}/${file}`, 'utf8').trim()
    }

    const readme = readdirSync(directory).find((name) => /^readme/i.test(name))
    const section = readme ? readFileSync(`${directory}/${readme}`, 'utf8').split(/^#+\s*licen[cs]e\s*$/im)[1] : null

    return section?.trim() ?? 'See the package repository.'
}

const writeThirdPartyLicenses = (metafile) => {
    const packages = new Set()
    let bundlesFilament = false

    for (const input of Object.keys(metafile.inputs)) {
        const match = input.match(/node_modules\/((@[^/]+\/)?[^/]+)/)

        if (match) {
            packages.add(match[1])
        }

        bundlesFilament ||= input.startsWith('vendor/filament/')
    }

    const sections = [...packages].sort().map((name) => {
        const directory = `node_modules/${name}`
        const { version, license } = JSON.parse(readFileSync(`${directory}/package.json`, 'utf8'))

        return `${name}@${version} (${license})\n\n${licenseText(directory)}`
    })

    if (bundlesFilament && existsSync('vendor/filament/support/composer.json')) {
        sections.push(`filament/support (MIT)\n\n${FILAMENT_NOTICE}`)
    }

    writeFileSync(
        'resources/dist/THIRD-PARTY-LICENSES.txt',
        `resources/dist/data-grid.js includes the following third-party software.\n\n${sections.join('\n\n---\n\n')}\n`,
    )
}

if (isWatching) {
    await context.watch()
} else {
    const result = await context.rebuild()
    writeThirdPartyLicenses(result.metafile)
    await context.dispose()
}
