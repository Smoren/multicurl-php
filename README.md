# multicurl

Multi curl wrapper for making parallel HTTP requests

![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/smoren/multicurl)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Smoren/multicurl-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Smoren/multicurl-php/?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/Smoren/multicurl-php/badge.svg?branch=master)](https://coveralls.io/github/Smoren/multicurl-php?branch=master)
![Build and test](https://github.com/Smoren/multicurl-php/actions/workflows/test_master.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

### How to install to your project
```
composer require smoren/multicurl
```

### Unit testing
```
composer install
composer test-init
composer test
```

### Usage

```php
use Smoren\MultiCurl\MultiCurl;

$mc = new MultiCurl(10, [
    CURLOPT_POST => true,
    CURLOPT_FOLLOWLOCATION => 1,
], [
    'Content-Type' => 'application/json',
]);

$mc->addRequest(1, 'https://httpbin.org/anything', ['some' => 'data']);
$mc->addRequest(2, 'https://httpbin.org/anything', ['some' => 'another data']);

$result = $mc->makeRequests();
print_r($result);

/*
Array
(
    [1] => Array
        (
            [code] => 200
            [headers] => Array
                (
                    [Date] => Sat, 14 May 2022 08:21:35 GMT
                    [Content-Type] => application/json
                    [Content-Length] => 459
                    [Connection] => keep-alive
                    [Server] => gunicorn/19.9.0
                    [Access-Control-Allow-Origin] => *
                    [Access-Control-Allow-Credentials] => true
                )

            [body] => Array
                (
                    [args] => Array
                        (
                        )

                    [data] => {"some":"data"}
                    [files] => Array
                        (
                        )

                    [form] => Array
                        (
                        )

                    [headers] => Array
                        (
                            [Accept] => 
                            [Accept-Encoding] => deflate, gzip
                            [Content-Length] => 15
                            [Content-Type] => application/json
                            [Host] => httpbin.org
                            [X-Amzn-Trace-Id] => Root=1-627f668f-2c004f4e5817d2b508e0cd6c
                        )

                    [json] => Array
                        (
                            [some] => data
                        )

                    [method] => POST
                    [origin] => 46.22.56.202
                    [url] => https://httpbin.org/anything
                )

        )

    [2] => Array
        (
            [code] => 200
            [headers] => Array
                (
                    [Date] => Sat, 14 May 2022 08:21:36 GMT
                    [Content-Type] => application/json
                    [Content-Length] => 475
                    [Connection] => keep-alive
                    [Server] => gunicorn/19.9.0
                    [Access-Control-Allow-Origin] => *
                    [Access-Control-Allow-Credentials] => true
                )

            [body] => Array
                (
                    [args] => Array
                        (
                        )

                    [data] => {"some":"another data"}
                    [files] => Array
                        (
                        )

                    [form] => Array
                        (
                        )

                    [headers] => Array
                        (
                            [Accept] => 
                            [Accept-Encoding] => deflate, gzip
                            [Content-Length] => 23
                            [Content-Type] => application/json
                            [Host] => httpbin.org
                            [X-Amzn-Trace-Id] => Root=1-627f668f-67767ca73cdb2bf313afa566
                        )

                    [json] => Array
                        (
                            [some] => another data
                        )

                    [method] => POST
                    [origin] => 46.22.56.202
                    [url] => https://httpbin.org/anything
                )

        )

)
 */
```