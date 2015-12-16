<?php namespace Zhimei\sso;

use GuzzleHttp\Client;

class SsoClient {

    use SsoManager;


    /**
     * Session token of the client
     * @var string
     */
    public $token = null;

    /**
     * Url of SSO server
     * @var string
     */
    protected $sso_server_url;
    /**
     * My identifier, given by SSO provider.
     * @var string
     */
    public $sso_app_id;
    /**
     * My secret word, given by SSO provider.
     * @var string
     */
    protected $sso_app_secret;


    /**
     * Class constructor
     * @param $config
     * @throws SsoAuthenticationException
     */
    public function __construct($config)
    {

        if (!$config['sso_server_url'])
            throw new SsoAuthenticationException("SSO server URL not specified");
        if (!$config['sso_app_id'])
            throw new SsoAuthenticationException("SSO client app_id not specified");
        if (!$config['sso_app_secret'])
            throw new SsoAuthenticationException("SSO client app_secret not specified");

        $this->sso_server_url   = $config['sso_server_url'];
        $this->sso_app_id       = $config['sso_app_id'];
        $this->sso_app_secret   = $config['sso_app_secret'];

        $this->token = session($this->getCacheName(), null);

    }

    /**
     * Get the session name.
     *
     * Note: Using the app_id in the session name.
     * This resolves issues when multiple client are on the same domain.
     *
     * @return string
     */
    protected function getCacheName()
    {
        return 'SSO_client_token_' . preg_replace('/[_\W]+/', '_', strtolower($this->sso_app_id));
    }

    /**
     * Generate session token
     */
    public function generateToken()
    {
        if (!empty($this->token)){
            return;
        }

        $this->token = base_convert(md5(uniqid(rand(), true)), 16, 36);
        session([$this->getCacheName()=>$this->token]);

    }

    /**
     * Check if we have an SSO token.
     * @return bool
     */
    public function isAttached()
    {
        return !empty($this->token);
    }

    /**
     * Get URL to attach session at SSO server.
     *
     * @param array $params
     * @return string
     */
    public function getAttachUrl($params = [])
    {
        $this->generateToken();

        $data = [
                'command'   => 'attach',
                'app_id'    => $this->sso_app_id,
                'token'     => $this->token
            ] + app('request')->query() + $params;

        $data['signature'] = $this->getSignature($data, $this->sso_app_secret);
        return $this->sso_server_url . "?" . http_build_query($data);
    }

    /**
     * Attach our session to the user's session on the SSO server.
     * @param null $state
     * @return bool
     */
    public function attach($state = null)
    {
        if ($this->isAttached()){
            return true;
        }
        $params = [];
        if (!empty($state)) {
            $params = ['state' => $state];
        }
        $url = $this->getAttachUrl($params);
        header("Location: $url", true, 307);
        echo "You're redirected to <a href='$url'>$url</a>";
        return false;
    }

    /**
     * Re-Attach our session to the user's session on the SSO server.
     * @param null $state
     * @return bool
     */
    public function reAttach($state = null)
    {
        $params = [];
        if (!empty($state)) {
            $params = ['state' => $state];
        }
        $url = $this->getAttachUrl($params);
        header("Location: $url", true, 307);
        echo "You're redirected to <a href='$url'>$url</a>";
        return false;
    }

    /**
     * Get the request url for a command
     *
     * @param string $command
     * @param array  $params   Query parameters
     * @return string
     */
    protected function getRequestUrl($command, $params = [])
    {
        $params['command']      = $command;
        $params['app_id']       = $this->sso_app_id;
        $params['access_token'] = md5($this->token.$this->sso_app_secret);
        $params['signature']    = $this->getSignature($params, $this->sso_app_secret);
        return $this->sso_server_url . '?' . http_build_query($params);
    }

    /**
     * @param $method
     * @param $command
     * @param null $data
     * @return mixed|null
     * @throws SsoAuthenticationException
     */
    protected function request($method, $command, $data = null)
    {
        $url = $this->getRequestUrl($command, !$data || $method === 'POST' ? [] : $data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        if ($method === 'POST' && !empty($data)) {
            $post = is_string($data) ? $data : http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch) != 0) {
            throw new SsoAuthenticationException("Server request failed: " . curl_error($ch), 500);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        list($contentType) = explode(';', curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
//        if ($contentType != 'application/json') {
//            $message = "Expected application/json response, got $contentType";
//            error_log($message . "\n\n" . $response);
//            throw new SsoAuthenticationException($message, $httpCode);
//        }
        $data = json_decode($response, true);
        if ($httpCode >= 400) throw new SsoAuthenticationException($data['error'] ?: $response, $httpCode);
        return $data;
    }

    /**
     * Logout at sso server.
     */
    public function logout()
    {
        $this->request('GET', 'logout');
    }
    /**
     * Get user information.
     *
     * @return object|null
     */
    public function getUserInfo()
    {
        if (!isset($this->userinfo)) {
            $this->userinfo = $this->request('GET', 'userInfo');
        }
        return $this->userinfo;
    }

    /**
     * Magic method to do arbitrary request
     *
     * @param string $fn
     * @param array  $args
     * @return mixed
     */
    public function __call($fn, $args)
    {
        $sentence = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $fn));
        $parts = explode(' ', $sentence);

        $method = count($parts) > 1 && in_array(strtoupper($parts[0]), ['GET', 'DELETE'])
            ? strtoupper(array_shift($parts))
            : 'POST';
        $command = join('-', $parts);

        return $this->request($method, $command, $args);
    }

}
