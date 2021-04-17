import { Controller } from 'stimulus';
import { fetch, ok } from '../lib/http';
import router from 'fosjsrouting';
import translator from 'bazinga-translator';

const VOTE_UP = 1;
const VOTE_NONE = 0;
const VOTE_DOWN = -1;

export default class extends Controller {
    static classes = ['upvoted', 'downvoted', 'loading', 'error'];
    static targets = ['score', 'up', 'down'];
    static values = {
        choice: Number,
        error: Boolean,
        id: String,
        loading: Boolean,
        route: String,
        score: Number,
    };

    up(event) {
        event.preventDefault();
        (async () => await this.vote(VOTE_UP))();
    }

    down(event) {
        event.preventDefault();
        (async () => await this.vote(VOTE_DOWN))();
    }

    async vote(choice) {
        choice = this.choiceValue === choice ? VOTE_NONE : choice;

        const url = router.generate(this.routeValue, {
            id: this.idValue,
            _format: 'json',
        });

        const data = new FormData(this.element);
        data.append('choice', choice);

        const oldChoice = this.choiceValue;

        this.errorValue = false;
        this.loadingValue = true;
        this.choiceValue = choice;

        try {
            let response = await fetch(url, { method: 'POST', body: data });
            response = await ok(response);
            const { netScore } = await response.json();

            this.choiceValue = choice;
            this.scoreValue = netScore;
        } catch (e) {
            this.choiceValue = oldChoice;
            this.errorValue = true;

            throw e;
        } finally {
            this.loadingValue = false;
        }
    }

    choiceValueChanged(choice) {
        if (choice === VOTE_UP) {
            this.element.classList.add(this.upvotedClass);
            this.element.classList.remove(this.downvotedClass);
            this.upTarget.title = translator.trans('action.retract_upvote');
            this.downTarget.title = translator.trans('action.downvote');
        } else if (choice === VOTE_DOWN) {
            this.element.classList.add(this.downvotedClass);
            this.element.classList.remove(this.upvotedClass);
            this.upTarget.title = translator.trans('action.upvote');
            this.downTarget.title = translator.trans('action.retract_downvote');
        } else {
            this.element.classList.remove(this.upvotedClass, this.downvotedClass);
            this.upTarget.title = translator.trans('action.upvote');
            this.downTarget.title = translator.trans('action.downvote');
        }
    }

    errorValueChanged(error) {
        if (error) {
            this.element.classList.add(this.errorClass);
        } else {
            this.element.classList.remove(this.errorClass);
        }
    }

    loadingValueChanged(loading) {
        if (loading) {
            this.element.classList.add(this.loadingClass);
            this.upTarget.disabled = this.downTarget.disabled = true;
        } else {
            this.element.classList.remove(this.loadingClass);
            this.upTarget.disabled = this.downTarget.disabled = false;
        }
    }

    scoreValueChanged(score) {
        if (score >= 0) {
            this.scoreTarget.innerText = score;
        } else {
            this.scoreTarget.innerHTML =
                '&minus;' +
                Math.abs(score) +
                '<span class="no-visibility" aria-hidden="true">&minus;</span>';
        }
    }
}
