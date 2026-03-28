define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {

    const selectors = {
        root: '.local-flatarchive',
        tree: '[data-region="tree"]',
        status: '[data-region="status"]',
        hint: '[data-region="hint"]',
        selectAll: '[data-action="selectall"]',
        deselectAll: '[data-action="deselectall"]',
        archive: '[data-action="archive"]',
        download: '[data-action="download"]',
    };

    const state = {
        courseid: null,
    };

    function setStatus(root, text) {
        root.querySelector(selectors.status).textContent = text;
    }

    function setHint(root, text) {
        root.querySelector(selectors.hint).textContent = text;
    }

    function renderTree(root, sections) {
        const container = root.querySelector(selectors.tree);
        container.innerHTML = '';

        Str.get_string('hidden', 'local_flatarchive').then(hiddenLabel => {

            sections.forEach(section => {

                const title = document.createElement('div');
                title.className = 'fa-section-title';
                title.textContent = section.name;
                if (!section.visible) {
                    title.classList.add('text-muted');
                }
                container.appendChild(title);

                section.modules.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'fa-item';

                    if (!item.visible) {
                        row.classList.add('text-muted');
                    }

                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.dataset.cmid = item.cmid;

                    const label = document.createElement('span');
                    label.textContent = item.name;

                    if (!item.visible) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-secondary ms-2';
                        badge.textContent = hiddenLabel;
                        label.appendChild(badge);
                    }

                    row.appendChild(cb);
                    row.appendChild(label);
                    container.appendChild(row);
                });
            });
        });
    }

    function getSelectedCmids(root) {
        const checkboxes = root.querySelectorAll(`${selectors.tree} input[type="checkbox"][data-cmid]`);
        const selected = [];
        checkboxes.forEach(cb => {
            if (cb.checked) {
                selected.push(parseInt(cb.dataset.cmid, 10));
            }
        });
        return selected;
    }

    function setAll(root, checked) {
        const checkboxes = root.querySelectorAll(`${selectors.tree} input[type="checkbox"][data-cmid]`);
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });
    }

    async function loadTree(root) {
        setHint(root, '...');

        try {
            const requests = Ajax.call([{
                methodname: 'local_flatarchive_get_tree',
                args: {courseid: state.courseid}
            }]);

            const result = await requests[0];
            renderTree(root, result.sections || []);
            setHint(root, '');

        } catch (e) {
            const msg = await Str.get_string('backendnotready', 'local_flatarchive');
            setHint(root, msg);
        }
    }

    async function init(params) {
        state.courseid = params.courseid;

        const root = document.querySelector(selectors.root);
        if (!root) {
            return;
        }

        setStatus(root, await Str.get_string('status_idle', 'local_flatarchive'));
        await loadTree(root);

        root.querySelector(selectors.selectAll).addEventListener('click', () => setAll(root, true));
        root.querySelector(selectors.deselectAll).addEventListener('click', () => setAll(root, false));

        root.querySelector(selectors.archive).addEventListener('click', async () => {
            const cmids = getSelectedCmids(root);

            if (cmids.length === 0) {
                Notification.alert(
                    await Str.get_string('flatarchive', 'local_flatarchive'),
                    await Str.get_string('nothingselected', 'local_flatarchive')
                );
                return;
            }

            Notification.alert(
                await Str.get_string('flatarchive', 'local_flatarchive'),
                'Sélection enregistrée. Étape suivante : start_job.'
            );
        });
    }

    return {init: init};
});
