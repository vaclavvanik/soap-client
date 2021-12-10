<?php

declare(strict_types=1);

namespace VaclavVanik\Soap\Client;

use SoapHeader;

interface Client
{
    /**
     * @param array<mixed, mixed>    $parameters
     * @param array<int, SoapHeader> $soapHeaders

     * @throws Exception\HttpConnection
     * @throws Exception\SoapFault
     * @throws Exception\ValueError
     * @throws Exception\Exception
     */
    public function call(string $operation, array $parameters = [], array $soapHeaders = []): Result;
}
