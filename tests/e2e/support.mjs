import { test as base, expect } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../..', import.meta.url))

export const resetDatabase = () => {
    execFileSync('php', ['vendor/bin/testbench', 'migrate:fresh', '--force', '--quiet'], { cwd: root, stdio: 'pipe' })
}

export const test = base.extend({
    page: async ({ page }, use) => {
        resetDatabase()

        const errors = []
        page.on('pageerror', (error) => errors.push(error.message))

        await use(page)

        expect(errors, 'no JavaScript errors').toEqual([])
    },
})

export { expect }

export const group = (page, key) => page.locator(`section[data-group="${key}"]`)

export const item = (page, key) => page.locator(`li[data-item="${key}"]`)

export const itemSwitch = (page, key) => item(page, key).locator('> .sbe-item-row [role="switch"]')

export const groupSwitch = (page, key) => group(page, key).locator('header [role="switch"]')

export const groupOrder = (page) => page.locator('.sbe-groups > section').evaluateAll((sections) => sections.map((section) => section.dataset.group))

export const itemOrder = (page, key) => group(page, key).locator('> ul > li[data-item]').evaluateAll((items) => items.map((node) => node.dataset.item))

export const sidebarGroups = (page) =>
    page.locator('.fi-main-sidebar .fi-sidebar-group').evaluateAll((groups) => groups.map((node) => node.querySelector('.fi-sidebar-group-label')?.textContent.trim() ?? ''))

export const sidebarItems = (page) => page.locator('.fi-main-sidebar .fi-sidebar-item-label').evaluateAll((labels) => labels.map((label) => label.textContent.trim()))

export const dragGroupAbove = (page, key, target) =>
    group(page, key).locator('[data-group-handle]').dragTo(group(page, target).locator('header'), { targetPosition: { x: 200, y: 2 } })

export const dragItemAbove = (page, key, target) =>
    item(page, key).locator('[data-item-handle]').dragTo(item(page, target).locator('> .sbe-item-row'), { targetPosition: { x: 200, y: 2 } })

export const save = async (page) => {
    await expect(page.locator('.sbe-dirty')).toBeVisible()

    await Promise.all([
        page.waitForEvent('load'),
        page.getByRole('button', { name: /^(Save sidebar|احفظ القائمة|Enregistrer le menu)$/ }).click(),
    ])
}
