@if ($damEnabled)
    <div
        id="dam-directory-permissions-tab"
        v-if="permission_type == 'custom' || selected_permission_type == 'custom'"
        class="flex flex-col gap-2 flex-1 max-xl:flex-auto"
        data-inherit-children="{{ $inheritChildren ? '1' : '0' }}"
        data-explicit-grants="{{ json_encode($grantedIds) }}"
        data-route-directory="{{ $routeDirectory }}"
        data-route-directory-paths="{{ $routeDirectoryPaths }}"
        data-route-directory-children="{{ $routeDirectoryChildren }}"
        data-route-directory-descendants="{{ $routeDirectoryDescendants }}"
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
                    class="sr-only peer"
                    {{ $allDirectories ? 'checked' : '' }}
                />
                <span class="icon-checkbox-normal text-2xl peer-checked:icon-checkbox-check peer-checked:text-violet-700 cursor-pointer"></span>
                <span class="font-semibold text-gray-800 dark:text-white text-sm">
                    @lang('dam::app.admin.permissions.all-directories')
                </span>
            </label>

            {{-- Inherit Sub-directories toggle --}}
            <label class="flex items-center gap-2 cursor-pointer mb-4 select-none" id="dam-inherit-children-label">
                <input
                    type="checkbox"
                    id="dam-inherit-children-toggle"
                    name="dam_inherit_children"
                    value="1"
                    class="sr-only peer"
                    {{ $inheritChildren ? 'checked' : '' }}
                />
                <span class="icon-checkbox-normal text-2xl peer-checked:icon-checkbox-check peer-checked:text-violet-700 peer-disabled:opacity-70 peer-disabled:cursor-not-allowed cursor-pointer"></span>
                <span class="font-semibold text-gray-800 dark:text-white text-sm">
                    @lang('dam::app.admin.permissions.inherit-children')
                </span>
            </label>

            {{-- Directory tree (hidden when all-directories is checked) --}}
            <div id="dam-directory-tree-wrapper" {{ $allDirectories ? 'style="display:none"' : '' }}>
                {{-- Lazy-loading tree mounts here --}}
                <div id="dam-perm-tree-root"></div>

                {{-- Inherit note: shown only while the inherit toggle is on --}}
                <p
                    id="dam-perm-inherit-note"
                    class="text-sm text-gray-600 dark:text-slate-300 mt-2"
                    style="display: {{ $inheritChildren ? '' : 'none' }};"
                >
                    @lang('dam::app.admin.permissions.inherit-children-note')
                </p>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            var DAM_PERM_I18N = {
                loading:       @json(__('dam::app.admin.permissions.loading')),
                empty:         @json(__('dam::app.admin.permissions.empty')),
                error:         @json(__('dam::app.admin.permissions.error')),
                retry:         @json(__('dam::app.admin.permissions.retry')),
                grantsWarning: @json(__('dam::app.admin.permissions.grants-load-warning')),
                inheritNote:   @json(__('dam::app.admin.permissions.inherit-children-note')),
            };
        </script>

        <script type="module">
            //
            // Lazy-loading DAM directory permission tree.
            //
            // Replaces the legacy x-admin::tree.view which loaded every directory
            // up-front (TLE at 50K dirs). Loads roots + ancestor paths of granted
            // directories on mount, and fetches each level's children on demand
            // when its chevron is expanded.
            //
            (function () {
                var I18N = window.DAM_PERM_I18N || {};

                // -- per-tab component state ------------------------------------
                // The role create/edit page recreates the tab DOM via v-if every
                // time the permission type flips. We key state to the wrapper
                // element so a fresh tab gets a fresh component.
                var ATTACHED_FLAG = 'damPermLazyAttached';

                function csrfToken() {
                    // XSRF-TOKEN cookie is refreshed by Laravel on every response and
                    // stays current in SPA navigation (no full page reload needed).
                    // The meta[name="csrf-token"] tag goes stale after the first Vue
                    // navigation and causes 419s on subsequent POST calls.
                    var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
                    if (match) return decodeURIComponent(match[1]);
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    return meta ? meta.content : '';
                }

                function getJson(url) {
                    return fetch(url, {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    }).then(function (res) {
                        if (! res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    });
                }

                function postJson(url, body) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': csrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    }).then(function (res) {
                        if (! res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    });
                }

                function escapeHtml(str) {
                    return String(str == null ? '' : str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                }

                //
                // Component factory. One instance per tab wrapper.
                //
                function createComponent(root) {
                    var mount = root.querySelector('#dam-perm-tree-root');
                    if (! mount) return null;

                    var routeDirectory            = root.dataset.routeDirectory || '';
                    var routeDirectoryPaths       = root.dataset.routeDirectoryPaths || '';
                    var routeDirectoryChildren    = root.dataset.routeDirectoryChildren || '';
                    var routeDirectoryDescendants = root.dataset.routeDirectoryDescendants || '';

                    var grantedIds = [];
                    try {
                        grantedIds = JSON.parse(root.dataset.explicitGrants || '[]') || [];
                    } catch (e) {
                        grantedIds = [];
                    }
                    grantedIds = grantedIds.map(function (v) { return parseInt(v, 10); })
                        .filter(function (v) { return ! isNaN(v); });
                    var grantedSet = {};
                    grantedIds.forEach(function (id) { grantedSet[id] = true; });

                    // nodes: id -> { id, name, parentId, hasChildren, childrenLoaded, expanded }
                    var nodes = {};
                    // childrenIndex: parentId (or 'root') -> [childId, ...]
                    var childIndex = {};
                    // explicit user-selected ids (Set semantics via plain object).
                    var selected = {};
                    // ids expanded as ancestors of granted nodes (not user-checked).
                    var ancestorExpand = {};
                    // ids with a descendant-cascade fetch in-flight (prevents double-trigger).
                    var pendingCascade = {};

                    // Snapshot of `selected` taken when inherit toggles ON, so
                    // toggling OFF restores the in-progress selection rather than
                    // the original DB grants.
                    var inheritSnapshot = null;

                    function childKey(parentId) {
                        return parentId == null ? 'root' : String(parentId);
                    }

                    // Upsert a raw node from any API response. Deduplicates by id:
                    // an existing node keeps its loaded/expanded flags, only name/
                    // has_children/parent get refreshed.
                    function upsert(raw) {
                        var id = parseInt(raw.id, 10);
                        if (isNaN(id)) return;

                        var parentId = raw.parent_id == null ? null : parseInt(raw.parent_id, 10);
                        if (parentId != null && isNaN(parentId)) parentId = null;

                        var hasChildren = !! raw.has_children;

                        if (nodes[id]) {
                            nodes[id].name = raw.name;
                            nodes[id].hasChildren = hasChildren;
                            // Don't downgrade a known parent to null on a later
                            // partial payload.
                            if (parentId != null) nodes[id].parentId = parentId;
                        } else {
                            nodes[id] = {
                                id: id,
                                name: raw.name,
                                parentId: parentId,
                                hasChildren: hasChildren,
                                childrenLoaded: false,
                                expanded: false,
                            };
                        }

                        // Maintain the child index (dedup).
                        var key = childKey(nodes[id].parentId);
                        if (! childIndex[key]) childIndex[key] = [];
                        if (childIndex[key].indexOf(id) === -1) childIndex[key].push(id);

                        // Roots/ancestor responses ship a nested `children` array
                        // (one shallow level). Flatten it in.
                        // Only mark loaded when children were actually provided (non-empty)
                        // or the node is a leaf — an empty array from the shallow response
                        // does NOT mean grandchildren have been fetched.
                        if (Array.isArray(raw.children)) {
                            raw.children.forEach(upsert);
                            if (raw.children.length > 0 || ! hasChildren) {
                                nodes[id].childrenLoaded = true;
                            }
                        }
                    }

                    function markGranted() {
                        Object.keys(nodes).forEach(function (idStr) {
                            var id = parseInt(idStr, 10);
                            if (grantedSet[id]) selected[id] = true;
                        });
                    }

                    // Ancestor nodes from the paths response (those NOT directly
                    // granted) get expanded so the granted leaf is revealed.
                    function markAncestorsExpanded() {
                        grantedIds.forEach(function (id) {
                            var node = nodes[id];
                            if (! node) return;
                            var p = node.parentId;
                            while (p != null && nodes[p]) {
                                ancestorExpand[p] = true;
                                nodes[p].expanded = true;
                                p = nodes[p].parentId;
                            }
                        });
                    }

                    // -- rendering ----------------------------------------------

                    function renderNode(id) {
                        var node = nodes[id];
                        if (! node) return '';

                        var hasKids = node.hasChildren
                            || (childIndex[childKey(id)] && childIndex[childKey(id)].length > 0);
                        var activeClass = (hasKids && node.expanded) ? ' active' : '';
                        var itemClass = 'v-tree-item inline-block w-full'
                            + ' [&>.v-tree-item]:ltr:pl-6 [&>.v-tree-item]:rtl:pr-6'
                            + ' [&>.v-tree-item]:hidden [&.active>.v-tree-item]:block'
                            + activeClass;

                        var chevronHtml;
                        if (hasKids) {
                            var chevronIcon = node.expanded ? 'icon-chevron-down' : 'icon-chevron-right';
                            chevronHtml = '<i class="' + chevronIcon
                                + ' text-xl rounded-md cursor-pointer transition-all'
                                + ' hover:bg-violet-50 dark:hover:bg-cherry-800"'
                                + ' data-dam-chevron="' + id + '"></i>';
                        } else {
                            chevronHtml = '<i class="text-xl" style="visibility:hidden"></i>';
                        }

                        var folderIcon = hasKids ? 'icon-folder' : 'icon-attribute';
                        var checkedAttr = selected[id] ? ' checked' : '';

                        var html = '<div class="' + itemClass + '" data-id="' + id + '">'
                            + '<div class="flex items-center">'
                            +   chevronHtml
                            +   '<i class="' + folderIcon + ' text-2xl cursor-pointer"></i>'
                            +   '<label class="inline-flex gap-2.5 w-max p-1.5 items-center cursor-pointer select-none group">'
                            +     '<input type="checkbox" class="hidden peer dam-perm-cb" data-id="' + id + '"' + checkedAttr + ' />'
                            +     '<span class="icon-checkbox-normal rounded-md text-2xl cursor-pointer peer-checked:icon-checkbox-check peer-checked:text-violet-700"></span>'
                            +     '<div class="text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-white">'
                            +       escapeHtml(node.name)
                            +     '</div>'
                            +   '</label>'
                            + '</div>'
                            + renderChildren(id)
                            + '</div>';

                        return html;
                    }

                    function renderChildren(parentId) {
                        var ids = childIndex[childKey(parentId)] || [];
                        var out = '';
                        for (var i = 0; i < ids.length; i++) {
                            out += renderNode(ids[i]);
                        }
                        return out;
                    }

                    function renderTree() {
                        var rootIds = childIndex['root'] || [];

                        if (rootIds.length === 0) {
                            mount.innerHTML = '<p class="text-sm text-gray-600 dark:text-slate-300">'
                                + escapeHtml(I18N.empty) + '</p>';
                            return;
                        }

                        var html = '<div class="dam-perm-tree">';
                        for (var i = 0; i < rootIds.length; i++) {
                            html += renderNode(rootIds[i]);
                        }
                        html += '</div>';
                        mount.innerHTML = html;
                    }

                    function renderLoading() {
                        mount.innerHTML = '<p class="text-sm text-gray-600 dark:text-slate-300">'
                            + escapeHtml(I18N.loading) + '</p>';
                    }

                    function renderError(onRetry) {
                        mount.innerHTML = ''
                            + '<div class="flex flex-col gap-2">'
                            +   '<p class="text-sm text-red-600">' + escapeHtml(I18N.error) + '</p>'
                            +   '<button type="button" class="dam-perm-retry primary-button max-w-max">'
                            +       escapeHtml(I18N.retry) + '</button>'
                            + '</div>';
                        var btn = mount.querySelector('.dam-perm-retry');
                        if (btn) btn.addEventListener('click', onRetry);
                    }

                    function showGrantsWarning(onRetryGrants) {
                        // Inserted above the tree; cleared on the next full render.
                        var banner = document.createElement('div');
                        banner.className = 'dam-perm-grants-warning flex items-center justify-between gap-2 '
                            + 'p-2 mb-2 rounded bg-yellow-100 text-yellow-800 text-sm';
                        banner.innerHTML = '<span>' + escapeHtml(I18N.grantsWarning) + '</span>';

                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'dam-perm-retry-grants secondary-button max-w-max';
                        btn.textContent = I18N.retry;
                        btn.addEventListener('click', function () {
                            banner.remove();
                            onRetryGrants();
                        });
                        banner.appendChild(btn);

                        mount.parentNode.insertBefore(banner, mount);
                    }

                    function clearGrantsWarning() {
                        var existing = mount.parentNode.querySelector('.dam-perm-grants-warning');
                        if (existing) existing.remove();
                    }

                    function noteEl() {
                        return root.querySelector('#dam-perm-inherit-note');
                    }

                    // -- data loading -------------------------------------------

                    function ingest(payload) {
                        var data = (payload && payload.data) ? payload.data : [];
                        if (Array.isArray(data)) data.forEach(upsert);
                    }

                    function fetchRoots() {
                        return getJson(routeDirectory);
                    }

                    function fetchPaths() {
                        if (grantedIds.length === 0) {
                            return Promise.resolve({ data: [] });
                        }
                        return postJson(routeDirectoryPaths, { ids: grantedIds });
                    }

                    function finalizeMount() {
                        markGranted();
                        markAncestorsExpanded();
                        renderTree();
                        if (isInherit()) {
                            applyInheritCascade();
                        }
                        // markAncestorsExpanded() sets expanded=true but paths response
                        // returns children:[] so childrenLoaded stays false.  Without a
                        // pre-fetch, the first collapse+re-expand would call the children
                        // API and reveal siblings not present in the initial render —
                        // an inconsistent visual state.  Pre-fetch every auto-expanded
                        // ancestor so the page load and the expanded view are the same.
                        var prefetchIds = Object.keys(ancestorExpand).filter(function (idStr) {
                            var node = nodes[parseInt(idStr, 10)];
                            return node && ! node.childrenLoaded;
                        }).map(Number);

                        function onAllPrefetchesDone() {
                            if (isInherit() && inheritSnapshot === null) {
                                // Snapshot after all sibling children are loaded so
                                // toggling inherit OFF restores the full visual state.
                                inheritSnapshot = Object.keys(selected).map(Number);
                            }
                        }

                        if (prefetchIds.length === 0) {
                            onAllPrefetchesDone();
                        } else {
                            var remaining = prefetchIds.length;
                            prefetchIds.forEach(function (id) {
                                expandNode(id, function () {
                                    remaining -= 1;
                                    if (remaining === 0) onAllPrefetchesDone();
                                });
                            });
                        }
                    }

                    // Full mount: paths (if any) + roots. Retry re-runs both.
                    function loadAll() {
                        clearGrantsWarning();
                        renderLoading();

                        var pathsResult = null;
                        var pathsFailed = false;

                        var pathsPromise = fetchPaths().then(function (r) {
                            pathsResult = r;
                        }).catch(function (err) {
                            pathsFailed = true;
                            console.error('[DAM permissions] fetchPaths failed:', err && err.message ? err.message : err);
                        });

                        var rootsPromise = fetchRoots();

                        Promise.all([
                            pathsPromise,
                            rootsPromise.then(function (r) { return { ok: true, data: r }; })
                                        .catch(function () { return { ok: false }; }),
                        ]).then(function (results) {
                            var rootsRes = results[1];

                            // Roots failed → hard error, retry both.
                            if (! rootsRes.ok) {
                                renderError(loadAll);
                                return;
                            }

                            // Seed with paths response first, then merge roots.
                            if (pathsResult) ingest(pathsResult);
                            ingest(rootsRes.data);

                            finalizeMount();

                            // Roots succeeded but grants/paths failed → soft warning,
                            // retry grants only.
                            if (pathsFailed && grantedIds.length > 0) {
                                showGrantsWarning(retryGrants);
                            }
                        });
                    }

                    // Retry grants only: re-run paths, merge, re-render preserving
                    // already-loaded roots/children.
                    function retryGrants() {
                        fetchPaths().then(function (r) {
                            ingest(r);
                            markGranted();
                            markAncestorsExpanded();
                            renderTree();
                            if (isInherit()) applyInheritCascade();
                        }).catch(function () {
                            showGrantsWarning(retryGrants);
                        });
                    }

                    // Expand a node: fetch its children if not loaded, then reveal.
                    // onComplete (optional) is called after the tree is re-rendered
                    // so callers can chain work that depends on the loaded children.
                    function expandNode(id, onComplete) {
                        var node = nodes[id];
                        if (! node) {
                            if (onComplete) onComplete();
                            return;
                        }

                        node.expanded = true;

                        if (node.childrenLoaded) {
                            renderTree();
                            // Cascade after the DOM exists so freshly-revealed
                            // descendants of a checked node get visually checked.
                            if (isInherit()) applyInheritCascade();
                            if (onComplete) onComplete();
                            return;
                        }

                        var url = routeDirectoryChildren.replace('__ID__', id);
                        getJson(url).then(function (r) {
                            var data = (r && r.data) ? r.data : [];
                            if (Array.isArray(data)) data.forEach(upsert);
                            node.childrenLoaded = true;

                            // Children that are themselves granted become checked.
                            data.forEach(function (raw) {
                                var cid = parseInt(raw.id, 10);
                                if (! isNaN(cid) && grantedSet[cid]) selected[cid] = true;
                            });

                            renderTree();

                            // If inherit is on, newly loaded descendants of a checked
                            // node should be checked too — run after renderTree so the
                            // new children are in the DOM.
                            if (isInherit()) applyInheritCascade();
                            if (onComplete) onComplete();
                        }).catch(function () {
                            // Revert expansion state on failure.
                            node.expanded = false;
                            renderTree();
                            if (onComplete) onComplete();
                        });
                    }

                    function collapseNode(id) {
                        var node = nodes[id];
                        if (! node) return;
                        node.expanded = false;
                        renderTree();
                    }

                    // -- selection / cascade ------------------------------------

                    function isInherit() {
                        var t = document.getElementById('dam-inherit-children-toggle');
                        return !! (t && t.checked);
                    }

                    function descendantIds(id) {
                        // Walk loaded descendants via the child index.
                        var out = [];
                        var stack = (childIndex[childKey(id)] || []).slice();
                        while (stack.length) {
                            var cur = stack.pop();
                            out.push(cur);
                            var kids = childIndex[childKey(cur)] || [];
                            for (var i = 0; i < kids.length; i++) stack.push(kids[i]);
                        }
                        return out;
                    }

                    // When inherit is ON, visually-check (without adding to the
                    // explicit Set) all loaded descendants of every checked node.
                    function applyInheritCascade() {
                        if (! isInherit()) return;
                        var cbs = mount.querySelectorAll('input.dam-perm-cb');
                        var checkedIds = {};
                        for (var i = 0; i < cbs.length; i++) {
                            var id = parseInt(cbs[i].dataset.id, 10);
                            if (selected[id]) checkedIds[id] = true;
                        }
                        Object.keys(checkedIds).forEach(function (idStr) {
                            descendantIds(parseInt(idStr, 10)).forEach(function (did) {
                                var box = mount.querySelector('input.dam-perm-cb[data-id="' + did + '"]');
                                if (box) box.checked = true;
                            });
                        });
                    }

                    function onInheritOn() {
                        // Snapshot the current explicit selection.
                        inheritSnapshot = Object.keys(selected).map(Number);
                        applyInheritCascade();
                        var note = noteEl();
                        if (note) note.style.display = '';
                    }

                    function onInheritOff() {
                        // Rebuild the explicit Set from the restore list so the
                        // submitted `selected` and the visual checkboxes agree.
                        var restore = inheritSnapshot
                            ? inheritSnapshot
                            : grantedIds.slice();
                        inheritSnapshot = null;

                        selected = {};
                        restore.forEach(function (id) { selected[id] = true; });

                        // Update checkboxes visually from the rebuilt Set.
                        var cbs = mount.querySelectorAll('input.dam-perm-cb');
                        for (var i = 0; i < cbs.length; i++) {
                            var id = parseInt(cbs[i].dataset.id, 10);
                            cbs[i].checked = !! selected[id];
                        }

                        var note = noteEl();
                        if (note) note.style.display = 'none';
                    }

                    // -- event wiring -------------------------------------------

                    // Chevron + checkbox events (delegated, survive re-render).
                    mount.addEventListener('click', function (e) {
                        var chevron = e.target.closest ? e.target.closest('[data-dam-chevron]') : null;
                        if (! chevron || ! mount.contains(chevron)) return;
                        var id = parseInt(chevron.dataset.damChevron, 10);
                        var node = nodes[id];
                        if (! node || ! node.hasChildren) return;
                        if (node.expanded) {
                            collapseNode(id);
                        } else {
                            expandNode(id);
                        }
                    });

                    mount.addEventListener('change', function (e) {
                        var t = e.target;
                        if (! t || ! t.classList || ! t.classList.contains('dam-perm-cb')) return;
                        var id = parseInt(t.dataset.id, 10);
                        if (isNaN(id)) return;

                        if (pendingCascade[id]) return;

                        var checking = t.checked;

                        // Apply to clicked node immediately.
                        if (checking) {
                            selected[id] = true;
                        } else {
                            delete selected[id];
                        }

                        // Optimistic instant cascade for already-loaded descendants.
                        descendantIds(id).forEach(function (did) {
                            if (checking) { selected[did] = true; } else { delete selected[did]; }
                            var box = mount.querySelector('input.dam-perm-cb[data-id="' + did + '"]');
                            if (box) box.checked = checking;
                        });

                        if (isInherit()) applyInheritCascade();

                        // Background fetch: get ALL descendant IDs (including unloaded
                        // branches) so every level is included in the form submission
                        // and visible checkboxes update without expanding the tree.
                        if (! routeDirectoryDescendants) return;

                        pendingCascade[id] = true;
                        var rowContent = t.closest && t.closest('.v-tree-item')
                            ? t.closest('.v-tree-item').querySelector('.flex.items-center')
                            : null;
                        var spinner = document.createElement('span');
                        spinner.className = 'dam-perm-spinner inline-block h-3.5 w-3.5 animate-spin rounded-full ml-2 align-middle flex-shrink-0';
                        spinner.style.cssText = 'border:2px solid #7c3aed;border-right-color:transparent;';
                        if (rowContent) rowContent.appendChild(spinner);

                        getJson(routeDirectoryDescendants.replace('__ID__', id)).then(function (r) {
                            var ids = (r && Array.isArray(r.data)) ? r.data : [];
                            ids.forEach(function (did) {
                                did = parseInt(did, 10);
                                if (isNaN(did)) return;
                                if (checking) { selected[did] = true; } else { delete selected[did]; }
                                var box = mount.querySelector('input.dam-perm-cb[data-id="' + did + '"]');
                                if (box) box.checked = checking;
                            });
                            if (isInherit()) applyInheritCascade();
                        }).catch(function () {
                            // Silent — client-side cascade + job expansion cover this case.
                        }).then(function () {
                            spinner.remove();
                            delete pendingCascade[id];
                        });
                    });

                    // Inherit toggle.
                    var inheritToggle = document.getElementById('dam-inherit-children-toggle');
                    if (inheritToggle && ! inheritToggle.dataset.damPermInheritBound) {
                        inheritToggle.dataset.damPermInheritBound = '1';
                        inheritToggle.addEventListener('change', function () {
                            if (inheritToggle.checked) {
                                onInheritOn();
                            } else {
                                onInheritOff();
                            }
                        });
                    }

                    // Form submit: inject hidden directories[] inputs from the
                    // explicit Set. Capture phase so the inputs exist before the
                    // request is built.
                    var form = root.closest('form');
                    if (form && ! root.dataset.damPermSubmitBound) {
                        root.dataset.damPermSubmitBound = '1';
                        form.addEventListener('submit', function () {
                            // Remove stale directories[] inputs from a prior submit.
                            var stale = form.querySelectorAll('input[type="hidden"][name="directories[]"].dam-perm-submit');
                            for (var i = 0; i < stale.length; i++) stale[i].remove();

                            Object.keys(selected).forEach(function (idStr) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'directories[]';
                                input.value = idStr;
                                input.className = 'dam-perm-submit';
                                form.appendChild(input);
                            });
                        }, true);
                    }

                    return { loadAll: loadAll };
                }

                function attach(root) {
                    if (! root || root.dataset[ATTACHED_FLAG]) return;
                    root.dataset[ATTACHED_FLAG] = '1';

                    var component = createComponent(root);
                    if (component) component.loadAll();
                }

                function scan() {
                    var root = document.getElementById('dam-directory-permissions-tab');
                    if (root) attach(root);
                }

                function init() {
                    scan();
                    // The tab is created by Vue v-if after the form mounts — watch
                    // the body so we attach as soon as it appears.
                    var obs = new MutationObserver(scan);
                    obs.observe(document.body, { childList: true, subtree: true });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();

            // All-directories toggle: show/hide the directory tree (unchanged).
            (function () {
                function syncTreeVisibility() {
                    var toggle = document.getElementById('dam-all-directories-toggle');
                    var tree = document.getElementById('dam-directory-tree-wrapper');
                    var inheritLabel = document.getElementById('dam-inherit-children-label');
                    if (! toggle || ! tree) return;
                    tree.style.display = toggle.checked ? 'none' : '';
                    if (inheritLabel) {
                        // Visually dim and block interaction without disabling the input.
                        // Disabled inputs are excluded from POST, so the inherit value
                        // would be lost on save; pointer-events + opacity gives the same
                        // visual cue while the value is still submitted.
                        inheritLabel.style.opacity       = toggle.checked ? '0.4' : '';
                        inheritLabel.style.pointerEvents = toggle.checked ? 'none' : '';
                        inheritLabel.style.cursor        = toggle.checked ? 'default' : '';
                    }
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
