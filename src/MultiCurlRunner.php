<?php

namespace Smoren\MultiCurl;

use RuntimeException;

/**
 * Class to run MultiCurl requests and get responses
 * @author <ofigate@gmail.com> Smoren
 */
class MultiCurlRunner
{
    /**
     * @var resource MultiCurl resource
     */
    protected $mh;
    /**
     * @var array<int, string> map [workerId => customRequestId]
     */
    protected $workersMap;
    /**
     * @var array<resource> unemployed workers stack
     */
    protected $unemployedWorkers;
    /**
     * @var int max parallel connections count
     */
    protected $maxConnections;
    /**
     * @var array<string, array<int, mixed>> map of CURL options including headers by custom request ID
     */
    protected $requestsConfigMap;
    /**
     * @var array<string, array<string, mixed>> responses mapped by custom request ID
     */
    protected $result;

    /**
     * MultiCurlRunner constructor
     * @param array<string, array<int, mixed>> $requestsConfigMap map of CURL options
     * including headers by custom request ID
     * @param int $maxConnections max parallel connections count
     */
    public function __construct(array $requestsConfigMap, int $maxConnections)
    {
        $this->requestsConfigMap = $requestsConfigMap;
        $this->maxConnections = min($maxConnections, count($requestsConfigMap));

        $mh = curl_multi_init();

        if(!$mh) {
            throw new RuntimeException("failed creating curl multi handle");
        }

        $this->mh = $mh;
        $this->workersMap = [];
        $this->unemployedWorkers = [];
        $this->result = [];
    }

    /**
     * Makes requests and stores responses
     * @return self
     * @throws RuntimeException
     */
    public function run(): self
    {
        for($i=0; $i<$this->maxConnections; ++$i) {
            /** @var resource|false $unemployedWorker */
            $unemployedWorker = curl_init();
            if(!$unemployedWorker) {
                throw new RuntimeException("failed creating unemployed worker #{$i}");
            }
            $this->unemployedWorkers[] = $unemployedWorker;
        }
        unset($i, $this->unemployedWorker);

        foreach($this->requestsConfigMap as $id => $options) {
            while(!count($this->unemployedWorkers)) {
                $this->doWork();
            }

            $options[CURLOPT_HEADER] = 1;

            $newWorker = array_pop($this->unemployedWorkers);

            if(!curl_setopt_array($newWorker, $options)) {
                $errNo = curl_errno($newWorker);
                $errMess = curl_error($newWorker);
                $errData = var_export($options, true);
                throw new RuntimeException("curl_setopt_array failed: {$errNo} {$errMess} {$errData}");
            }

            $this->workersMap[(int)$newWorker] = $id;
            curl_multi_add_handle($this->mh, $newWorker);
        }
        unset($options);

        while(count($this->workersMap) > 0) {
            $this->doWork();
        }

        foreach($this->unemployedWorkers as $unemployedWorker) {
            curl_close($unemployedWorker);
        }

        curl_multi_close($this->mh);

        return $this;
    }

    /**
     * Returns response:
     * [customRequestId => [code => statusCode, headers => [key => value, ...], body => responseBody], ...]
     * @param bool $okOnly if true: return only responses with (200 <= status code < 300)
     * @return array<string, array<string, mixed>> responses mapped by custom request IDs
     */
    public function getResult(bool $okOnly = false): array
    {
        $result = [];

        foreach($this->result as $key => $value) {
            if(!$okOnly || $value['code'] === 200) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns response bodies:
     * [customRequestId => responseBody, ...]
     * @param bool $okOnly if true: return only responses with (200 <= status code < 300)
     * @return array<string, mixed> responses mapped by custom request IDs
     */
    public function getResultData(bool $okOnly = false): array
    {
        $result = [];

        foreach($this->result as $key => $value) {
            if(!$okOnly || $value['code'] >= 200 && $value['code'] < 300) {
                $result[$key] = $value['body'];
            }
        }

        return $result;
    }

    /**
     * Manages workers during making the requests
     * @return void
     */
    protected function doWork(): void
    {
        assert(count($this->workersMap) > 0, "work() called with 0 workers!!");
        $stillRunning = null;

        while(true) {
            do {
                $err = curl_multi_exec($this->mh, $stillRunning);
            } while($err === CURLM_CALL_MULTI_PERFORM);

            if($err !== CURLM_OK) {
                $errInfo = [
                    "multi_exec_return" => $err,
                    "curl_multi_errno" => curl_multi_errno($this->mh),
                    "curl_multi_strerror" => curl_multi_strerror($err)
                ];

                $errData = str_replace(["\r", "\n"], "", var_export($errInfo, true));
                throw new RuntimeException("curl_multi_exec error: {$errData}");
            }
            if($stillRunning < count($this->workersMap)) {
                // some workers has finished downloading, process them
                // echo "processing!";
                break;
            } else {
                // no workers finished yet, sleep-wait for workers to finish downloading.
                curl_multi_select($this->mh, 1);
                // sleep(1);
            }
        }
        while(($info = curl_multi_info_read($this->mh)) !== false) {
            if($info['msg'] !== CURLMSG_DONE) {
                // no idea what this is, it's not the message we're looking for though, ignore it.
                continue;
            }

            if($info['result'] !== CURLM_OK) {
                $errInfo = [
                    "effective_url" => curl_getinfo($info['handle'], CURLINFO_EFFECTIVE_URL),
                    "curl_errno" => curl_errno($info['handle']),
                    "curl_error" => curl_error($info['handle']),
                    "curl_multi_errno" => curl_multi_errno($this->mh),
                    "curl_multi_strerror" => curl_multi_strerror(curl_multi_errno($this->mh))
                ];

                $errData = str_replace(["\r", "\n"], "", var_export($errInfo, true));
                throw new RuntimeException("curl_multi worker error: {$errData}");
            }

            $ch = $info['handle'];
            $chIndex = (int)$ch;

            $this->result[$this->workersMap[$chIndex]] = $this->parseResponse(curl_multi_getcontent($ch));

            unset($this->workersMap[$chIndex]);
            curl_multi_remove_handle($this->mh, $ch);
            $this->unemployedWorkers[] = $ch;
        }
    }

    /**
     * Parses the response
     * @param string $response raw HTTP response
     * @return array<string, mixed> [code => statusCode, headers => [key => value, ...], body => responseBody]
     */
    protected function parseResponse(string $response): array
    {
        $arResponse = explode("\r\n\r\n", $response);

        $arHeaders = [];
        $statusCode = null;
        $body = null;

        while(count($arResponse)) {
            $respItem = array_shift($arResponse);

            $line = (string)strtok($respItem, "\r\n");
            $statusCodeLine = trim($line);
            if(preg_match('|HTTP/[\d.]+\s+(\d+)|', $statusCodeLine, $matches)) {
                $arHeaders = [];

                if(isset($matches[1])) {
                    $statusCode = (int)$matches[1];
                } else {
                    $statusCode = null;
                }

                // Parse the string, saving it into an array instead
                while(($line = strtok("\r\n")) !== false) {
                    if(($matches = explode(':', $line, 2)) !== false) {
                        $arHeaders[trim(mb_strtolower($matches[0]))] = trim(mb_strtolower($matches[1]));
                    }
                }
            } else {
                $contentType = $arHeaders['content-type'] ?? null;
                if($contentType === 'application/json') {
                    $body = json_decode($respItem, true);
                } else {
                    $body = $respItem;
                }
            }
        }

        return [
            'code' => $statusCode,
            'headers' => $arHeaders,
            'body' => $body,
        ];
    }
}
