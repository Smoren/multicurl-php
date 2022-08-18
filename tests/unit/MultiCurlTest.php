<?php

namespace Smoren\MultiCurl\Tests\Unit;

use Smoren\MultiCurl\MultiCurl;

class MultiCurlTest extends \Codeception\Test\Unit
{
    public function testDefaultDelimiter()
    {
        $mc = new MultiCurl(2, [
            CURLOPT_POST => true,
            CURLOPT_FOLLOWLOCATION => 1,
        ], [
            'Content-Type' => 'application/json',
        ]);

        $mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
        $mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);
        $result = $mc->makeRequests(false, false);
        $this->assertEquals($this->getExpected(false), $this->formatResult($result, false));

        $mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
        $mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);
        $mc->addRequest(3, 'https://httpbin.org/status/500', ['bad' => 'request']);

        $result = $mc->makeRequests(false, true);
        $this->assertEquals($this->getExpected(false), $this->formatResult($result, false));

        $mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
        $mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);

        $result = $mc->makeRequests(true, false);
        $this->assertEquals($this->getExpected(true), $this->formatResult($result, true));

        $mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
        $mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);
        $mc->addRequest(3, 'https://httpbin.org/status/500', ['bad' => 'request']);

        $result = $mc->makeRequests(true, true);
        $this->assertEquals($this->getExpected(true), $this->formatResult($result, true));

        for($i=1; $i<=5; ++$i) {
            $mc->addRequest($i, 'https://httpbin.org/anything', ['id' => $i]);
        }
        $result = $mc->makeRequests(true, false);
        $this->assertCount(5, $result);
        for($i=1; $i<=5; ++$i) {
            $this->assertEquals($i, $result[$i]['json']['id'] ?? null);
        }
    }

    protected function formatResult(array $result, bool $dataOnly): array
    {
        foreach($result as &$item) {
            if($dataOnly) {
                $item = $item['json'];
            } else {
                $item['headers'] = [
                    'content-type' => $item['headers']['content-type'],
                ];
                $item['body'] = $item['body']['json'];
            }
        }
        unset($item);

        return $result;
    }

    protected function getExpected(bool $dataOnly): array
    {
        $result = [
            1 => [
                "code" => 200,
                "headers" => [
                    "content-type" => "application/json",
                ],
                "body" => [
                    "some" => "data"
                ]
            ],
            2 => [
                "code" => 200,
                "headers" => [
                    "content-type" => "application/json",
                ],
                "body" => [
                    "some" => "another data"
                ]
            ]
        ];

        if($dataOnly) {
            foreach($result as &$item) {
                $item = $item['body'];
            }
        }

        return $result;
    }
}
