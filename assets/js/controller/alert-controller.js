import { fadeOutAndRemove } from '../lib/animation';
import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['close'];

    close() {
        this.closeTarget.disabled = true;
        fadeOutAndRemove(this.element);
    }
}
