<?php

namespace Smoren\MultiCurl;

use RuntimeException;

/**
 * MultiCurl wrapper for making parallel HTTP requests
 * @author <ofigate@gmail.com> Smoren
 */
class MultiCurl
{
    /**
     * @var int max parallel connections count
     */
    protected $maxConnections;
    /**
     * @var array<int, mixed> common CURL options for every request
     */
    protected $commonOptions = [];
    /**
     * @var array<string, string> common headers for every request
     */
    protected $commonHeaders = [];
    /**
     * @var array<string, array<int, mixed>> map of CURL options including headers by custom request ID
     */
    protected $requestsConfigMap = [];

    /**
     * MultiCurl constructor
     * @param int $maxConnections max parallel connections count
     * @param array<int, mixed> $commonOptions common CURL options for every request
     * @param array<string, string> $commonHeaders common headers for every request
     */
    public function __construct(
        int $maxConnections = 100,
        array $commonOptions = [],
        array $commonHeaders = []
    ) {
        $this->maxConnections = $maxConnections;
        $this->commonOptions = [
            CURLOPT_URL => '',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => ''
        ];

        foreach($commonOptions as $key => $value) {
            $this->commonOptions[$key] = $value;
        }

        $this->commonHeaders = $commonHeaders;
    }

    /**
     * Executes all the requests and returns their results map
     * @param bool $dataOnly if true: return only response body data, exclude status code and headers
     * @param bool $okOnly if true: return only responses with (200 <= status code < 300)
     * @return array<string, mixed> responses mapped by custom request IDs
     * @throws RuntimeException
     */
    public function makeRequests(bool $dataOnly = false, bool $okOnly = false): array
    {
        $runner = new MultiCurlRunner($this->requestsConfigMap, $this->maxConnections);
        $runner->run();
        $this->requestsConfigMap = [];

        return $dataOnly ? $runner->getResultData($okOnly) : $runner->getResult($okOnly);
    }

    /**
     * Adds new request to execute
     * @param string $requestId custom request ID
     * @param string $url URL for request
     * @param mixed $body body data (will be json encoded if array)
     * @param array<int, mixed> $options CURL request options
     * @param array<string, string> $headers request headers
     * @return self
     */
    public function addRequest(
        string $requestId,
        string $url,
        $body = null,
        array $options = [],
        array $headers = []
    ): self {
        foreach($this->commonOptions as $key => $value) {
            if(!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        foreach($this->commonHeaders as $key => $value) {
            if(!array_key_exists($key, $headers)) {
                $headers[$key] = $value;
            }
        }

        if(is_array($body)) {
            $body = json_encode($body);
        }

        if($body !== null) {
            /** @var string|numeric $body */
            $options[CURLOPT_POSTFIELDS] = strval($body);
        }

        $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
        $options[CURLOPT_URL] = $url;

        $this->requestsConfigMap[$requestId] = $options;

        return $this;
    }

    /**
     * Formats headers from associative array
     * @param array<string, string> $headers request headers associative array
     * @return array<string> headers array to pass to curl_setopt_array
     */
    protected function formatHeaders(array $headers): array
    {
        $result = [];

        foreach($headers as $key => $value) {
            $result[] = "{$key}: {$value}";
        }

        return $result;
    }
}
