import { dragGroupAbove, dragItemAbove, expect, group, groupOrder, groupSwitch, item, itemOrder, itemSwitch, save, sidebarGroups, sidebarItems, test } from './support.mjs'

test('hides pages, child pages and groups in the real sidebar', async ({ page }) => {
    await page.goto('/admin/sidebar')

    await itemSwitch(page, 'coupons').click()
    await expect(item(page, 'coupons')).toHaveClass(/is-hidden/)
    await page.locator('li[wire\\:key="child-brands"] [role="switch"]').click()
    await groupSwitch(page, 'marketing').click()
    await expect(group(page, 'marketing')).toHaveClass(/is-hidden/)
    await expect(page.locator('.sbe-dirty')).toBeVisible()
    await expect(group(page, 'sales').locator('.sbe-group-title p')).toHaveText('3 of 4 shown')
    await expect(page.locator('.sbe-preview')).not.toContainText('Coupons')

    await save(page)
    await page.reload()

    expect(await sidebarGroups(page)).not.toContain('Marketing')
    expect(await sidebarItems(page)).not.toContain('Coupons')

    await page.goto('/admin/products')

    expect(await sidebarItems(page)).toContain('Categories')
    expect(await sidebarItems(page)).not.toContain('Brands')
})

test('reorders groups and pages by dragging', async ({ page }) => {
    await page.goto('/admin/sidebar')

    await dragGroupAbove(page, 'catalog', 'sales')
    await expect.poll(() => groupOrder(page)).toEqual(['main', 'catalog', 'sales', 'marketing', 'settings'])

    await dragItemAbove(page, 'invoices', 'orders')
    await expect.poll(() => itemOrder(page, 'sales')).toEqual(['invoices', 'orders', 'customers', 'coupons'])

    await save(page)
    await page.reload()

    expect(await sidebarGroups(page)).toEqual(['', 'Catalog', 'Sales', 'Marketing', 'Settings'])
    expect((await sidebarItems(page)).slice(0, 3)).toEqual(['Dashboard', 'Products', 'Inventory'])
    expect((await sidebarItems(page)).indexOf('Invoices')).toBeLessThan((await sidebarItems(page)).indexOf('Orders'))
})

test('keeps the editor page and the top group locked', async ({ page }) => {
    await page.goto('/admin/sidebar')

    await expect(item(page, 'sidebar').locator('.sbe-pinned')).toBeVisible()
    await expect(itemSwitch(page, 'sidebar')).toHaveCount(0)
    await expect(groupSwitch(page, 'settings')).toHaveCount(0)
    await expect(group(page, 'main').locator('[data-group-handle]')).toHaveCount(0)
})

test('discards, shows everything and restores the default', async ({ page }) => {
    await page.goto('/admin/sidebar')

    await groupSwitch(page, 'sales').click()
    await page.getByRole('button', { name: 'Discard changes' }).click()
    await expect(group(page, 'sales')).not.toHaveClass(/is-hidden/)

    await groupSwitch(page, 'sales').click()
    await save(page)
    await page.getByRole('button', { name: 'Show everything' }).click()
    await expect(group(page, 'sales')).not.toHaveClass(/is-hidden/)

    await page.getByRole('button', { name: 'Restore default' }).click()
    await page.locator('.fi-modal-window:visible .fi-modal-footer-actions button').last().click()
    await page.waitForLoadState('networkidle')
    await page.reload()

    expect(await sidebarGroups(page)).toContain('Sales')
})

test('works right to left in Arabic', async ({ page }) => {
    await page.goto('/admin/sidebar?locale=ar')

    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl')
    await expect(page.locator('h1')).toHaveText('القائمة الجانبية')
    await expect(group(page, 'sales').locator('.sbe-group-title p')).toHaveText('تظهر 4 من 4')

    await itemSwitch(page, 'orders').click()
    await save(page)
    await page.reload()

    expect(await sidebarItems(page)).not.toContain('الطلبات')
    expect(await sidebarItems(page)).toContain('العملاء')
})
