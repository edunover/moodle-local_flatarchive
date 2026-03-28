/**
 * Flatarchive – Sélecteur de modules à archiver
 *
 * Script NON-AMD, chargé via $PAGE->requires->js()
 *
 * Rôle :
 * - attendre l’injection de window.FLATARCHIVE
 * - appeler get_tree.php
 * - afficher les sections et modules Page (visibles + cachés)
 * - permettre la sélection
 * - déclencher start_job.php
 */

(function waitForConfig() {
console.log('Flatarchive 0.0.7')
    // ==================================================
    // 1. Attente de la config injectée par Moodle
    // ==================================================
    if (!window.FLATARCHIVE || !window.FLATARCHIVE.courseid) {
        setTimeout(waitForConfig, 10);
        return;
    }

    const courseid = window.FLATARCHIVE.courseid;

    console.log('Flatarchive JS chargé – courseid =', courseid);

    const loading = document.querySelector('[data-region="loading"]');
    const treeContainer = document.querySelector('[data-region="tree"]');

    if (!treeContainer) {
        console.error('Flatarchive : conteneur [data-region="tree"] introuvable');
        return;
    }

    // ==================================================
    // 2. Appel AJAX vers get_tree.php
    // ==================================================
    const params = new URLSearchParams();
    params.append('courseid', courseid);
    params.append('sesskey', M.cfg.sesskey);

    if (loading) {
        loading.style.display = 'block';
    }

    fetch(M.cfg.wwwroot + '/local/flatarchive/ajax/get_tree.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })

    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur HTTP get_tree.php (' + response.status + ')');
        }
        return response.json();
    })

    .then(data => {
        if (loading) {
            loading.style.display = 'none';
        }

        if (!data.sections || data.sections.length === 0) {
            console.warn('Flatarchive : aucune section/page trouvée');
            return;
        }

        renderTree(data.sections);
        bindArchiveButton();
    })

    .catch(error => {
        if (loading) {
            loading.style.display = 'none';
        }
        console.error('Flatarchive – erreur AJAX get_tree :', error);
    });

    // ==================================================
    // 3. Rendu HTML de l’arborescence
    // ==================================================
    function renderTree(sections) {

        treeContainer.innerHTML = '';

        sections.forEach((section, index) => {

            // Ligne horizontale entre sections (sauf avant la première)
            if (index > 0) {
                const hr = document.createElement('hr');
                hr.className = 'flatarchive-section-separator';
                treeContainer.appendChild(hr);
            }

            // ---------- Titre de section ----------
            const sectionTitle = document.createElement('h5');
            sectionTitle.textContent = section.name;

            if (!section.visible) {
                sectionTitle.style.opacity = '0.6';

                const hiddenTag = document.createElement('em');
                hiddenTag.style.marginLeft = '0.5em';
                hiddenTag.textContent = ' [caché]';

                sectionTitle.appendChild(hiddenTag);
            }

            treeContainer.appendChild(sectionTitle);

            // ---------- Modules de la section ----------
            section.modules.forEach(module => {

                const row = document.createElement('div');
                row.className = 'fa-item';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = module.cmid;

                const label = document.createElement('span');
                label.textContent = module.name;

                if (!module.visible) {
                    row.style.opacity = '0.5';

                    const hiddenTag = document.createElement('em');
                    hiddenTag.style.marginLeft = '0.5em';
                    hiddenTag.textContent = ' [caché]';

                    label.appendChild(hiddenTag);
                }

                row.appendChild(checkbox);
                row.appendChild(label);
                treeContainer.appendChild(row);
            });
        });
    }

    // ==================================================
    // 4. Sélection utilisateur
    // ==================================================
    function getSelectedCmids() {
        return Array.from(
            document.querySelectorAll('.fa-item input[type="checkbox"]:checked')
        ).map(cb => parseInt(cb.value, 10));
    }

    // ==================================================
    // 5. Démarrage du job d’archivage
    // ==================================================
    function bindArchiveButton() {

        const button = document.querySelector('[data-action="start-archive"]');

        if (!button) {
            console.warn('Flatarchive : bouton start-archive introuvable');
            return;
        }

        button.addEventListener('click', () => {

            const cmids = getSelectedCmids();

            if (cmids.length === 0) {
                alert('Aucun module sélectionné');
                return;
            }

            const params = new URLSearchParams();
            params.append('courseid', courseid);
            params.append('sesskey', M.cfg.sesskey);

            cmids.forEach(id => params.append('cmids[]', id));

            console.log('Flatarchive : démarrage job avec CMIDs', cmids);

            fetch(M.cfg.wwwroot + '/local/flatarchive/ajax/start_job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })

            .then(r => {
                if (!r.ok) {
                    throw new Error('Erreur HTTP start_job (' + r.status + ')');
                }
                return r.json();
            })

            .then(data => {
                console.log('Flatarchive : job créé', data);
                alert('Archivage lancé (job #' + data.jobid + ')');
            })

            .catch(err => {
                console.error('Flatarchive – erreur start_job', err);
                alert('Erreur lors du lancement de l’archivage');
            });
        });
    }

})();
