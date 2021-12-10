<?php

declare(strict_types=1);

namespace VaclavVanik\Soap\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use VaclavVanik\Soap\Binding;

final class HttpBindingClient implements Client
{
    /** @var ClientInterface */
    private $client;

    /** @var Binding\Binding */
    private $binding;

    /** @var RequestInterface|null */
    private $request;

    /** @var ResponseInterface|null */
    private $response;

    public function __construct(ClientInterface $client, Binding\Binding $binding)
    {
        $this->client = $client;
        $this->binding = $binding;
    }

    /** @inheritdoc */
    public function call(string $operation, array $parameters = [], array $soapHeaders = []): Result
    {
        try {
            $this->request = $this->binding->request($operation, $parameters, $soapHeaders);

            $this->response = $this->client->sendRequest($this->request);

            $result = $this->binding->response($operation, $this->response);

            return new Result($result->getResult(), $result->getHeaders());
        } catch (NetworkExceptionInterface $e) {
            throw Exception\HttpConnection::fromNetworkException($e);
        } catch (Binding\Exception\SoapFault $e) {
            throw Exception\SoapFault::fromSoapFault($e);
        } catch (Binding\Exception\ValueError $e) {
            throw new Exception\ValueError($e->getMessage(), $e->getCode(), $e);
        } catch (Throwable $e) {
            throw Exception\Runtime::fromThrowable($e);
        }
    }

    /**
     * Get last request
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * Get last response
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
