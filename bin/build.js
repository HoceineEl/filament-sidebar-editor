import * as esbuild from 'esbuild'

const isDev = process.argv.includes('--dev')

const context = await esbuild.context({
    entryPoints: ['./resources/css/index.css'],
    outfile: './dist/sidebar-editor.css',
    bundle: true,
    minify: !isDev,
    sourcemap: isDev ? 'inline' : false,
    logLevel: 'info',
})

if (isDev) {
    await context.watch()
} else {
    await context.rebuild()
    await context.dispose()
}
