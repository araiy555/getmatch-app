import { Controller } from 'stimulus';
import translator from 'bazinga-translator';

let splitter;

async function countCharacters(text) {
    const { default: GraphemeSplitter } = await import('grapheme-splitter');

    if (!splitter) {
        splitter = new GraphemeSplitter();
    }

    return splitter.countGraphemes(text);
}

export default class extends Controller {
    static values = {
        max: Number,
    };

    connect() {
        if (this.element.value.length > 0) {
            this.validate();
        }
    }

    validate() {
        if (this.element.value.length === 0) {
            this.element.setCustomValidity('');

            return;
        }

        (async () => {
            const characterCount = await countCharacters(this.element.value);

            if (characterCount > this.maxValue) {
                const message = translator.trans('flash.too_many_characters', {
                    count: characterCount,
                    max: this.maxValue,
                });

                this.element.setCustomValidity(message);
            } else {
                this.element.setCustomValidity('');
            }
        })();
    }
}
