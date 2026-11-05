<script data-navigate-once>
/**
 * Exclusive sidebar navigation groups — only one expanded at a time.
 *
 * Filament v5 stores expand/collapse state in the Alpine store
 * `$store.sidebar` (collapsedGroups + groupIsCollapsed/toggleCollapsedGroup).
 * Each <li class="fi-sidebar-group"> carries its label in
 * `data-group-label`. When the user opens a group, we walk every other
 * group and toggle it closed via the same store API Filament uses, so
 * the persisted state stays consistent.
 *
 * Initial collapsed state on first visit is handled by the separate
 * sidebar-nav-init partial (rendered at BODY_START so it runs before
 * Filament's sidebar init reads localStorage).
 */
(function () {
    if (window.__exclusiveNav) return;
    window.__exclusiveNav = true;

    const TRIGGER_SELECTOR = '.fi-sidebar-group-btn, .fi-sidebar-group-collapse-btn';

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest(TRIGGER_SELECTOR);
        if (!trigger) return;

        const clickedGroup = trigger.closest('.fi-sidebar-group[data-group-label]');
        if (!clickedGroup) return;
        const clickedLabel = clickedGroup.dataset.groupLabel;

        // Defer one frame so Filament's own toggle runs first.
        requestAnimationFrame(() => {
            const store = window.Alpine?.store?.('sidebar');
            if (!store || typeof store.groupIsCollapsed !== 'function') return;

            document.querySelectorAll('.fi-sidebar-group[data-group-label]').forEach(group => {
                const label = group.dataset.groupLabel;
                if (! label || label === clickedLabel) return;

                // If this other group is currently expanded, collapse it.
                if (! store.groupIsCollapsed(label)) {
                    store.toggleCollapsedGroup(label);
                }
            });
        });
    }, true);
})();
</script>
