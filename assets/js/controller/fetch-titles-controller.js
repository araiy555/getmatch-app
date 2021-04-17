import { Controller } from 'stimulus';
import { fetch, ok } from '../lib/http';
import routing from 'fosjsrouting';

export default class extends Controller {
    static targets = ['source', 'destination'];

    initialize() {
        this.endpoint = routing.generate('fetch_title');
    }

    fetchTitle() {
        const url = this.sourceTarget.value.trim();

        if (
            url === '' ||
            !/^http?s:\/\//i.test(url) ||
            this.destinationTarget.value.trim() !== ''
        ) {
            return;
        }

        (async () => {
            const body = new FormData();
            body.append('url', this.sourceTarget.value);

            const response = await fetch(this.endpoint, { body, method: 'POST' });
            await ok(response);

            const { title } = await response.json();

            if (this.destinationTarget.value.trim() === '') {
                this.destinationTarget.value = title;
            }
        })();
    }
}
