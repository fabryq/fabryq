<?php

/**
 * Demo controller returning a simple greeting response.
 *
 * @package   App\Test\HelloWorld\Controller
 * @copyright Copyright (c) 2025 Fabryq
 */

declare(strict_types=1);

namespace App\Test\HelloWorld\Controller;

use App\Other\Ghost;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


/**
 * Handles the HelloWorld demo endpoint.
 */
final class HelloWorldController
{
    /**
     * Return a plain-text greeting.
     *
     * @return Response HTTP response containing the greeting.
     */
    #[OA\Get(
        path: '/hello',
        operationId: 'helloWorld',
        summary: 'Return a greeting message.',
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: 'Greeting returned as plain text.',
                content:     new OA\MediaType(
                                 mediaType: 'text/plain',
                                 schema:    new OA\Schema(type: 'string')
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Unexpected server error.'
            ),
        ]
    )]
    #[Route('/hello', name: 'hello')]
    public function __invoke(): Response
    {
        return new Response('Hello Fabryq');
    }

    #[Route('/hello', name: 'hello')]
    public function sss(): Response
    {
        return new Response('Hello Fabryq');
    }
}
