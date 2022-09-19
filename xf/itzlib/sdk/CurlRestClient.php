<?php

/**
 * Class CurlRestClient
 */

namespace itzlib\sdk;

class CurlRestClient
{
    /**
     * @var $url null
     */
    private $url;
    /**
     * @var $header array
     */
    private $header;
    /**
     * @var $auth array
     */
    private $auth;

    /**
     * @var $options array
     */
    private $options = [];

    private $curl;

    /**
     * @param null $url
     * @param array $header
     * @param array $auth
     * @param array $options
     */
    public function __construct($url = null, $header = [], $auth = [], $options = [])
    {
        $this->url = $url;
        $this->header = $header;
        $this->auth = $auth;
        $this->options = $options;
    }

    /**
     * function setHeader
     * @param array $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }

    /**
     * function setAuth
     * @param array $auth
     */
    public function setAuth($auth)
    {
        $this->auth = $auth;
    }

    /**
     * function setOptions
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $header
     * @param array $data
     * @param array $auth
     * @param bool $forceInit
     * @return mixed
     * @throws \Exception
     */
    public function executeQuery($url, $method = 'GET', $header = [], $data = [], $auth = [], $forceInit = false)
    {

        if (true === $forceInit) {
            $this->close(); // close previous channel
        }

        if (null === $this->curl) {
            $this->curl = curl_init();
        }

        if ($method == 'GET')
            $url = $url . '?' . http_build_query($data);

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 10);

        if (!empty($auth)) {
            curl_setopt($this->curl, CURLOPT_HTTPAUTH, $auth['CURLOPT_HTTPAUTH']);
            curl_setopt($this->curl, CURLOPT_USERPWD, $auth['username'] . ':' . $auth['password']);
        }

        if (is_array($this->options)) {
            foreach ($this->options as $option => $value) {
                curl_setopt($this->curl, $option, $value);
            }
        }

        if ($method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
        } elseif ($method == 'PUT') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "PUT");
        } elseif ($method == 'DELETE') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        if ($method != 'GET') {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $content = curl_exec($this->curl);

        if (curl_errno($this->curl)) {
            throw new \Exception(curl_error($this->curl), curl_errno($this->curl));
        }
        $resCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        if ($resCode > 399 && $resCode < 599) {
            throw new \Exception($content, $resCode);
        }

        return $content;
    }

    /**
     * function call
     * @param string $method
     * @param string $segment
     * @param array $data
     * @return array
     */
    private function call($method, $segment, $data = [])
    {
        return $this->executeQuery($this->url . '/' . $segment, $method, $this->header, $data, $this->auth);
    }

    /**
     * function get
     * @param string $segment
     * @param array $data
     * @return array
     */
    public function get($segment, $data = [])
    {
        return $this->call('GET', $segment, $data);
    }

    /**
     * function post
     * @param string $segment
     * @param array $data
     * @return array
     */
    public function post($segment, $data)
    {
        return $this->call('POST', $segment, $data);
    }

    /**
     * function put
     * @param string $segment
     * @param array $data
     * @return array
     */
    public function put($segment, $data)
    {
        return $this->call('PUT', $segment, $data);
    }

    /**
     * function delete
     * @param string $segment
     * @param array $data
     * @return array
     */
    public function delete($segment, $data)
    {
        return $this->call('DELETE', $segment, $data);
    }

    /**
     * function getName
     * @return string
     */
    public function getName()
    {
        return 'curl';
    }

    public function getHttpInfo($opt = null)
    {
        return $this->curl ? curl_getinfo($this->curl, $opt) : false;
    }

    public function close()
    {
        if (null !== $this->curl) {
            curl_close($this->curl);
        }

        $this->curl = null;
    }

}
