let numberFormatter;

export function formatNumber(number) {
    if (!numberFormatter) {
        const lang = document.documentElement.lang || 'en';

        numberFormatter = new Intl.NumberFormat(lang);
    }

    return numberFormatter.format(number);
}
