import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['image'];

    connect() {
        this.element.disabled = false;
    }

    reload() {
        const [url, queryString] = this.imageTarget.src.split('?', 2);

        const params = new URLSearchParams(queryString);
        params.set('n', '' + new Date().getTime());

        this.imageTarget.src = url + '?' + params.toString();
    }
}
