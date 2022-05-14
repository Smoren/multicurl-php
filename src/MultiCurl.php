<?php


namespace Smoren\MultiCurl;


use RuntimeException;

/**
 * MultiCurl wrapper for making parallel HTTP requests
 */
class MultiCurl
{
    /**
     * @var int max parallel connections count
     */
    protected $maxConnections;
    /**
     * @var array common CURL options for every request
     */
    protected $commonOptions = [];
    /**
     * @var array common headers for every request
     */
    protected $commonHeaders = [];
    /**
     * @var array map of CURL options including headers by custom request ID
     */
    protected $requestsConfigMap = [];

    /**
     * MultiCurl constructor
     * @param int $maxConnections max parallel connections count
     * @param array $commonOptions common CURL options for every request
     * @param array $commonHeaders common headers for every request
     */
    public function __construct(
        int $maxConnections = 100, array $commonOptions = [], array $commonHeaders = []
    )
    {
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
     * @param bool $dataOnly if true: return only response body data, exclude status code and headers
     * @param bool $okOnly if true: return only responses with (200 <= status code < 300)
     * @return array responses mapped by custom request IDs
     * @throws RuntimeException
     */
    public function makeRequests(bool $dataOnly = false, bool $okOnly = false): array
    {
        $runner = new MultiCurlRunner($this->requestsConfigMap, $this->maxConnections);
        $runner->run();

        return $dataOnly ? $runner->getResultData($okOnly) : $runner->getResult($okOnly);
    }

    /**
     * @param string $requestId custom request ID
     * @param string $url URL for request
     * @param mixed $body body data (will be json encoded if array)
     * @param array $options CURL request options
     * @param array $headers request headers
     * @return self
     */
    public function addRequest(
        string $requestId, string $url, $body = null, array $options = [], array $headers = []
    ): self
    {
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
            $options[CURLOPT_POSTFIELDS] = (string)$body;
        }

        $options[CURLOPT_HTTPHEADER] = $this->formatHeaders($headers);
        $options[CURLOPT_URL] = $url;

        $this->requestsConfigMap[$requestId] = $options;

        return $this;
    }

    /**
     * Formats headers from associative array
     * @param array $headers request headers associative array
     * @return array headers array to pass to curl_setopt_array
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
