/* global toolbar, pickerToggle, pickerInsert */

if (typeof window.toolbar !== 'undefined') {
    toolbar[toolbar.length] = {
        type: 'cellbg',
        title: 'Cell background',
        icon: '../../plugins/cellbg/images/cellbg.png',
        list: {
            'Yellow': 'yellow',
            'Red': 'red',
            'Orange': 'orange',
            'Salmon': 'salmon',
            'Pink': 'pink',
            'Plum': 'plum',
            'Purple': 'purple',
            'Fuchsia': 'fuchsia',
            'Silver': 'silver',
            'Aqua': 'aqua',
            'Teal': 'teal',
            'Cornflower': '#6495ed',
            'Sky Blue': 'skyblue',
            'Aquamarine': 'aquamarine',
            'Pale Green': 'palegreen',
            'Lime': 'lime',
            'Green': 'green',
            'Olive': 'olive'
        }
    };
}

/**
 * Create the color picker for the cellbg toolbar button
 *
 * @param {jQuery} $btn the toolbar button toggling the picker
 * @param {Object} props button properties, list holds the colors keyed by their name
 * @param {string} edid ID of the editor textarea
 * @returns {string} ID of the created picker
 */
function addBtnActionCellbg($btn, props, edid) {
    const picker = document.createElement('div');
    picker.id = 'picker_plugin_cellbg';
    picker.className = 'picker a11y';
    picker.hidden = true;
    picker.style.position = 'absolute';

    for (const [name, color] of Object.entries(props.list)) {
        const swatch = document.createElement('button');
        swatch.type = 'button';
        swatch.className = 'pickerbutton';
        swatch.title = name;
        swatch.setAttribute('aria-controls', edid);
        swatch.style.height = '2em';
        swatch.style.padding = '1em';
        swatch.style.backgroundColor = color;
        swatch.style.backgroundImage = 'none'; // templates tend to style buttons with a gradient
        swatch.addEventListener('click', () => pickerInsert(`@${color}:`, edid));
        picker.appendChild(swatch);
    }
    document.body.appendChild(picker);

    $btn[0].addEventListener('click', (event) => {
        pickerToggle(picker.id, $btn);
        event.preventDefault();
    });

    return picker.id;
}
