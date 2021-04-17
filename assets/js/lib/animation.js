export function cancelFadeOut(el) {
    el.style.animationDirection = 'normal';
    el.onanimationend = null;
}

export function fadeOut(el) {
    const classes = el.className;

    el.className = '';
    el.offsetWidth;
    el.className = classes;
    el.style.animationDirection = 'reverse';
}

export function fadeOutAndRemove(el) {
    fadeOut(el);

    el.onanimationend = () => {
        if (el.parentNode.contains(el)) {
            el.remove();
            cancelFadeOut(el);
        }
    };
}
