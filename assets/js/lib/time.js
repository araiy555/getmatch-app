/**
 * @param {string} lang
 *
 * @returns {Promise<null|object>}
 */
export async function loadDateFnsLocale(lang = document.documentElement.lang || 'en') {
    if (lang === 'en') {
        return Promise.resolve(null);
    }

    try {
        const { default: locale } = await import(`date-fns/locale/${lang}/index.js`);

        return locale;
    } catch (e) {
        const i = lang.indexOf('-');

        if (i !== -1) {
            const newLang = lang.substring(0, i);

            console.info(`Couldn't load ${lang}; trying ${newLang}`);

            return loadDateFnsLocale(newLang);
        }

        throw new Error(`Couldn't load ${lang}`);
    }
}
