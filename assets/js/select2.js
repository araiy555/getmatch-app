import icon from './lib/icon';

const forumSelectorTemplate = ({ loading, element, text }, $) => {
    if (loading) {
        return '';
    }

    const $nameEl = $('<span class="flex__grow" />')
        .text(element.getAttribute('data-name') || text);

    const $iconEl = $('<span />');

    if (element.getAttribute('data-subscribed')) {
        $iconEl.append(icon('heart', { extraClasses: 'fg-red' }));
    }

    if (element.getAttribute('data-featured')) {
        $iconEl.append(icon('star', { extraClasses: 'fg-orange' }));
    }

    return $('<span class="flex" />')
        .append($nameEl)
        .append($iconEl);
};

document.querySelectorAll('.select2').forEach(async el => {
    const { default: $ } = await import('jquery');

    if (!window.$) {
        window.$ = window.jQuery = $;
    }

    await Promise.all([
        import('select2'),
        import('select2/dist/css/select2.css'),
    ]);

    const options = {};

    if (el.getAttribute('data-forum-selector')) {
        options.templateResult = state => forumSelectorTemplate(state, $);
        options.templateSelection = state => forumSelectorTemplate(state, $);
    }

    $(el).select2(options);
});
