<?php

namespace SummerCraft\Core\Response;

use SummerCraft\Core\ComponentManaging\RequestScope;
use SummerCraft\Core\EventDispatcher\EventDispatcher;
use SummerCraft\Core\EventDispatcher\SimpleEvent;
use SummerCraft\Core\ExceptionProcessing\ExceptionProcessor;
use SummerCraft\Core\ExceptionProcessing\ThrowableContext;
use SummerCraft\Core\Request\Request;

class SpecificResponseHandler
{
    public function __construct(
        private RequestScope $requestScope,
        private EventDispatcher $eventDispatcher,
        private Request $request,
        private Response $response,
        private ThrowableContext $throwableContext,
    ) { }

    public function errorForbidden(string $message = ''): Response
    {
        $this->eventDispatcher->fire(new SimpleEvent('specific_response_shown', ['message' => 'Page '. Response::HTTP_CODE_FORBIDDEN .' showed with uri ['.$this->request->getUri().']']));
        $this->response->setStatus(Response::HTTP_CODE_FORBIDDEN);
        $this->response->append('<h1>403 Forbidden</h1>');
        $this->response->append('<br/><br/>Additional Message: ' . $message);

        return $this->response;
    }

    public function errorBadRequest(): Response
    {
        $this->eventDispatcher->fire(new SimpleEvent('specific_response_shown', ['message' => 'Page '. Response::HTTP_CODE_BAD_REQUEST .' showed with uri ['.$this->request->getUri().']']));

        $this->response->setStatus(Response::HTTP_CODE_BAD_REQUEST);
        $this->response->append('<h1>400 Not found</h1>');

        return $this->response;
    }

    public function errorNotFound(): Response
    {
        $this->eventDispatcher->fire(new SimpleEvent('specific_response_shown', ['message' => 'Page '. Response::HTTP_CODE_NOT_FOUND .' showed with uri ['.$this->request->getUri().']']));

        $this->response->setStatus(Response::HTTP_CODE_NOT_FOUND);
        $this->response->append('<h1>404 Not found</h1>');

        return $this->response;
    }

    public function errorServerError(): Response
    {
        $this->response->setStatus(Response::HTTP_CODE_INTERNAL_SERVER_ERROR);
        $responseString = ExceptionProcessor::defaultProcessExceptionToString(
            $this->throwableContext->getThrowable(),
            $this->requestScope
        );

        $this->eventDispatcher->fire(new SimpleEvent('specific_response_shown', ['message' => 'Page '. Response::HTTP_CODE_INTERNAL_SERVER_ERROR .' showed with uri ['.$this->request->getUri().'] [' . $responseString . ']']));

        if (empty($responseString)) {
            $responseString = 'Internal server error 500.';
        }
        $this->response->append($responseString);

        return $this->response;
    }
}