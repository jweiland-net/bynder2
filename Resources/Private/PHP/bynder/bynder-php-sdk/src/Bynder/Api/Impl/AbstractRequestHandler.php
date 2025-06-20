<?php

namespace Bynder\Api\Impl;

abstract class AbstractRequestHandler
{
    protected $configuration;

    public function sendRequestAsync($requestMethod, $uri, $options = [])
    {
        $uri = sprintf(
            'https://%s/%s',
            $this->configuration->getBynderDomain(),
            $uri
        );

        if (!in_array($requestMethod, ['GET', 'POST', 'DELETE'])) {
            throw new \Exception('Invalid request method provided');
        }

        $request = $this->sendAuthenticatedRequest($requestMethod, $uri, $options);

        return $request->then(
            function ($response) {
                // Some 204 No Content responses have no content type header.
                if ($response->getStatusCode() === 204 && !$response->hasHeader('Content-Type')) {
                    return null;
                }
                $mimeType = explode(';', $response->getHeader('Content-Type')[0])[0];
                switch ($mimeType) {
                    case 'application/json':
                        return json_decode($response->getBody(), true);
                    case 'text/plain':
                        return (string)$response->getBody();
                    case 'text/html':
                        return $response;
                    default:
                        throw new \Exception('The response type not recognized.');
                }
            }
        );
    }

    protected function getRequestOptions($options = [])
    {
        $requestOptions = array_merge(
            $options,
            $this->configuration->getRequestOptions()
        );

        if (!isset($requestOptions['headers']) || !isset($requestOptions['headers']['User-Agent'])) {
            $requestOptions['headers']['User-Agent'] = 'bynder-php-sdk/' . $this->configuration->getSdkVersion();
        }

        return $requestOptions;
    }

    abstract protected function sendAuthenticatedRequest($requestMethod, $uri, $options = []);
}
