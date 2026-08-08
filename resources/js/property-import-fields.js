/** Campos editables del modal de importación, en el orden en que se revisan. */
export const IMPORT_FIELDS = [
    { name: 'title', label: 'Título', type: 'text', wide: true, required: true },
    { name: 'operation', label: 'Operación', type: 'select', required: true,
        options: { venta: 'Venta', alquiler: 'Alquiler' } },
    { name: 'type', label: 'Tipo', type: 'select', required: true,
        options: { departamento: 'Departamento', casa: 'Casa', oficina: 'Oficina / local', terreno: 'Terreno' } },
    { name: 'district', label: 'Distrito', type: 'text', required: true },
    { name: 'currency', label: 'Moneda', type: 'select', required: true,
        options: { USD: 'Dólares (US$)', PEN: 'Soles (S/)' } },
    { name: 'price', label: 'Precio', type: 'number', step: '0.01', required: true },
    { name: 'area', label: 'Área (m²)', type: 'number', step: '0.01', required: true },
    { name: 'bedrooms', label: 'Dormitorios', type: 'number' },
    { name: 'bathrooms', label: 'Baños', type: 'number', step: '0.5' },
    { name: 'address', label: 'Ubicación', type: 'text', wide: true },
    { name: 'latitude', label: 'Latitud', type: 'number', step: 'any' },
    { name: 'longitude', label: 'Longitud', type: 'number', step: 'any' },
    { name: 'description', label: 'Descripción', type: 'textarea', wide: true },
];

const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));

const control = (field, value) => {
    if (field.type === 'textarea') {
        return `<textarea rows="5" data-import-field="${field.name}">${escape(value)}</textarea>`;
    }
    if (field.type === 'select') {
        const options = Object.entries(field.options)
            .map(([key, label]) => `<option value="${key}"${key === value ? ' selected' : ''}>${label}</option>`)
            .join('');
        return `<select data-import-field="${field.name}">${options}</select>`;
    }
    const step = field.step ? ` step="${field.step}"` : '';
    return `<input type="${field.type}"${step} value="${escape(value)}" data-import-field="${field.name}">`;
};

/** Cada campo trae su casilla: lo que se desmarca no viaja al guardar. */
export function renderFields(container, data) {
    container.innerHTML = IMPORT_FIELDS.map((field) => {
        const value = data[field.name] ?? '';
        const empty = value === '' || value === null;
        const checked = field.required || ! empty ? ' checked' : '';
        return `<label class="import-field${field.wide ? ' import-field-wide' : ''}">
            <span class="import-field-head">
                <input type="checkbox" data-import-use="${field.name}"${checked}${field.required ? ' disabled' : ''}>
                <span>${field.label}${field.required ? ' *' : ''}</span>
            </span>
            ${control(field, value)}
        </label>`;
    }).join('');
}

/** @returns {{values: object, missing: string[]}} */
export function collectFields(container) {
    const values = {};
    const missing = [];
    IMPORT_FIELDS.forEach((field) => {
        const use = container.querySelector(`[data-import-use="${field.name}"]`);
        const input = container.querySelector(`[data-import-field="${field.name}"]`);
        if (! input || (! field.required && ! use?.checked)) return;
        const value = input.value.trim();
        if (field.required && value === '') {
            missing.push(field.label);
            return;
        }
        if (value !== '') values[field.name] = value;
    });
    return { values, missing };
}
