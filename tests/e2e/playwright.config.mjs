import { defineConfig } from '@playwright/test'
import { chromium } from '@playwright/test'
import { existsSync } from 'node:fs'
import { createServer } from 'node:net'
import { homedir } from 'node:os'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../..', import.meta.url))

const freePort = () =>
    new Promise((resolve, reject) => {
        const server = createServer()

        server.once('error', reject)
        server.listen(0, '127.0.0.1', () => {
            const { port } = server.address()
            server.close(() => resolve(port))
        })
    })

const resolveExecutable = () => {
    if (process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE) {
        return process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE
    }

    if (existsSync(chromium.executablePath())) {
        return undefined
    }

    const cached = `${homedir()}/Library/Caches/ms-playwright/chromium_headless_shell-1243/chrome-headless-shell-mac-arm64/chrome-headless-shell`

    return existsSync(cached) ? cached : undefined
}

process.env.SE_E2E_PORT ||= String(await freePort())

const baseURL = `http://127.0.0.1:${process.env.SE_E2E_PORT}`

export default defineConfig({
    testDir: '.',
    testMatch: '*.spec.mjs',
    outputDir: `${root}/test-results`,
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    timeout: 30_000,
    expect: { timeout: 7_000 },
    reporter: process.env.CI ? [['list'], ['github']] : 'list',
    use: {
        baseURL,
        viewport: { width: 1280, height: 800 },
        trace: 'retain-on-failure',
        launchOptions: { executablePath: resolveExecutable() },
    },
    webServer: {
        command: `php vendor/bin/testbench workbench:build --quiet && php vendor/bin/testbench serve --port=${process.env.SE_E2E_PORT} --no-reload`,
        cwd: root,
        url: `${baseURL}/admin/sidebar`,
        env: { PHP_CLI_SERVER_WORKERS: '4' },
        reuseExistingServer: false,
        timeout: 120_000,
        stdout: 'ignore',
        stderr: 'pipe',
    },
})
