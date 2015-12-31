<?php namespace Zhimei\sso;


class SsoServer {

    use SsoManager;

    public $model;
    public $clients;
    protected $sso_app_secret;
    protected $redirect_url = null;

    public $lifetime = 120; // minutes

    /**
     * @param $config
     * @param SsoServerModeAbstract $model
     * @throws SsoAuthenticationException
     */
    public function __construct($config, SsoServerModeAbstract $model)
    {

        if (!$config['driver']){
            throw new SsoAuthenticationException("SSO server driver not specified");
        }

        $this->model        = $model;
        $this->clients      = $config['clients'];
        $this->cache_driver = app('cache')->store($config['driver']);
        $this->lifetime     = env('SSO_CACHE_LIFETIME', 120);
    }


    /**
     * Attach a user session to a broker session
     */
    public function attach()
    {

        $token = app('request')->input('token', '');
        $token = preg_replace('/[^a-z0-9]/i', '', $token);
        if(strlen($token)<20){
            throw new SsoAuthenticationException('Token is invalid!');
        }
        $app_info = $this->getClientInfo();
        $this->checkSignature();
        $access_token = md5($token.$app_info['app_secret']);
        $session_id = $this->getSessionIdByAccessToken($access_token);
        if($session_id){
            $userInfo = $this->getUserBySessionId($session_id);
            if(empty($userInfo)){
                return view('sso::login');
            }else{
                return redirect($this->getRedirectUrl());
            }
        }else{
            $session_id = app('session')->getId();
            $this->setSessionIdByAccessToken($access_token, $session_id);
            $userInfo = $this->getUserBySessionId($session_id);
            if(empty($userInfo)){
                return view('sso::login');
            }else{
                return redirect($this->getRedirectUrl());
            }
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector
     * @throws SsoAuthenticationException
     */
    public function login(){
        $username = app('request')->input('username');
        $password = app('request')->input('password');
        if(empty($username) || empty($password)){
            return redirect()->back()->with('error', 'Username or Password is required!');
        }
        if(!$this->model->authenticate($username, $password)){
            return redirect()->back()->with('error', 'Username or Password is incorrect!');
        }
        $userInfo = $this->model->getUserByUsername($username);
        $this->setUserBySessionId(app('session')->getId(), $userInfo);
        return redirect($this->getRedirectUrl());
    }

    /**
     * @param int $user_id
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector
     * @throws SsoAuthenticationException
     */
    public function loginById($user_id){

        $userInfo = $this->model->getUserById($user_id);
        $this->setUserBySessionId(app('session')->getId(), $userInfo);
        return redirect($this->getRedirectUrl());
    }

    /**
     * @return null|string
     * @throws SsoAuthenticationException
     */
    public function getRedirectUrl(){
        if(empty($this->redirect_url)){
            $app_info = $this->getClientInfo();
            $redirect_url = $app_info['return_url'];
        }else{
            $redirect_url = $this->redirect_url;
        }

        if(app('request')->input('state')){
            if(strpos($redirect_url, '?')===false){
                $redirect_url .= '?state='.app('request')->input('state');
            }else{
                $redirect_url .= '&state='.app('request')->input('state');
            }
        }
        return $redirect_url;
    }

    /**
     * @param $redirect_url
     */
    public function setRedirectUrl($redirect_url){
        $this->redirect_url = $redirect_url;
    }

    /**
     * @throws SsoAuthenticationException
     */
    public function logout(){
        $this->checkSignature();
        $access_token = app('request')->input('access_token');
        if(empty($access_token)){
            throw new SsoAuthenticationException('access_token not specified');
        }
        $session_id = $this->getSessionIdByAccessToken($access_token);
        if(!empty($session_id)){
            $this->setUserBySessionId($session_id, null);
            $this->setSessionIdByAccessToken($access_token, null);
        }
        app('session')->flush();
    }

    /**
     * @return mixed|null
     * @throws SsoAuthenticationException
     */
    public function userInfo(){
        $this->checkSignature();
        $access_token = app('request')->input('access_token');
        if(empty($access_token)){
            throw new SsoAuthenticationException('access_token not specified');
        }
        $session_id = $this->getSessionIdByAccessToken($access_token);
        if(empty($session_id)){
            return null;
        }
        app('session')->flush();
        return $this->getUserBySessionId($session_id, null);
    }

    /**
     * @param $session_id
     * @param null $userInfo
     */
    protected function setUserBySessionId($session_id, $userInfo=null){
        if($userInfo===null){
            $this->cache_driver->forget('SSO_user_info_by_'.$session_id);
        }else{
            $this->cache_driver->put('SSO_user_info_by_'.$session_id, $userInfo, $this->lifetime);
        }
    }

    /**
     * @param $session_id
     * @return mixed
     */
    protected function getUserBySessionId($session_id){
        return $this->cache_driver->get('SSO_user_info_by_'.$session_id, null);
    }

    /**
     * @param $access_token
     * @param null $session_id
     */
    protected function setSessionIdByAccessToken($access_token, $session_id=null){
        if($session_id===null){
            $this->cache_driver->forget('SSO_session_id_by_'.$access_token);
        }else{
            $this->cache_driver->put('SSO_session_id_by_'.$access_token, $session_id, $this->lifetime);
        }
    }

    /**
     * @param $access_token
     * @return mixed
     */
    protected function getSessionIdByAccessToken($access_token){
        return $this->cache_driver->get('SSO_session_id_by_'.$access_token, null);
    }

    /**
     * @return bool
     * @throws SsoAuthenticationException
     */
    protected function checkSignature(){

        $params = app('request')->query();
        $client_info = $this->getClientInfo();
        if($this->getSignature($params, $client_info['app_secret']) != app('request')->input('signature')){
            throw new SsoAuthenticationException('Signature is invalid!');
        }
        return true;
    }

    /**
     * @return mixed
     * @throws SsoAuthenticationException
     */
    public function getClientInfo(){
        $app_id = app('request')->input('app_id', app('request')->input('state'));
        if(!isset($this->clients[$app_id])){
            throw new SsoAuthenticationException('app id is invalid!');
        }
        return $this->clients[$app_id];
    }

}
