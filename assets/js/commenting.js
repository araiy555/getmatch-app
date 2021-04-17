// load comment forms via ajax

import translator from 'bazinga-translator';
import { fetch, ok } from './lib/http';
import { escapeHtml, parseHtml } from './lib/html';

function createErrorMessage(e) {
    return parseHtml(`
        <p class="comment__error fg-red" title="${escapeHtml(String(e))}">
            <strong>${escapeHtml(translator.trans('comments.form_load_error'))}</strong>
        </p>
    `);
}

function handleClick(replyLinkEl) {
    const parentEl = replyLinkEl.closest('.comment__main');
    const formEl = parentEl.querySelector('.comment-form');

    if (formEl) {
        formEl.style.display = formEl.style.display ? null : 'none';

        return;
    }

    parentEl.querySelectorAll('.comment__error').forEach(el => el.remove());

    replyLinkEl.classList.add('comment__reply-link-disabled');

    fetch(replyLinkEl.getAttribute('data-form-url'))
        .then(response => ok(response))
        .then(response => response.text())
        .then(formHtml => parentEl.append(parseHtml(formHtml)))
        .catch(e => parentEl.append(createErrorMessage(e)))
        .finally(() => {
            replyLinkEl.classList.remove('comment__reply-link-disabled');
        });
}

document.querySelectorAll('.comment .comment-form').forEach(el => {
    // hide open forms (they're initially visible for non-js users)
    el.style.display = 'none';
});

addEventListener('click', event => {
    const el = event.target.closest('.comment__reply-link');

    if (el) {
        event.preventDefault();

        handleClick(el);
    }
});
