import { Application } from 'stimulus';
import { definitionsFromContext } from 'stimulus/webpack-helpers';

const application = Application.start();
const context = require.context('./controller', true, /\.js$/);
application.load(definitionsFromContext(context));

import './comment-count';
import './commenting';
import './dropdowns';
import './select2';
import './unload-forms';
import './user-popper';
