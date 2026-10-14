/**
 * Populate the editable CSS field only on an explicit preset change.
 * @module local_flatarchive/appearance
 * @copyright 2026 Edunover
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const init = function(presets) {
        const select = document.getElementById('id_s_local_flatarchive_stylepreset');
        const field = document.getElementById('id_s_local_flatarchive_customcss');
        if (!select || !field || select.dataset.faInitialized) {
            return;
        }
        select.dataset.faInitialized = 'true';
        select.addEventListener('change', () => {
            if (Object.prototype.hasOwnProperty.call(presets, select.value)) {
                field.value = presets[select.value];
                field.dispatchEvent(new Event('input', {bubbles: true}));
                field.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });
    };
    return {init: init};
});
