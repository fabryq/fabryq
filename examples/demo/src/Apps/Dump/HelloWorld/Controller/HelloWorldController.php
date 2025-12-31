<?php

/**
 * Demo controller returning a simple greeting response.
 *
 * @package   App\Test\HelloWorld\Controller
 * @copyright Copyright (c) 2025 Fabryq
 */

declare (strict_types=1);

namespace App\Dump\HelloWorld\Controller;
 
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Handles the HelloWorld demo endpoint.
 */
final class HelloWorldController extends \Fabryq\Runtime\Controller\AbstractFabryqController
{
    /**
     * Return a plain-text greeting.
     *
     * @return Response HTTP response containing the greeting.
     *
     * Side effects:
     * - None.
     */
    #[\OpenApi\Attributes\Get(path: '/hello', operationId: 'helloWorld', summary: 'Return a greeting message.', responses: [new \OpenApi\Attributes\Response(response: \Symfony\Component\HttpFoundation\Response::HTTP_OK, description: 'Greeting returned as plain text.', content: new \OpenApi\Attributes\MediaType(mediaType: 'text/plain', schema: new \OpenApi\Attributes\Schema(type: 'string'))), new \OpenApi\Attributes\Response(response: \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Unexpected server error.')])]
    #[\Symfony\Component\Routing\Attribute\Route('/hello', name: 'hello')]
    public function __invoke(): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response('Hello Fabryq');
    }

    /**
     * Return a plain-text greeting using the alternate handler.
     *
     * @return Response HTTP response containing the greeting.
     *
     * Side effects:
     * - None.
     */
    #[\OpenApi\Attributes\Get(path: '/hello', operationId: 'helloWorldAlternate', summary: 'Return a greeting message (alternate handler).', responses: [new \OpenApi\Attributes\Response(response: \Symfony\Component\HttpFoundation\Response::HTTP_OK, description: 'Greeting returned as plain text.', content: new \OpenApi\Attributes\MediaType(mediaType: 'text/plain', schema: new \OpenApi\Attributes\Schema(type: 'string'))), new \OpenApi\Attributes\Response(response: \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Unexpected server error.')])]
    #[\Symfony\Component\Routing\Attribute\Route('/hello', name: 'hello')]
    public function sss(): \Symfony\Component\HttpFoundation\Response
    {
        return new \Symfony\Component\HttpFoundation\Response('Hello Fabryq');
    }
}
