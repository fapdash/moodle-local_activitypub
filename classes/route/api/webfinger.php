<?php

namespace local_activitypub\route\api;

use core\param;
use core\router\route;
use core\router\schema\objects\array_of_strings;
use core\router\schema\objects\array_of_things;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\query_parameter;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use core\url;
use local_activitypub\webfinger_link;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class webfinger {
    /**
     * Get webfinger data for the requested user.
     */
    #[route(
        path: '/webfinger', // Resolves to /api/rest/v2/local_activitypub/webfinger.
        method: ['GET'],
        queryparams: [
            new query_parameter(
                name: 'resource',
                type: param::TEXT,
                required: true,
                description: 'The resource for which to retrieve webfinger data.',
            ),
        ],
        responses: [
            new response(
                statuscode: 200,
                description: 'OK',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'subject' => new scalar_type(type: param::TEXT, required: true),
                                'aliases' => new array_of_strings(param::TEXT),
                                'links' => new array_of_things(webfinger_link::class),
                            ],
                        ),
                    ),
                ],
            ),
        ],
    )]
    public function get_webfinger(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \moodle_database $db,
    ): payload_response {
        global $CFG;
        $params = $request->getQueryParams();
        $resource = $params['resource'] ?? '';
        if (!str_contains($resource, '@')) {
            throw new \moodle_exception('Invalid resource format for webfinger.');
        }
        [$username, $domain] = explode('@', $resource);
        if ($domain !== parse_url($CFG->wwwroot, PHP_URL_HOST)) {
            throw new \moodle_exception('Invalid domain for webfinger resource.');
        }
        $user = $db->get_record('user', ['username' => $username, 'deleted' => 0]);
        if (!$user) {
            throw new \moodle_exception('User not found for webfinger resource.');
        }
        $profileurl = new url('/user/profile.php', ['id' => $user->id]);
        $payload = [
            'subject' => "acct:{$resource}",
            'links' => [
                [
                    'rel' => 'self',
                    'type' => 'text/html',
                    'href' => $profileurl->out(),
                ],
            ],
        ];

        return new payload_response(
            $payload,
            $request,
            $response,
        );
    }
}