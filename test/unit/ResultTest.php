<?php

declare(strict_types=1);

namespace VaclavVanikTest\Soap\Client;

use PHPUnit\Framework\TestCase;
use VaclavVanik\Soap\Client\Result;

final class ResultTest extends TestCase
{
    public function testResponse(): void
    {
        $result = 'foo';
        $headers = [];

        $response = new Result($result, $headers);

        $this->assertSame($result, $response->getResult());
        $this->assertSame($headers, $response->getHeaders());
    }
}
