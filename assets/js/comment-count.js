// Add text next to submission comments link showing how many new comments have
// appeared since last visit.

import translator from 'bazinga-translator';
import { formatNumber } from './lib/intl';

document.querySelectorAll('.js-display-new-comments').forEach(el => {
    const submissionId = el.getAttribute('data-submission-id');
    const currentCount = el.getAttribute('data-comment-count');
    const lastCount = localStorage.getItem(`comments-${submissionId}`);
    const newComments = Math.max(currentCount - lastCount, 0);

    if (lastCount === null || newComments === 0) {
        return;
    }

    el.append(' ', translator.transChoice('submission.new_comments',
        newComments,
        { count: formatNumber(newComments) }
    ));
});

document.querySelectorAll('.js-update-comment-count').forEach(el => {
    const submissionId = el.getAttribute('data-submission-id');
    const commentCount = el.getAttribute('data-comment-count');

    localStorage.setItem(`comments-${submissionId}`, commentCount);
});
