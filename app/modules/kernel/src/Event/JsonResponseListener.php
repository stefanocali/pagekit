<?php

namespace Pagekit\Kernel\Event;

use Pagekit\Event\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonResponseListener implements EventSubscriberInterface
{
    /**
     * Transforms the body of a JSON request to POST parameters.
     *
     * @param $event
     */
    public function onRequest($event, $request)
    {
        if ('json' === $request->getContentType() && $data = @json_decode($request->getContent(), true)) {
            $request->request->replace($data);
        }
    }

    /**
     * Converts a array to a JSON response.
     *
     * @param $event
     */
    public function onController($event, $request)
    {
        $result = $event->getControllerResult();

        if (is_array($result) || is_a($result, '\JsonSerializable')) {
            $event->setResponse(new JsonResponse($result));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe()
    {
        return [
            'app.request'    => ['onRequest', 130],
            'app.controller' => ['onController', 20]
        ];
    }
}
