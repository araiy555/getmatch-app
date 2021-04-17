import router from 'fosjsrouting';
import translator from 'bazinga-translator';
import { Controller } from 'stimulus';
import { fetch, ok } from '../lib/http';
import { formatNumber } from '../lib/intl';

export default class extends Controller {
    static classes = ['subscribe', 'unsubscribe'];
    static targets = ['label', 'subscribers'];
    static values = {
        errorValue: Boolean,
        forum: String,
        loading: Boolean,
        subscribers: Number,
        subscribed: Boolean,
    };

    connect() {
        this.formEl = this.element.closest('.subscribe-form');
    }

    async subscribe(event) {
        if (this.errorValue) {
            return;
        }

        event.preventDefault();

        const url = router.generate(this.subscribedValue ? 'unsubscribe' : 'subscribe', {
            forum_name: this.forumValue,
            _format: 'json',
        });

        try {
            this.loadingValue = true;

            const response = await fetch(url, {
                method: 'POST',
                body: new FormData(this.formEl),
            });
            await ok(response);

            this.subscribedValue = !this.subscribedValue;
            this.subscribersValue += (this.subscribedValue ? 1 : -1);
        } catch (e) {
            console && console.log(e);

            this.errorValue = true;
            this.element.click();
        } finally {
            this.loadingValue = false;
        }
    }

    loadingValueChanged(loading) {
        this.element.blur();
        this.element.disabled = loading;
    }

    subscribedValueChanged(subscribed) {
        if (subscribed) {
            this.element.classList.add(this.unsubscribeClass);
            this.element.classList.remove(this.subscribeClass);
        } else {
            this.element.classList.add(this.subscribeClass);
            this.element.classList.remove(this.unsubscribeClass);
        }

        this.labelTarget.innerText = subscribed
            ? translator.trans('action.unsubscribe')
            : translator.trans('action.subscribe');
    }

    subscribersValueChanged(subscribers) {
        this.subscribersTarget.innerText = formatNumber(subscribers);
        this.subscribersTarget.setAttribute('aria-label', translator.transChoice(
            'forum.subscriber_count',
            subscribers,
            { formatted_count: formatNumber(subscribers) }
        ));
    }
}
