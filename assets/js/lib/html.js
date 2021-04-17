/**
 * @param {string} html
 *
 * @returns {DocumentFragment}
 */
export function parseHtml(html) {
    return document.createRange().createContextualFragment(html);
}

/**
 * @param {string} text
 *
 * @returns {string}
 */
export function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;

    return div.innerHTML;
}
