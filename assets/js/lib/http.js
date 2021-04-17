import { isObject } from 'lodash-es';

/**
 * @returns {Promise<Response>}
 */
export async function fetch(url = '', options = {}) {
    if (isObject(url)) {
        options = url;
        url = options.url;
    }

    options = { ...options };
    options.credentials = options.credentials || 'same-origin';
    options.redirect = options.redirect || 'error';

    return window.fetch(url, options);
}

export async function ok(response) {
    if (!response.ok) {
        const e = new Error(response.statusText);
        e.response = response;

        throw e;
    }

    return response;
}
