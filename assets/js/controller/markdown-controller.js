import { debounce } from 'lodash-es';
import { Controller } from 'stimulus';
import { escapeHtml } from '../lib/html';

const DEBOUNCE_RATE = 600;

let parser;

async function loadParser() {
    const { default: md } = await import('markdown-it');

    parser = parser || md({
        highlight: (string, language) => (
            '<pre>' +
                `<code data-controller="syntax-highlight" data-syntax-highlight-language-value="${escapeHtml(language)}">` +
                    escapeHtml(string) +
                `</code>` +
            `</pre>`
        ),
    });

    return Promise.resolve(parser);
}

export default class extends Controller {
    static targets = ['input', 'preview', 'previewContainer'];

    connect() {
        this.updatePreview();
    }

    preview() {
        this.updatePreview();
    }

    updatePreview = debounce(() => {
        const input = this.inputTarget.value;

        if (input.length === 0) {
            this.previewContainerTarget.hidden = true;

            return;
        }

        (async () => {
            const parser = await loadParser();
            const rendered = parser.render(this.inputTarget.value);

            this.previewTarget.innerHTML = rendered;
            this.previewContainerTarget.hidden = rendered.length === 0;
        })();
    }, DEBOUNCE_RATE);
}
