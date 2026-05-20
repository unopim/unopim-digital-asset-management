@if (! empty($directoryTree))
    <div
        id="dam-directory-permissions-tab"
        v-if="permission_type == 'custom' || selected_permission_type == 'custom'"
        class="flex flex-col gap-2 flex-1 max-xl:flex-auto"
    >
        <input
            type="hidden"
            name="dam_directory_grants_managed"
            value="1"
        />

        <div class="p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <p class="text-base text-gray-800 dark:text-white font-semibold mb-1">
                @lang('dam::app.admin.permissions.title')
            </p>
            <p class="text-sm text-gray-600 dark:text-slate-300 mb-4">
                @lang('dam::app.admin.permissions.tab-subtitle')
            </p>

            {{-- All Directories toggle --}}
            <label class="flex items-center gap-2 cursor-pointer mb-4 select-none">
                <input
                    type="checkbox"
                    id="dam-all-directories-toggle"
                    name="dam_all_directories"
                    value="1"
                    class="w-4 h-4 rounded border-gray-300 text-violet-700 cursor-pointer"
                    {{ $allDirectories ? 'checked' : '' }}
                />
                <span class="font-semibold text-gray-800 dark:text-white text-sm">
                    @lang('dam::app.admin.permissions.all-directories')
                </span>
            </label>

            {{-- Directory tree (hidden when all-directories is checked) --}}
            <div id="dam-directory-tree-wrapper" {{ $allDirectories ? 'style=display:none' : '' }}>
                <x-admin::tree.view
                    input-type="checkbox"
                    name-field="directories"
                    value-field="id"
                    id-field="id"
                    label-field="name"
                    selection-type="individual"
                    :items="json_encode($directoryTree)"
                    :value="json_encode($grantedIds)"
                    :fallback-locale="config('app.fallback_locale')"
                />
            </div>
        </div>
    </div>

    @push('scripts')
        <script type="module">
            (function () {
                var EXPANDED_ATTR = 'data-dam-perm-expanded';
                var COLLAPSED_ATTR = 'data-dam-perm-collapsed';
                var ATTACHED_ATTR = 'data-dam-perm-attached';
                var propagating = false;
                var syncing = false;

                function root() {
                    return document.getElementById('dam-directory-permissions-tab');
                }

                function swapChevronDown(item) {
                    var chev = item.querySelector(':scope > i.text-xl');
                    if (chev && chev.classList.contains('icon-chevron-right')) {
                        chev.classList.remove('icon-chevron-right');
                        chev.classList.add('icon-chevron-down');
                    }
                }

                function swapChevronRight(item) {
                    var chev = item.querySelector(':scope > i.text-xl');
                    if (chev && chev.classList.contains('icon-chevron-down')) {
                        chev.classList.remove('icon-chevron-down');
                        chev.classList.add('icon-chevron-right');
                    }
                }

                function applyAttrs() {
                    var r = root();
                    if (! r) return false;
                    var items = r.querySelectorAll('.v-tree-item');
                    if (items.length === 0) return false;

                    syncing = true;
                    try {
                        for (var i = 0; i < items.length; i++) {
                            var item = items[i];
                            if (! item.querySelector('.v-tree-item')) continue;

                            if (item.hasAttribute(EXPANDED_ATTR)) {
                                if (! item.classList.contains('active')) {
                                    item.classList.add('active');
                                }
                                swapChevronDown(item);
                            } else if (item.hasAttribute(COLLAPSED_ATTR)) {
                                if (item.classList.contains('active')) {
                                    item.classList.remove('active');
                                }
                                swapChevronRight(item);
                            }
                        }
                    } finally {
                        syncing = false;
                    }

                    return true;
                }

                function markAncestorsExpanded(item) {
                    var node = item.parentElement
                        ? item.parentElement.closest('.v-tree-item')
                        : null;
                    while (node) {
                        if (node.querySelector('.v-tree-item')) {
                            node.setAttribute(EXPANDED_ATTR, '1');
                            node.removeAttribute(COLLAPSED_ATTR);
                        }
                        node = node.parentElement
                            ? node.parentElement.closest('.v-tree-item')
                            : null;
                    }
                }

                function markSubtreeExpanded(item) {
                    if (item.querySelector('.v-tree-item')) {
                        item.setAttribute(EXPANDED_ATTR, '1');
                        item.removeAttribute(COLLAPSED_ATTR);
                    }
                    var nested = item.querySelectorAll('.v-tree-item');
                    for (var i = 0; i < nested.length; i++) {
                        if (nested[i].querySelector('.v-tree-item')) {
                            nested[i].setAttribute(EXPANDED_ATTR, '1');
                            nested[i].removeAttribute(COLLAPSED_ATTR);
                        }
                    }
                }

                function applyInitialExpansion() {
                    var r = root();
                    if (! r) return false;
                    if (r.querySelector('.v-tree-item') === null) return false;

                    var checked = r.querySelectorAll(
                        'input[type="checkbox"][name="directories[]"]:checked'
                    );
                    for (var i = 0; i < checked.length; i++) {
                        var item = checked[i].closest('.v-tree-item');
                        if (item) markAncestorsExpanded(item);
                    }

                    applyAttrs();
                    return true;
                }

                function attachInteractionTrackers() {
                    var r = root();
                    if (! r) return;
                    if (r.getAttribute(ATTACHED_ATTR) === '1') return;
                    r.setAttribute(ATTACHED_ATTR, '1');

                    // Tab DOM is fresh — Vue may not have rendered the tree
                    // checkboxes yet. Retry initial expansion until they
                    // appear (or give up after a few seconds). Re-runs each
                    // time v-if recreates the tab.
                    var tries = 0;
                    var iv = setInterval(function () {
                        if (applyInitialExpansion() || ++tries > 60) {
                            clearInterval(iv);
                        }
                    }, 80);

                    // Chevron click — record explicit intent. Runs in capture
                    // phase so the attribute is set BEFORE Vue's own handler
                    // toggles the `active` class, then applyAttrs honors it
                    // via the mutation observer.
                    r.addEventListener('click', function (e) {
                        var t = e.target;
                        if (! t || t.tagName !== 'I') return;
                        if (! t.classList.contains('text-xl')) return;
                        var parent = t.parentElement;
                        if (! parent || ! parent.classList.contains('v-tree-item')) return;

                        var willBeActive = ! parent.classList.contains('active');
                        if (willBeActive) {
                            parent.setAttribute(EXPANDED_ATTR, '1');
                            parent.removeAttribute(COLLAPSED_ATTR);
                        } else {
                            parent.setAttribute(COLLAPSED_ATTR, '1');
                            parent.removeAttribute(EXPANDED_ATTR);
                        }
                    }, true);

                    r.addEventListener('change', function (e) {
                        var t = e.target;
                        if (! t || ! t.matches) return;
                        if (! t.matches('input[type="checkbox"][name="directories[]"]')) return;

                        var item = t.closest('.v-tree-item');
                        if (! item) return;

                        // Synthesised clicks from a parent's cascade — let the
                        // parent's own handler do the EXPANDED / COLLAPSED
                        // bookkeeping. Just re-apply class state here.
                        if (propagating) {
                            applyAttrs();
                            return;
                        }

                        var hasDescendants = item.querySelector('.v-tree-item') !== null;

                        if (hasDescendants) {
                            var desired = t.checked;
                            propagating = true;
                            try {
                                var descendantCheckboxes = item.querySelectorAll(
                                    ':scope .v-tree-item input[type="checkbox"][name="directories[]"]'
                                );
                                for (var j = 0; j < descendantCheckboxes.length; j++) {
                                    var cb = descendantCheckboxes[j];
                                    if (cb === t) continue;
                                    if (cb.checked !== desired) {
                                        cb.click();
                                    }
                                }
                            } finally {
                                propagating = false;
                            }
                        }

                        if (t.checked) {
                            markAncestorsExpanded(item);
                            if (hasDescendants) {
                                markSubtreeExpanded(item);
                            }
                        } else if (hasDescendants) {
                            // Unchecking a node with descendants (cascade-down)
                            // collapses ONLY that node — siblings and ancestors
                            // keep their own expansion state.
                            item.setAttribute(COLLAPSED_ATTR, '1');
                            item.removeAttribute(EXPANDED_ATTR);
                        }
                        // Leaf uncheck: no expansion changes, only the box flips.

                        applyAttrs();
                    });

                    var treeObserver = new MutationObserver(function () {
                        if (syncing) return;
                        applyAttrs();
                    });

                    treeObserver.observe(r, {
                        attributes: true,
                        attributeFilter: ['class'],
                        childList: true,
                        subtree: true,
                    });
                }

                function init() {
                    attachInteractionTrackers();

                    var bodyObserver = new MutationObserver(function () {
                        attachInteractionTrackers();
                        if (! syncing) applyAttrs();
                    });

                    bodyObserver.observe(document.body, {
                        childList: true,
                        subtree: true,
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();

            // All-directories toggle: show/hide the directory tree
            (function () {
                function syncTreeVisibility() {
                    var toggle = document.getElementById('dam-all-directories-toggle');
                    var tree = document.getElementById('dam-directory-tree-wrapper');
                    if (! toggle || ! tree) return;
                    tree.style.display = toggle.checked ? 'none' : '';
                }

                function attachToggle() {
                    var toggle = document.getElementById('dam-all-directories-toggle');
                    if (! toggle || toggle.dataset.damToggleAttached) return;
                    toggle.dataset.damToggleAttached = '1';
                    toggle.addEventListener('change', syncTreeVisibility);
                    syncTreeVisibility();
                }

                var obs = new MutationObserver(attachToggle);
                obs.observe(document.body, { childList: true, subtree: true });
                attachToggle();
            })();
        </script>
    @endpush
@endif
