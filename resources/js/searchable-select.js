document.querySelectorAll('[data-searchable-select]').forEach((select, selectIndex) => {
    const choices = [...select.options].filter((option) => option.value !== '');
    const wrapper = document.createElement('span');
    const input = document.createElement('input');
    const list = document.createElement('span');
    const listId = `searchable-list-${selectIndex}`;
    const dependency = select.dataset.dependsOn
        ? select.form?.elements.namedItem(select.dataset.dependsOn) : null;
    const normalize = (value) => value.normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    let visible = [];
    let filtered = [];
    let renderedCount = 0;
    let active = -1;

    wrapper.className = 'searchable-combobox';
    input.type = 'text';
    input.autocomplete = 'off';
    input.placeholder = 'Escribe para buscar…';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', listId);
    input.setAttribute('aria-expanded', 'false');
    list.className = 'searchable-options';
    list.id = listId;
    list.setAttribute('role', 'listbox');
    list.hidden = true;
    select.classList.add('searchable-select-source');
    select.insertAdjacentElement('afterend', wrapper);
    wrapper.append(input, list);

    const selectedLabel = () => select.selectedOptions[0]?.value
        ? select.selectedOptions[0].textContent.trim() : '';
    const availableChoices = () => choices.filter((option) => ! dependency
        || option.dataset.optionGroup === dependency.value);
    const close = () => {
        list.hidden = true;
        wrapper.classList.remove('is-open');
        wrapper.closest('.field')?.classList.remove('select-open');
        input.setAttribute('aria-expanded', 'false');
        active = -1;
    };
    const choose = (option) => {
        [...select.options].forEach((choice) => { choice.selected = choice === option; });
        input.value = option.textContent.trim();
        select.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    };
    const highlight = () => list.querySelectorAll('[role=option]').forEach(
        (option, index) => option.classList.toggle('active', index === active)
    );
    const appendChoices = () => {
        const batch = filtered.slice(renderedCount, renderedCount + 10);
        batch.forEach((option) => {
            const item = document.createElement('span');
            item.className = 'searchable-option';
            item.setAttribute('role', 'option');
            item.textContent = option.textContent;
            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
                choose(option);
            });
            list.append(item);
        });
        renderedCount += batch.length;
        visible = filtered.slice(0, renderedCount);
    };
    const render = () => {
        const term = normalize(input.value);
        filtered = availableChoices().filter(
            (option) => normalize(option.textContent).includes(term)
        );
        renderedCount = 0;
        list.replaceChildren();
        appendChoices();
        if (! filtered.length) {
            const empty = document.createElement('span');
            empty.className = 'searchable-empty';
            empty.textContent = 'Sin coincidencias';
            list.append(empty);
        }
        list.hidden = false;
        wrapper.classList.add('is-open');
        wrapper.closest('.field')?.classList.add('select-open');
        const bounds = wrapper.getBoundingClientRect();
        const listHeight = Math.min(list.scrollHeight, parseFloat(getComputedStyle(list).maxHeight));
        wrapper.classList.toggle('opens-up',
            window.innerHeight - bounds.bottom < listHeight + 12 && bounds.top > listHeight
        );
        input.setAttribute('aria-expanded', 'true');
        active = -1;
    };

    const syncDependency = (notify = false) => {
        const chosen = select.selectedOptions[0];
        if (dependency && chosen?.value && chosen.dataset.optionGroup !== dependency.value) {
            select.value = '';
            if (notify) select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        input.disabled = Boolean(dependency && ! dependency.value);
        input.placeholder = input.disabled ? 'Primero selecciona el tipo' : 'Escribe para buscar…';
        input.value = selectedLabel();
        close();
    };

    syncDependency();
    dependency?.addEventListener('change', () => syncDependency(true));
    list.addEventListener('scroll', () => {
        const nearEnd = list.scrollTop + list.clientHeight >= list.scrollHeight - 24;
        if (nearEnd && renderedCount < filtered.length) appendChoices();
    });
    input.addEventListener('focus', render);
    input.addEventListener('input', () => {
        const exact = availableChoices().find(
            (option) => normalize(option.textContent) === normalize(input.value)
        );
        if (exact && select.selectedOptions[0] !== exact) {
            [...select.options].forEach((choice) => { choice.selected = choice === exact; });
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        render();
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') return close();
        if (! ['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'Enter' && active >= 0) return choose(visible[active]);
        if (event.key === 'ArrowDown') active = Math.min(active + 1, visible.length - 1);
        if (event.key === 'ArrowUp') active = Math.max(active - 1, 0);
        highlight();
    });
    input.addEventListener('blur', () => window.setTimeout(() => {
        if (! input.value.trim() && select.value) {
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        input.value = selectedLabel();
        close();
    }, 100));
});
