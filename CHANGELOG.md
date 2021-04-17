# Change Log

## v2.1.0 (2020-01-01)

* Add background images for forums.
* Add Docker images suitable for production use.
* Add link to global moderation log in forum logs.
* Add monochrome theme.
* Add popovers for user links.
* Add `postmill:admin` command for setting the admin flag, replacing
  `app:user:add` and `app:user:modify`.
* Add requirement for deletion reason when moderators delete comments.
* Add REST API (unfinished, experimental).
* Add /site/health_check endpoint.
* Add site setting for global theme.
* Disable HTTP caching by default, as this would consume a lot of storage in a
  short amount of time.
* Fix bugs occurring when soft-deleting submissions.
* Fix false positive that had 1/10000 chance of occurring when checking if a ban
  had expired.
* Fix incorrect creation of `DateTime` objects with millisecond precision which
  could potentially cause server errors.
* Fix JS breaking when using a locale with country code.
* Fix nested hideables (e.g. nested comments).
* Fix redirect to 404 after deleting things.
* Fix sessions not being stored in var/sessions.
* Fix unload warning after changing non-text fields.
* Improve appearance of alerts.
* Improve appearance of (un)subscribe buttons.
* Improve display of moderators in forum sidebar.
* Improve display of submission images, particularly if they're portrait-sized.
* Improve dropdown menus to close upon hitting Escape.
* Improve forum list header by reducing vertical footprint.
* Improve light/dark mode button to act without needing a page refresh.
* Improve loading time of paginated comments.
* Improve placement of 'edit biography' link.
* Improve test coverage.
* Improve UI for marking users as whitelisted (previously 'trusted users').
* Refactor alerts to use macros/Twig theme.
* Refactor buttons to use Twig macros instead of the Symfony form builder.
* Refactor CAPTCHA reloading to remove use of inline JS.
* Refactor JS to remove nearly all uses of jQuery.
* Refactor pagination to use Doctrine's ORM.
* Refactor translation files to be flat YAML files, allowing for easy grepping
  of translation keys.
* Remove message about bcrypt password length restrictions, as non-bcrypt
  algorithms are used if available.
* Remove `.notification-head` class.
* Remove `.required-indicator` class.
* Remove special handling of exceptions in XHR context, as XHR is no longer
  used.
* Rename `.nav` to `.flex` in HTML/CSS.
* Rename `.table-list` to `.definition-list` in HTML/CSS.
* Restore Node 8 support in Webpack configuration.
* Update Norwegian translation.
* Update to Symfony 4.4.

## v2.0.0 (2019-07-25)

* Add accessible nav for skipping to certain locations on the page.
* Add client-side checking of character count on some input fields.
* Add comment hiding.
* Add comment listing for forums.
* Add Content Security Policy for near fool-proof mitigation of potential XSS &
  IP harvesting attacks.
* Add full-text search.
* Add HTTP cache support, with Symfony's built-in HTTP cache enabled by default.
* Add image upload for submissions.
* Add linear comment ordering.
* Add native spoiler tags.
* Add quick search by submission URL host.
* Add preferences for disabling notifications.
* Add preference for disabling private messages.
* Add preference for selecting one's preferred fonts.
* Add preference for setting the front page's sort mode.
* Add preference for timezone.
* Add private message deletion.
* Add 'remember me' checkbox to login form.
* Add RSS link and creation date to forum sidebar.
* Add S3 support for submission images and thumbnails.
* Add site setting for disabling wiki.
* Add site settings for minimum roles needed to perform some tasks.
* Add site setting for site name.
* Add soft-deletion of submissions (preserves comments).
* Add sort by submission activity.
* Add sort by creation date to forum list.
* Add user deletion.
* Change some URLs for consistency/easy path matching.
* Clean up Composer dependencies.
* Create self-signed HTTPS certificate and enable HTTP/2 in docker-compose
  setup.
* Disable logging of redirects and bad HTTP requests.
* Fix inconsistency of RSS feeds.
* Fix inconsistent width of subscribe buttons.
* Improve performance of authorization checking.
* Improve performance of forum list.
* Make forms more accessible.
* Miscellaneous performance fixes, code fixes, and UI fixes (too many to
  mention).
* Move night mode preference to user menu.
* Override native styling of checkboxes/radio buttons/selector widgets for
  consistency.
* Prevent duplicate form submission.
* Redesign alerts.
* Redesign comments.
* Redesign forum list.
* Redesign the general site layout to look modern and consistent.
* Redesign site nav to be mobile-friendly, have search.
* Remove CAPTCHA cookie alert on non-POST requests.
* Remove dev dependency on the fontello-cli library due to vulnerabilities.
* Remove DuckDuckGo search box in favour of native search bar.
* Remove legacy theme support.
* Remove neglected webhook support.
* Require entering name of forum one is about to delete, replacing password
  verification.
* Require re-authentication when editing user credentials.
* Replace external formatting help link with built-in formatting help.
* Replace 'recent comments' with submissions/comments selector.
* Select locale based on `Accept-Language` header.
* Show ban details to admins on banned users' profiles.
* Show number of new comments since last visit.
* Update to Symfony 4.3
* Update Esperanto translation.
* Use a message bus to download submission images asynchronously. This replaces
  the old method which was only compatible with PHP-FPM.
* Use Unicode minus when displaying negative votes.
* Use tag-style lists for forums/moderators in sidebars.

## v1.1.0 (2019-03-16)

* Add ability for users to hide forums.
* Add ability to delete wiki pages.
* Add ability to filter submissions by time.
* Add ability to "mention"/"ping" users.
* Add docker-compose configuration for development.
* Add progress spinner to votes, and show failure state.
* Add "recent comments" link to site nav.
* Add search bar for searching via DuckDuckGo.
* Configure and parse Markdown via an event system.
* Don't allow editing soft-deleted comments.
* Add preference for hiding thumbnails.
* Fix instances where long user-inputted text could break the width of the page.
* Fix unwanted redirects when a user is authenticated by REMEMBERME cookie only.
* Improve serialized submissions/comments, used in webhooks.
* Improve URL hierarchy for submission & user routes.
* Rate-limit login attempts, more severe rate-limiting for submissions,
  add rate-limiting to comments.
* Restyle tabs, tables, dropdown menus, forms, votes.
* Show ASCII art on some empty pages.
* Update front-end assets, and the accompanying build tools.
* Update to Symfony 4.2.
* Use CSS variables for `font-family`, `line-height`, and colour values.
* Use sprite sheets for SVG icons.
* Warn admins that attempt to edit credentials of other users.

## v1.0.2 (2018-09-18)

* Fix infinite redirect loop that resulted from a BC break in Symfony.
* Improve GitLab CI configuration. Frontend assets can now be downloaded
  pre-built. Postmill is now tested against PHP 7.1 and 7.2 automatically.
  Templates, translation files, composer.lock, database entities, and all PHP
  files in the project are now automatically linted.
* Update dependencies.

## v1.0.1 (2018-08-03)

* Update dependencies (fixes vulnerability in Symfony).

## v1.0.0 (2018-06-22)

* Add ability to set default locale via environment variable.
* Add `autofocus` attribute to login page.
* Add button to clear individual notifications from inbox.
* Add command for modifying a user.
* Add `/c/` syntax for linking to forum categories.
* Add diff functionality to wiki.
* Add French translation.
* Add global moderation log.
* Add list of users for admins.
* Add logging of submission (un)lockings.
* Add mechanisms for overriding templates and translations.
* Add 'most commented' sorting mode.
* Add notice about cookies on registration page because fuckwits keep disabling
  cookies in their browser then complain about things breaking. Fuck you.
* Add page showing a user's forum bans.
* Add pagination to user pages.
* Add short URL for submissions.
* Add sidebars and descriptions to categories.
* Add (un)ban link in profile for admins.
* Add user setting for toggling automatic fetching of submission titles from
  URL.
* Add user setting for toggling Markdown previews.
* Add webhooks (experimental).
* Add web interface for managing forum categories.
* Add `/w/` syntax for linking to wiki pages.
* Bump minimum required PHP version to 7.1.3.
* Bump minimum required PostgreSQL version to 9.4.
* Clearify the functionality of the user setting that lets forums override
  custom stylesheets.
* Ensure compatibility with PHP 7.2.
* Fix 500 errors when user tricks `validateCsrf` into taking a non-string
  argument via PHP's `[]` syntax in form field names.
* Fix absence of 'Reply' link being confusing to users who disable JS by making
  the link visible all the time.
* Fix chosen locale being ignored when session expires.
* Fix entries incorrectly being marked as 'admin' in the moderation log.
* Fix forums with bans being unable to be deleted.
* Fix language names being sorted incorrectly in user settings.
* Fix long site names wrapping in the navbar.
* Fix missing 'active' class of active tab on user pages.
* Fix missing validation of IP with CIDR notation used in IP ban form.
* Fix 'open external links in new tab' user setting to work with links in
  Markdown.
* Fix redirect to 404 upon deleting a comment via its permalink.
* Fix redirect to login when password is too long.
* Fix replying to deleted top-level comments creating new top-level comments.
* Fix tests not passing reliably.
* Fix 'top'/'controversial' sorting modes being broken.
* Hide password reset link when mailing is disabled.
* Improve appearance of 'no entries on this page' notices.
* Improve `app:user:add` command.
* Improve ban form to allow banning of all IP addresses on record for the user.
* Improve pagination on front page/forums. Caveat: no 'previous' button.
* Improve performance in comment listings.
* Improve performance of test suites by not hashing passwords in the `test`
  environment.
* Improve styling of blockquotes.
* Improve styling of tabs.
* Improve subscribe buttons to use Ajax and avoid reloading the entire page.
* Improve thumbnailing (correct thumbnail size for 1x, use `srcset` for 2x).
* Improve vote buttons by using event bubbling, paving the way for future
  implementations of infinite scroll.
* Increase number of submissions shown on front page from 20 to 25.
* Misc code improvements.
* Move 'no subscriptions' notice beneath the nav bar on the front page.
* Prompt for reason for deletion when mods/admins delete a submission.
* Prompt user for confirmation when leaving a page with filled-out forms.
* Redirect to last visited location when logging in.
* Redirect to URLs with canonical forum names/usernames.
* Refactor comments to use BEM class names.
* Remove broken `CDN` option.
* Remove deprecated CSS classes as [explained in the
  documentation](docs/deprecated-css-classes.md).
* Rename the software to Postmill.
* Replace 'incrementing number' pagination on front page/forums with keyset
  pagination.
* Replace Underscore.js with Lodash to reduce size of built assets and fix a
  build warning.
* Separate CSS and settings forms in the theme editor.
* Show info about blocking users on the blocking page.
* Show 'post'/'save' instead of 'submit' on comment forms.
* Sort items in forum toolbox.
* Update Esperanto, Greek, Portuguese, Spanish translations.
* Update footer with new link.
* Update to Symfony 4.1.
* Use only static front-end assets in production.

## v0.6.0 (2017-11-08)

* Added an admin menu.
* Added a button for locking wiki pages.
* Added Atom feed for featured forums and individual forums.
* Added CAPTCHA on password reset form.
* Added CSS class for all page headings.
* Added forum bans.
* Added maintenance mode that can be toggled via a command.
* Added moderation log.
* Added multi-forum view.
* Added pretty error pages.
* Added recent changes page for wiki.
* Added `.single-comment-alert' to container that shows warnings about viewing a
  single comment.
* Added source code viewer for themes.
* Added submission locking.
* Added syntax highlighting.
* Added navigation tabs to user pages.
* Added unlinked 'recent comments' page.
* Added user biographies.
* Added user blocking.
* Added user settings for choosing a preferred theme.
* Added user setting to choose if links are opened in a new tab.
* Bind forms to data transfer objects (DTOs) instead of entities.
* Bumped minimum PHP version to 7.0.8/7.1.3.
* Changed the alert animation.
* Don't order by sticky in new/top/controversial sorting modes.
* Don't store IP addresses of trusted users.
* Don't show thumbnails on tiny screens.
* Fixed 500 errors that could occur when hitting 'IP ban' on deleted posts.
* Fixed authorisation issues that occurred when roles change during a session.
* Fixed being unable to ban IPv6 addresses with CIDR mask.
* Fixed issue where changing from an upvote to a downvote or vice versa wouldn't
  update a submission's ranking.
* Fixed issue where disabling 'show custom stylesheets' wouldn't include the
  built-in stylesheets on the page.
* Fixed privilege escalation issue where everyone could sticky a thread or post
  as moderator.
* Fixed remaining logged in when passwords changed.
* Fixed typos in Spanish translation.
* Fixed word-wrapping in \<pre> tags.
* Improve how private messages are displayed.
* Increase max size of wiki bodies.
* Log out on password change.
* Made forum bans public.
* Made forum-related route paths more consistent.
* Mark CSS responses immutable.
* Move away from `raddit:` namespace in commands.
* Moved the submission deletion from the edit form to the submission nav.
* Refactor most entities to not include validation constraints and add a
  constructor which ensures the entities are always valid.
* Refactored route definitions and removed template namespaces.
* Refactor some entities to use UUIDs instead of auto-incrementing integers as
  their primary key.
* Removed broken 2FA.
* Revamped the entire ban system.
    * Added user bans in addition to IP bans.
    * Trusted users are checked for user bans.
    * Untrusted users are checked for user bans and IP bans.
    * Logged out users are checked for IP bans.
* Replaced 'stylesheets' with 'themes'.
    * Have separate fields for common, day & night CSS.
    * Make themes version controlled.
* Optimise performance by removing redundant Twig blocks in the vote widget.
* Optimise submission listings by pre-hydrating a number of associations,
  drastically reducing the number of SQL queries made.
* Restrict wiki editing to trusted users and users that signed up over 24 hours
  ago.
* Set width and height on SVG icons.
* Updated Portuguese, Spanish translations.
* Updated the submission ranking algorithm to account for the number of
  comments and make downvotes count for something.
* Updated to Symfony 3.4.
* Use the select2 library for the forum selection box on the submit page.

## v0.5.1 (2017-08-09)

* Add titles/headings to pages where this was missing.
* Fix bug where the stylesheet of a forum couldn't be changed if the forum
  lacked a description.
* Remove superfluous margin from submissions.

## v0.5.0 (2017-08-04)

* Added `autocomplete="new-password"` to password fields when editing user
  accounts.
* Added CAPTCHA to registration form.
* Added a command that prunes IP addresses on many entities, optionally after
  they reach a provided age.
* Added (buggy) two-factor authentication for admins only.
* Added combined view of comments ands
* Added custom CSS.
* Added Dutch, Finnish, German, Greek and Portuguese (Brazilian) translations.
* Added explanation of the various user form fields.
* Added Flysystem for submission thumbnails as part of push for multi-server
  support.
* Added forum categories.
* Added honeypot fields to fool spam bots.
* Added icons to the user menu.
* Added 'night mode', a dark stylesheet that's supposedly easier on the eyes.
* Added mod/admin flags.
* Added Open Graph support
* Added the option to serve assets over a CDN.
* Added private messaging.
* Added sorting options for forum list.
* Added strikethrough support via `~~` syntax.
* Added 'trusted' users.
* Added URL slugs for submissions.
* Added wiki.
* Allow trailing slashes for all routes.
* Automatically subscribe to forums when you create them.
* Began using BEM naming conventions for page elements.
* Began refactoring translation key names.
* Bumped the minimum PostgreSQL version to 9.3.
* Check for user bans on login.
* Don't allow new users to create forums.
* Fixed 'clear inbox' button potentially removing unseen entries.
* Fixed `/f/{forum_name}` being case-sensitive.
* Fixed mistake in English language pack.
* Fixed wrong permissions being set on submission images.
* Go back to page 1 when switching between front page listings.
* Keep track of when a submission or comment was edited.
* Keep track of when a user last logged in.
* Linkify `/f/<forum>` and `/u/<username>` in Markdown.
* Lock submissions/comments for furthering editing once they've been edited by a
  moderator.
* Login automatically upon registration.
* Notify user when their submission or comment is replied to.
* Preload some assets with HTTP/2 Server Push.
* Rate limit submissions for untrusted users.
* Refactored the entire frontend asset build system.
* Removed .htaccess files as PHP-FPM is the preferred way to run the software.
* Renamed the 'description' field when creating/editing forums to 'sidebar' and
  added an actual description field.
* Replaced the stock favicons.
* Rotate log files in production.
* Upgraded to Symfony 3.3, Swiftmailer 6.
* Use APCu caching for Doctrine queries & metadata in production.
* Use environment variables for parameters.
* Use SVG icons instead of an icon fonts.
* Various fixes for bugs and consistency.
* Various UI improvements.

## v0.4.1 (2017-05-23)

* Added Esperanto and Spanish translations.
* Added Markdown preview.
* Added page for adding moderators.
* Added page for viewing submissions across all forums.
* Collapse navbar margins on small screen sizes.
* Fixed bug preventing deletion of forums.
* Fixed bug preventing moderators from editing submissions.
* Fixed nasty bug resulting in invalid entities being persisted to the database.
* Use the correct page title on submission pages.
* Use a web font for voting arrows since Unicode arrows are inconsistent across
  platforms.

## v0.4.0 (2017-05-09)

* Added ability for users to select their preferred locale.
* Added featured forums for logged-out users and users without subscriptions.
* Added footer which displays the software name and version.
* Added forum directory.
* Added forum subscriptions.
* Added IP bans.
* Added link to Markdown help.
* Added moderator list page.
* Added Norwegian translation.
* Added pagination for front page and forum indexes.
* Added separate page for user settings.
* Added sticky posts for forums.
* Added 'Submit' links everywhere. Clicking them within a forum makes that forum
  selected in the submission form.
* Added thumbnails for link submissions.
* Display host part of URL next to link submission titles.
* Fetch title of URL in submission form via Ajax.
* Keep track of IP addresses when submitting/commenting/voting.
* Keep track of time a moderator was given their privileges.
* Keep users logged in past session expiration.
* Load comment forms in-line via Ajax.
* Miscellaneous UI & backend fixes.
* Parsed Markdown is no longer stored in the database. Instead, it is parsed on
  demand and cached for 24 hours.
* Refactor submissions table to store the ranking.
* Remove required email address when registering.
* Replaced the popular submission ranking algorithm with one that makes sense.
* Revamped form styling.
* Rewrote fixtures.

## v0.3.1 (2017-04-11)

* Fixed recursion bug in JS which would make the browser consume 100% CPU.
* Fixed nasty bug where submitting the user form without a password would erase
  the existing password.

## v0.3.0 (2017-03-26)

* Bumped the minimum PHP version to 7.0 as 5.6 is no longer supported.
* Much future-proofing and many improvements to frontend assets.
    * webpack/gulp-based build system.
    * JS is written in ES2015 and transpiled to ES5 on build.
    * jQuery is used for DOM manipulation & traversal, and Ajax calls.
    * Individual JS 'plugins' are now reusable and can be applied to e.g. new
      DOM elements created after an Ajax request.
    * CSS rules have been grouped into files.
    * Many style improvements have been made.
* Added the ability to edit:
    * Comments
    * Forums
    * Submissions
    * User accounts
* Added the ability to remove:
    * Forums
    * Submissions
* Added a dropdown menu for user actions.
* Added the ability to create user accounts via the command line.
* Remove the distinction between 'Post submissions' and 'URL submissions'.
* Show notices when certain actions are performed.
* Users can now be administrators.
    * Added an `--admin` option to the `raddit:add-user` command.
* Users can now reset their passwords via email.
* Voting on posts via Ajax (non-JS fallback still available.)

## v0.2.0 (2017-01-06)

* Sort comments by descending net score.
* Usernames and forum names must now be case-insensitively unique. Duplicates
  are renamed upon running database migrations.
* Ability to delete comments.
    * Users can delete their own comments, but they will not disappear entirely
      if they have replies. These partially deleted comments are to be called
      *soft-deleted*, i.e. their entry remains in the database, but the comment
      body is blanked out.
    * Forum moderators and site administrators can delete comments in their
      respective realms. If a comment has replies, they can choose to delete the
      entire comment thread, or to merely soft-delete the original post.

## v0.1.2 (2017-01-02)

* Make use of Doctrine migrations.
* Add missing 'create forum' link in the menu on the front page.
* Add a form theme and CSS so all forms look OK.
* Have `rel="nofollow` added to link elements in user-submitted Markdown.
* Update fixtures to have the author upvote their contributions.

## v0.1.1 (2016-12-29)

* Added the ability for the user to choose how to sort submission listings.
* Minor accessibility improvement to voting buttons.
* Block undesired embedding of external resources in user-submitted Markdown.
  External embedding was never intended to be allowed in the first place.
* Autolinkify URLs in user-submitted Markdown.
* Have `target="_blank"` and `rel="noreferrer"` added to link elements in
  user-submitted Markdown.

## v0.1.0 (2016-12-28)

* First release.
