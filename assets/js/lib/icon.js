import spriteSheet from '../../icons/icons.svg';
import { escapeHtml as e, parseHtml } from './html';

const icon = (name, options = {}) => parseHtml(`
    <span class="icon ${e(options.extraClasses || '')}">
        <svg width="16" height="16">
            <use xlink:href="${e(`${spriteSheet}#${name}`)}" />
        </svg>
    </span>
`);

export default icon;
