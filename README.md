# ActivityPub for Moodle

A proof-of-concept to get Moodle users on the Fediverse.

## Webfinger support

Option 1 - add support for /.well-known routes. Apply
https://github.com/moodle/moodle/compare/main...micaherne:moodle:wellknown-routes

Option 2 - add a redirect rule to point /.well-known/webfinger to /api/rest/v2/local_activitypub/webfinger
in your web server configuration
