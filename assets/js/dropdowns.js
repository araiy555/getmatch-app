// Make dropdown menus accessible and triggered on click

const FOCUSABLE_ELEMENTS = [
    '.dropdown__toggle',
    '.dropdown__menu a[href]',
    '.dropdown__menu button:not([disabled])',
    '.dropdown__menu input:not([type="hidden"]):not([disabled])',
].map(v => '.dropdown--expanded > ' + v).join(', ');

const MENU_ACTIONS = [
    '.dropdown__menu a[href]',
    '.dropdown__menu button[type="submit"]',
    '.dropdown__menu button:not([type])',
].join(', ');

function toggle(dropdownEl) {
    [...document.querySelectorAll('.dropdown--expanded')]
        .filter(v => !v.contains(dropdownEl))
        .concat(dropdownEl)
        .forEach(dropdownEl => {
            const expanded = dropdownEl.classList.toggle('dropdown--expanded');
            const toggleEl = dropdownEl.querySelector('.dropdown__toggle');

            toggleEl.setAttribute('aria-expanded', expanded);
        });
}

function getFocusableElements() {
    return [...document.querySelectorAll(FOCUSABLE_ELEMENTS)]
        .filter(el => el.offsetWidth > 0 && el.offsetHeight > 0);
}

function moveInDropdown(amount) {
    const elements = getFocusableElements();
    let i = elements.findIndex(el => el === document.activeElement) + amount;

    if (i >= elements.length) {
        i = 0;
    } else if (i < 0) {
        i = elements.length - 1;
    }

    elements[i].focus();
}

function handleKeyDown(event) {
    const dropdownEl = document.querySelector('.dropdown--expanded');

    if (!dropdownEl || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    if (event.key === 'Escape' || event.key === 'Esc') {
        const deepestDropdownEl = [
            dropdownEl,
            ...dropdownEl.querySelectorAll('.dropdown--expanded'),
        ].pop();

        toggle(deepestDropdownEl);
        deepestDropdownEl.querySelector('.dropdown__toggle').focus();
    } else if (event.shiftKey && event.key === 'Tab') {
        moveInDropdown(-1);
    } else if (event.key === 'Tab') {
        moveInDropdown(1);
    } else if (event.key === 'ArrowUp' || event.key === 'Up') {
        moveInDropdown(-1);
    } else if (event.key === 'ArrowDown' || event.key === 'Down') {
        moveInDropdown(1);
    } else if (event.key === 'Home') {
        moveInDropdown(Infinity);
    } else if (event.key === 'End') {
        moveInDropdown(-Infinity);
    } else {
        return;
    }

    event.preventDefault();
}

document.querySelectorAll('.dropdown__toggle').forEach(el => {
    el.setAttribute('aria-haspopup', 'true');
    el.setAttribute('aria-expanded', 'false');
});

addEventListener('keydown', handleKeyDown);

addEventListener('click', event => {
    if (event.target.closest(MENU_ACTIONS)) {
        event.stopImmediatePropagation();

        // close the menu upon clicking a link or button or similar inside it
        document.querySelectorAll('.dropdown--expanded').forEach(toggle);
    }
});

addEventListener('click', event => {
    const toggleEl = event.target.closest('.dropdown__toggle');

    if (toggleEl) {
        event.stopImmediatePropagation();

        toggle(toggleEl.closest('.dropdown'));
    }
});

addEventListener('click', event => {
    if (event.target.closest('.dropdown__menu')) {
        // prevent closing the menu when clicking inside
        event.stopImmediatePropagation();
    }
});

addEventListener('click', () => {
    document.querySelectorAll('.dropdown--expanded').forEach(toggle);
});
