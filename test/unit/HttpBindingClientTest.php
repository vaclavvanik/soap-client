<?php

declare(strict_types=1);

namespace VaclavVanikTest\Soap\Client;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SoapHeader;
use Throwable;
use VaclavVanik\Soap\Binding;
use VaclavVanik\Soap\Client\Exception\HttpConnection;
use VaclavVanik\Soap\Client\Exception\Runtime;
use VaclavVanik\Soap\Client\Exception\SoapFault;
use VaclavVanik\Soap\Client\Exception\ValueError;
use VaclavVanik\Soap\Client\HttpBindingClient;

final class HttpBindingClientTest extends TestCase
{
    use HttpProphecy;

    public function testRequestAndResponseNull(): void
    {
        /** @var ClientInterface $httpClient */
        $httpClient = $this->prophesizeHttpClient()->reveal();

        /** @var Binding\Binding|ObjectProphecy $binding */
        $binding = $this->prophesizeBinding()->reveal();

        $httpBindingClient = new HttpBindingClient($httpClient, $binding);

        $this->assertNull($httpBindingClient->getRequest());
        $this->assertNull($httpBindingClient->getResponse());
    }

    public function testCall(): void
    {
        $operation = 'sayHello';

        /** @var RequestInterface $psrRequest */
        $psrRequest = $this->prophesizeHttpRequest()->reveal();

        /** @var ResponseInterface $psrResponse */
        $psrResponse = $this->prophesizeHttpResponse()->reveal();

        /** @var ClientInterface $httpClient */
        $httpClient = $this->prophesizeHttpClientSendRequest($psrRequest, $psrResponse)->reveal();

        $bindingResponse = new Binding\Response('foo');

        /** @var Binding\Binding|ObjectProphecy $binding */
        $binding = $this->prophesizeBindingRequest($psrRequest, $operation);
        $binding->response($operation, $psrResponse)->willReturn($bindingResponse);
        $binding = $binding->reveal();

        $httpBindingClient = new HttpBindingClient($httpClient, $binding);
        $result = $httpBindingClient->call($operation);

        $this->assertSame($bindingResponse->getResult(), $result->getResult());
        $this->assertSame($bindingResponse->getHeaders(), $result->getHeaders());
        $this->assertSame($psrRequest, $httpBindingClient->getRequest());
        $this->assertSame($psrResponse, $httpBindingClient->getResponse());
    }

    /** @return iterable<string, array{HttpBindingClient, string, string}> */
    public function provideCallException(): iterable
    {
        $operation = 'sayHello';

        /** @var ClientInterface $httpClient */
        $httpClient = $this->prophesizeHttpClient()->reveal();

        /** @var RequestInterface $psrRequest */
        $psrRequest = $this->prophesizeHttpRequest()->reveal();

        /** @var NetworkExceptionInterface $networkException */
        $networkException = $this->prophesizeNetworkExceptionWithRequest($psrRequest)->reveal();

        /** @var ClientInterface $httpClientNetworkException */
        $httpClientNetworkException = $this->prophesizeHttpClientSendRequestThrowsException(
            $psrRequest,
            $networkException,
        )->reveal();

        /** @var ClientInterface $httpClientRuntimeException */
        $httpClientRuntimeException = $this->prophesizeHttpClientSendRequestThrowsException(
            $psrRequest,
            new Runtime('foo'),
        )->reveal();

        /** @var Binding\Binding|ObjectProphecy $requestBinding */
        $requestBinding = $this->prophesizeBindingRequest($psrRequest, $operation)->reveal();

        /** @var Binding\Binding|ObjectProphecy $soapFaultBinding */
        $soapFaultBinding = $this->prophesizeBindingRequestWillThrow(
            new Binding\Exception\SoapFault(
                '1',
                'a',
            ),
            $operation,
        )->reveal();

        /** @var Binding\Binding|ObjectProphecy $valueErrorBinding */
        $valueErrorBinding = $this->prophesizeBindingRequestWillThrow(
            new Binding\Exception\ValueError(),
            $operation,
        )->reveal();

        yield HttpConnection::class => [
            new HttpBindingClient($httpClientNetworkException, $requestBinding),
            $operation,
            HttpConnection::class,
        ];

        yield Runtime::class => [
            new HttpBindingClient($httpClientRuntimeException, $requestBinding),
            $operation,
            Runtime::class,
        ];

        yield SoapFault::class => [
            new HttpBindingClient($httpClient, $soapFaultBinding),
            $operation,
            SoapFault::class,
        ];

        yield ValueError::class => [
            new HttpBindingClient($httpClient, $valueErrorBinding),
            $operation,
            ValueError::class,
        ];
    }

    /** @dataProvider provideCallException */
    public function testCallCatchException(
        HttpBindingClient $httpBindingClient,
        string $operation,
        string $exception
    ): void {
        $this->expectException($exception);

        $httpBindingClient->call($operation);
    }

    private function prophesizeBinding(): ObjectProphecy
    {
        return $this->prophesize(Binding\Binding::class);
    }

    /**
     * @param array<mixed, mixed>    $parameters
     * @param array<int, SoapHeader> $soapHeaders
     */
    private function prophesizeBindingRequest(
        RequestInterface $request,
        string $operation,
        array $parameters = [],
        array $soapHeaders = []
    ): ObjectProphecy {
        /** @var Binding\Binding|ObjectProphecy $binding */
        $binding = $this->prophesizeBinding();
        $binding->request($operation, $parameters, $soapHeaders)->willReturn($request);

        return $binding;
    }

    /**
     * @param array<mixed, mixed>    $parameters
     * @param array<int, SoapHeader> $soapHeaders
     */
    private function prophesizeBindingRequestWillThrow(
        Throwable $e,
        string $operation,
        array $parameters = [],
        array $soapHeaders = []
    ): ObjectProphecy {
        /** @var Binding\Binding|ObjectProphecy $binding */
        $binding = $this->prophesizeBinding();
        $binding->request($operation, $parameters, $soapHeaders)->willThrow($e);

        return $binding;
    }
}
