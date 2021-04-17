import tippy from 'tippy.js';
import router from 'fosjsrouting';
import { fetch, ok } from './lib/http';

const BASE_URL = router.getBaseUrl();

const TARGETS = [
    '.submission__submitter',
    '.submission__body a[href*="/user/"]',
    '.comment__body a[href*="/user/"]',
    '.comment__context a[href*="/user/"]',
    '.comment__info a[href*="/user/"]',
    '.message__head a[href*="/user/"]',
    '.message__body a[href*="/user/"]',
].map(target => '.js-poppers-enabled ' + target).join(', ');

const htmlCache = new Map();

/**
 * @param {string} username
 *
 * @returns {Promise<string>}
 */
function fetchPopperHtml(username) {
    if (htmlCache.has(username)) {
        return Promise.resolve(htmlCache.get(username));
    }

    const url = router.generate('user_popper', { username });

    return fetch(url)
        .then(response => ok(response))
        .then(response => response.text())
        .then(html => {
            htmlCache.set(username, html);

            return html;
        });
}

const elements = [...document.querySelectorAll(TARGETS)].filter(el => (
    el.origin === location.origin &&
    el.href.match(/\//g).length - (BASE_URL.match(/\//g) || []).length === 4
));

tippy(elements, {
    allowHTML: true,
    content: '…',
    delay: 250,
    interactive: true,
    theme: 'postmill',

    // props that keep this desktop-only
    appendTo: document.body,
    aria: { content: null },
    touch: false,
    trigger: 'mouseenter',

    onCreate(instance) {
        instance.fetchState = {
            fetching: false,
            done: false,
            username: instance.reference.href.replace(/^.*\//, ''),
        };
    },

    onShow(instance) {
        if (instance.fetchState.fetching || instance.fetchState.done) {
            return;
        }

        instance.fetchState.fetching = true;

        fetchPopperHtml(instance.fetchState.username)
            .then(html => {
                instance.fetchState.done = true;
                instance.setContent(html);
            })
            .finally(() => {
                instance.fetchState.fetching = false;
            });
    },
});
