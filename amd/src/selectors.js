/**
 * Course resource selection and synchronous HTML export.
 *
 * @module local_flatarchive/selectors
 * @copyright 2025 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    const selectors = {
        root: '.local-flatarchive',
        form: '[data-region="archive-form"]',
        tree: '[data-region="tree"]',
        loading: '[data-region="loading"]',
        status: '[data-region="status"]',
        selectAll: '[data-action="selectall"]',
        deselectAll: '[data-action="deselectall"]',
        archive: '[data-action="start-archive"]',
        inputs: 'input[name="cmid[]"]',
        courseInputs: 'input[name="courseoptions[]"]',
    };

    const init = async function(params) {
        const root = document.querySelector(selectors.root);
        if (!root || root.dataset.initialized) {
            return;
        }
        root.dataset.initialized = 'true';
        const form = root.querySelector(selectors.form);
        const tree = root.querySelector(selectors.tree);
        const loading = root.querySelector(selectors.loading);
        const status = root.querySelector(selectors.status);
        const controls = [selectors.selectAll, selectors.deselectAll, selectors.archive].map(s => root.querySelector(s));
        let ready = false;
        let submitting = false;
        let strings;

        const setDisabled = disabled => controls.forEach(control => { control.disabled = disabled; });
        setDisabled(true);
        form.addEventListener('submit', event => {
            if (!ready || submitting) {
                event.preventDefault();
                return;
            }
            if (!form.querySelector(`${selectors.inputs}:checked:not(:disabled), ${selectors.courseInputs}:checked:not(:disabled)`)) {
                event.preventDefault();
                Notification.alert(strings.flatarchive, strings.nothingselected);
                return;
            }
            submitting = true;
            setDisabled(true);
            status.textContent = strings.status_running;
        });

        [selectors.selectAll, selectors.deselectAll].forEach((selector, index) => {
            root.querySelector(selector).addEventListener('click', () => {
                form.querySelectorAll(`${selectors.inputs}:not(:disabled), ${selectors.courseInputs}:not(:disabled)`).forEach(input => { input.checked = index === 0; });
            });
        });

        // A restored browser page must allow another export.
        window.addEventListener('pageshow', event => {
            if (event.persisted && ready) {
                submitting = false;
                setDisabled(false);
                status.textContent = strings.status_idle;
            }
        });

        try {
            const keys = ['flatarchive', 'nothingselected', 'hidden', 'loading', 'status_idle',
                'status_running', 'status_failed', 'noresources', 'unsupportedactivity', 'exportnotpermitted'];
            const values = await Str.get_strings(keys.map(key => ({key: key, component: 'local_flatarchive'})));
            strings = Object.fromEntries(keys.map((key, index) => [key, values[index]]));
            status.textContent = strings.loading;
            const result = await Ajax.call([{
                methodname: 'local_flatarchive_get_tree',
                args: {courseid: params.courseid}
            }])[0];
            tree.textContent = '';
            const addHidden = element => {
                const badge = document.createElement('span');
                badge.className = 'badge badge-secondary ml-2';
                badge.textContent = strings.hidden;
                element.classList.add('text-muted');
                element.appendChild(badge);
            };
            (result.sections || []).forEach(section => {
                const heading = document.createElement('h4');
                heading.className = 'fa-section-title h5';
                heading.textContent = section.name;
                if (!section.visible) {
                    addHidden(heading);
                }
                tree.appendChild(heading);
                section.modules.forEach(item => {
                    const label = document.createElement('label');
                    label.className = 'fa-item';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'cmid[]';
                    checkbox.value = item.cmid;
                    checkbox.disabled = !item.selectable;
                    const title = document.createElement('span');
                    title.textContent = item.name;
                    if (!item.visible) {
                        addHidden(title);
                    }
                    label.appendChild(checkbox);
                    if (item.iconurl) {
                        const icon = document.createElement('img');
                        icon.className = 'icon activityicon fa-module-icon';
                        icon.src = item.iconurl;
                        icon.alt = item.modfullname || '';
                        label.appendChild(icon);
                    }
                    label.appendChild(title);
                    if (checkbox.disabled) {
                        label.classList.add('fa-item-disabled');
                        const reason = document.createElement('span');
                        reason.id = `fa-disabled-${item.cmid}`;
                        reason.className = 'badge badge-secondary fa-export-status';
                        reason.textContent = item.supported ? strings.exportnotpermitted : strings.unsupportedactivity;
                        checkbox.setAttribute('aria-describedby', reason.id);
                        label.appendChild(reason);
                    }
                    tree.appendChild(label);
                });
            });
            ready = Boolean(form.querySelector(`${selectors.inputs}:not(:disabled), ${selectors.courseInputs}:not(:disabled)`));
            status.textContent = ready ? strings.status_idle : strings.noresources;
            setDisabled(!ready);
        } catch (error) {
            status.textContent = strings ? strings.status_failed : '';
            Notification.exception(error);
        } finally {
            loading.hidden = true;
        }
    };
    return {init: init};
});
