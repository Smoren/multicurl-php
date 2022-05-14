<?php

namespace Smoren\MultiCurl\Tests\Unit;


use Smoren\MultiCurl\MultiCurl;

class MultiCurlTest extends \Codeception\Test\Unit
{
    public function testDefaultDelimiter()
    {
        $mc = new MultiCurl(10, [
            CURLOPT_POST => true,
            CURLOPT_FOLLOWLOCATION => 1,
        ], [
            'Content-Type' => 'application/json',
        ]);

        $mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
        $mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);

        $result = $mc->makeRequests();

        foreach($result as &$item) {
            unset($item['headers']['Date']);
            unset($item['body']['headers']['X-Amzn-Trace-Id']);
        }
        unset($item);

        $expected = [
            1 => [
                "code" => 200,
                "headers" => [
                    //"Date" => "Sat, 14 May 2022 08:27:37 GMT",
                    "Content-Type" => "application/json",
                    "Content-Length" => "459",
                    "Connection" => "keep-alive",
                    "Server" => "gunicorn/19.9.0",
                    "Access-Control-Allow-Origin" => "*",
                    "Access-Control-Allow-Credentials" => "true"
                ],
                "body" => [
                    "args" => [
                    ],
                    "data" => '{"some":"data"}',
                    "files" => [
                    ],
                    "form" => [
                    ],
                    "headers" => [
                        "Accept" => "*/*",
                        "Accept-Encoding" => "deflate, gzip",
                        "Content-Length" => "15",
                        "Content-Type" => "application/json",
                        "Host" => "httpbin.org",
                        //"X-Amzn-Trace-Id" => "Root=1-627f67f9-234d420863f83b915d3fa540"
                    ],
                    "json" => [
                        "some" => "data"
                    ],
                    "method" => "POST",
                    "origin" => "46.22.56.202",
                    "url" => "https://httpbin.org/anything"
                ]
            ],
            2 => [
                "code" => 200,
                "headers" => [
                    //"Date" => "Sat, 14 May 2022 08:27:37 GMT",
                    "Content-Type" => "application/json",
                    "Content-Length" => "475",
                    "Connection" => "keep-alive",
                    "Server" => "gunicorn/19.9.0",
                    "Access-Control-Allow-Origin" => "*",
                    "Access-Control-Allow-Credentials" => "true"
                ],
                "body" => [
                    "args" => [
                    ],
                    "data" => '{"some":"another data"}',
                    "files" => [
                    ],
                    "form" => [
                    ],
                    "headers" => [
                        "Accept" => "*/*",
                        "Accept-Encoding" => "deflate, gzip",
                        "Content-Length" => "23",
                        "Content-Type" => "application/json",
                        "Host" => "httpbin.org",
                        //"X-Amzn-Trace-Id" => "Root=1-627f67f9-65ec4a9d2b204e2576e7564f"
                    ],
                    "json" => [
                        "some" => "another data"
                    ],
                    "method" => "POST",
                    "origin" => "46.22.56.202",
                    "url" => "https://httpbin.org/anything"
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }
}
