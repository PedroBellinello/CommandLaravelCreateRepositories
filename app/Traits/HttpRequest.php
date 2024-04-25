<?php

namespace App\Traits;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

trait HttpRequest
{
    use ApiResponses;
    private array $headers = [];
    private array $options = [];
    private mixed $payload = [];
    private mixed $baseUrl, $endPoint, $response, $httpResponse, $httpMethod, $token, $username, $password, $attachments;
    private $params = null;
    private bool $showResponse = true;
    private array $config = [
        'globalPayload' => [],
        "callbackUrl" => "",
        "headers" => [
            'Content-Type'  => 'application/json',
            'accept' => 'application/json',
            'authorization' => null
        ],
        'httpOptions' => [
            "verify" => false
        ]
    ];
    private bool $new = false;
    private bool $clear = false;
    private bool $clean = false;
    private bool $useTokenApplication = false;

    public function setBearerToken($token = null): static
    {
        $this->headers['authorization'] = sprintf("Bearer %s", ($token ?? $this->token));

        return $this;
    }

    public function setBasicAuth($username = null, $password = null): static
    {
        $this->token = base64_encode(sprintf("%s:%s",
            ($username ?? $this->username), ($password ?? $this->password)));

        $this->headers['authorization'] = sprintf("Basic %s", $this->token);

        return $this;
    }

    public function setHeader(string|array $keyOrValues, $value = null)
    {

        if(is_array($keyOrValues)) {

            $this->headers = array_merge($keyOrValues, $this->headers);

        }else {

            $this->headers[$keyOrValues] = $value;

        }

        return $this;
    }

    public function getBaseUrl(): mixed
    {
        return $this->baseUrl;
    }

    public function setParams(array $params = null, $prefix = "", $separator = null): static
    {
        $this->params = "";

        if(!empty($params))
            $this->params = "?" . http_build_query($params, $prefix, $separator);

        return $this;
    }

    public function url(string $baseUrlOrEndPoint = "", $method = null): static
    {
        $this->httpMethod = $method;
        $this->endPoint = $this->baseUrl . $baseUrlOrEndPoint;

        if($this->clear || $this->new)
            $this->endPoint = $baseUrlOrEndPoint;

        return $this;
    }

    public function baseUrl($http_Url, $method = null): static
    {
        $this->httpMethod = $method;
        $this->endPoint = $http_Url;
        return $this;
    }

    public function body($body, array $headers = [], array $options = []): static
    {
        if(!empty($headers)) $this->headers = $headers;
        if(!empty($options)) $this->options = $options;

        $this->payload = $body;
        return $this;
    }

    public function request($method, $timeout= 30)
    {
        if(!$this->clear || !$this->new)
            $this->endPoint = empty($this->endPoint) ? $this->baseUrl : $this->endPoint;

        if($this->useTokenApplication)
            $this->headers['authorization'] = request()->header('Authorization');

        $headers = $this->clean ? $this->headers : array_merge($this->config['headers'],$this->headers);

        $options = $this->clean ? $this->options : array_merge($this->options, $this->config['httpOptions']);

        $this->payload = array_merge($this->payload, $this->config['globalPayload']);

        return Http::withHeaders($headers)->withOptions($options)->timeout($timeout);
    }

    public function attach($name, $contents = '', $filename = null, $headers = []){

        $this->attachments = Http::attach($name, $contents, $filename, $headers);

        $this->attachments->withHeaders($this->headers);

        return $this;

    }

    public function http($method = 'post', $timeout = 30): static
    {
        $method = ($this->httpMethod ?? $method);
        $response = $this->request($method, $timeout);

        $this->endPoint = $this->endPoint.$this->params;

        $this->response = call_user_func(array($response, strtolower($method)), $this->endPoint, $this->payload);

        return $this;
    }
    public function httpWithOutHeaders($method = 'post',$timeout = 30): static
    {
        $response = Http::timeout($timeout);

        $this->endPoint = $this->endPoint.$this->params;

        $this->response = call_user_func(array($response, strtolower($method)), $this->endPoint, $this->payload);

        return $this;
    }
    public function httpResponse($showResponse = true): \Illuminate\Http\JsonResponse|bool|null
    {
        if (!$this->response->successful()) {

            $response = [
                "url" => $this->endPoint,
                "headers" => $this->headers,
                "payload" => $this->payload,
                "body" => $this->response->body(),
                "json" => $this->response->json(),
            ];

            if(($this->showResponse ?? $showResponse))
                $this->failRequest("houve uma falha e a ação não pode ser executada", $response, $this->response->status());

        }

        return $this->response->successful();
    }
    
    public function send($timeout = 30)
    {
        if(!empty($this->attachments)){

            $this->response = $this->attachments->timeout($timeout)->post($this->endPoint, $this->payload);

            return $this->httpResponse();

        }

        return $this->http("POST", $timeout)->httpResponse();
    }

    public function sendAsync($method = "POST", $timeout = 30)
    {
        $method = ($this->httpMethod ?? $method);
        $response = $this->request($method, $timeout);

        $this->endPoint = $this->endPoint.$this->params;

        $this->response = $response->send($method, $this->endPoint, $this->payload);

        return $this;
    }

    public function call($timeout = 30): \Illuminate\Http\JsonResponse|bool|null
    {
        return $this->http("GET", $timeout)->httpResponse();
    }

    public function method($method, $timeout = 30, $showResponse = null): \Illuminate\Http\JsonResponse|bool|null
    {
        return $this->http($method, $timeout)->httpResponse($showResponse);
    }
    public function response($dump = false)
    {
        if($dump)
            $this->dump($this->response);

        return $this->response;
    }
    public function toRequest($dump = false): array
    {
        $request = [
            "url" => $this->endPoint,
            "headers" => $this->headers,
            "payload" => $this->payload,
            "code" => $this->response->status(),
            "body" => $this->response->body(),
            "json" => $this->response->json()
        ];

        if($dump)
            $this->dump($request);

        return $request;
    }
    public function toJson($dump = false)
    {
        if($dump)
            $this->dump($this->toRequest());

        return $this->response->json();
    }
    public function toBody($dump = false)
    {
        if($dump)
            $this->dump($this->toRequest());

        return $this->response->body();
    }

    public function fromJsonToArray(string $json = null)
    {
        return json_decode($json ?? $this->toBody(), true);
    }

    public function fromJsonToObject(string $json = null)
    {
        return json_decode($json ?? $this->toBody());
    }
    public function toStatus($dump = false)
    {
        if($dump)
            $this->dump($this->response->status());

        return $this->response->status();
    }
    public function status($dump = false)
    {
        if($dump)
            $this->dump($this->response->status());

        return $this->response->status();
    }
    public function payload()
    {
        return $this->payload;
    }
    public function headers()
    {
        return $this->headers;
    }
    public function clean()
    {
        $this->clean = true;
        return $this;
    }
    public function clear()
    {
        $this->clear = true;
        return $this;
    }
    public function new()
    {
        $this->new = true;
        return $this;
    }
    public function getResponseCollection(): Collection
    {
        return collect($this->response->json());
    }
    
    public function patch($timeout = 30)
    {
        $response = $this->request('patch', $timeout);
    
        $this->endPoint = $this->endPoint.$this->params;
    
        $this->response = call_user_func(array($response, 'patch'), $this->endPoint, $this->payload);
    
        return $this;
    }
   

}
