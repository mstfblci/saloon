<?php

namespace Sammyjo20\Saloon\Http;

use Sammyjo20\Saloon\Exceptions\NestedRequestNotFoundException;

class AnonymousRequestCollection extends RequestCollection
{
    /**
     * @param SaloonConnector $connector
     * @param string $collectionName
     * @param array $requests
     */
    public function __construct(
        SaloonConnector $connector,
        protected string $collectionName,
        protected array $requests,
    )
    {
        parent::__construct($connector);
    }


    /**
     * @param string $name
     * @param array $arguments
     * @return SaloonRequest
     * @throws NestedRequestNotFoundException
     * @throws \ReflectionException
     * @throws \Sammyjo20\Saloon\Exceptions\ClassNotFoundException
     * @throws \Sammyjo20\Saloon\Exceptions\SaloonInvalidRequestException
     */
    public function __call(string $name, array $arguments)
    {
        if (! array_key_exists($name, $this->requests)) {
            throw new NestedRequestNotFoundException($name, $this->collectionName, $this->connector);
        }

        return $this->connector->forwardCallToRequest($this->requests[$name], $arguments);
    }
}
