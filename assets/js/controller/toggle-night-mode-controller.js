import router from 'fosjsrouting';
import { fetch } from '../lib/http';
import { Controller } from 'stimulus';

const LIGHT = 'light';
const DARK = 'dark';

export default class extends Controller {
    initialize() {
        this.url = router.generate('change_night_mode', { _format: 'json' });
    }

    lighten(event) {
        event.preventDefault();
        this.applyPreference(LIGHT);
    }

    darken(event) {
        event.preventDefault();
        this.applyPreference(DARK);
    }

    applyPreference(mode) {
        document.documentElement.setAttribute('data-night-mode', mode);

        (() => this.sendRequest(mode))();
    }

    async sendRequest(value) {
        const body = new FormData(this.element);
        body.append('nightMode', value);

        return fetch(this.url, { body, method: 'POST' });
    }
}
