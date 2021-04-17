import translator from 'bazinga-translator';
import { Controller } from 'stimulus';
import { formatDistanceStrict, parseISO } from 'date-fns';
import { loadDateFnsLocale } from '../lib/time';

export default class extends Controller {
    connect() {
        (async () => {
            const locale = await loadDateFnsLocale();

            const then = parseISO(this.element.dateTime);
            const now = new Date();

            const relativeTime = formatDistanceStrict(then, now, {
                addSuffix: true,
                locale,
            });

            this.element.innerText = translator.trans('time.at_relative_time', {
                relative_time: relativeTime,
            });
        })();
    }
}
