# Changelog

## 0.1.0 - 2026-10-11

- Sidebar page (slug `sidebar` by default) where people hide, show and reorder navigation groups and pages, child pages included, with drag and drop, "N of M shown" counts, a live preview, show everything, discard, reset and `mod+s` to save.
- Registered per panel with `SidebarEditorPlugin::make()`. Panels without the plugin keep Filament's navigation.
- Storage drivers: tenant column (`storeOnTenant()`), per user (`storePerUser()`), one layout per panel (`storeGlobally()`, the default) and your own (`store()` or `storeUsing()`).
- `authorize()`, `slug()`, `navigationGroup()`, `navigationSort()`, `navigationIcon()`, `navigationLabel()`, `pinnedItems()` and `fixedMainGroup()`.
- The editor page is always pinned, and a group that holds a pinned page cannot be hidden, so nobody locks themselves out.
- New pages and resources appear automatically; removed ones drop out of saved layouts.
- Wraps whichever `NavigationManager` is bound through `extend()`, so it composes with other packages.
- English, French and Arabic. Light and dark mode, RTL, any panel theme.
- Testbench workbench (`composer serve`) and a Playwright end-to-end suite (`npm run test:e2e`) covering hide, drag and drop, save, reset and RTL, run in CI.
