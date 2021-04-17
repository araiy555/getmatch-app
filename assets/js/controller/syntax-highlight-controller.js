import { Controller } from 'stimulus';

const languageAliases = {
    'html': 'xml',
    'c': 'c-like',
    'c++': 'cpp',
    'js': 'javascript',
};

export default class extends Controller {
    static values = {
        language: String,
    };

    connect() {
        (() => this.highlight())();
    }

    async highlight() {
        let language = this.languageValue;

        if (languageAliases[language]) {
            language = languageAliases[language];
        }

        const [{ default: hljs }, { default: definition }] = await Promise.all([
            import('highlight.js/lib/core'),
            import(`highlight.js/lib/languages/${language}.js`),
        ]);

        hljs.registerLanguage(language, definition);
        hljs.highlightBlock(this.element);
    }
}
