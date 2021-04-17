import translator from 'bazinga-translator';
import { Controller } from 'stimulus';
import { formatDistanceStrict, isBefore, parseISO } from 'date-fns';
import { loadDateFnsLocale } from '../lib/time';

export default class extends Controller {
    static values = {
        compareTo: String,
    };

    connect() {
        (async () => {
            const locale = await loadDateFnsLocale();

            const timeA = parseISO(this.element.dateTime);
            const timeB = parseISO(this.compareToValue);

            const relativeTime = formatDistanceStrict(timeA, timeB, { locale });

            const format = isBefore(timeB, timeA)
                ? 'time.later_format'
                : 'time.earlier_format';

            this.element.innerText = translator.trans(format, {
                relative_time: relativeTime,
            });
        })();
    }
}
