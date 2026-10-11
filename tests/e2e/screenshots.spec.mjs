import { fileURLToPath } from 'node:url'
import { expect, group, groupSwitch, itemSwitch, test } from './support.mjs'

const directory = process.env.SE_SCREENSHOTS_DIR || fileURLToPath(new URL('../../docs/images', import.meta.url))

test.skip(!process.env.SE_SCREENSHOTS, 'Set SE_SCREENSHOTS=1 to retake the README screenshots.')

test.use({ deviceScaleFactor: 2 })

const shoot = (page, name) => page.screenshot({ path: `${directory}/${name}.png` })

const edit = async (page) => {
    await itemSwitch(page, 'coupons').click()
    await groupSwitch(page, 'marketing').click()
    await expect(group(page, 'marketing')).toHaveClass(/is-hidden/)
    await page.evaluate(() => window.scrollTo(0, 0))
    await page.mouse.move(0, 0)
    await page.waitForTimeout(300)
}

test('editor', async ({ page }) => {
    await page.goto('/admin/sidebar')
    await edit(page)
    await shoot(page, 'editor')
})

test('dark', async ({ page }) => {
    await page.emulateMedia({ colorScheme: 'dark' })
    await page.goto('/admin/sidebar')
    await page.evaluate(() => document.documentElement.classList.add('dark'))
    await edit(page)
    await shoot(page, 'dark')
})

test('rtl', async ({ page }) => {
    await page.goto('/admin/sidebar?locale=ar')
    await edit(page)
    await shoot(page, 'rtl')
})
