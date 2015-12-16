<?php namespace Zhimei\sso;


abstract class SsoServerModeAbstract {

    /**
     * @param $username
     * @param $password
     * @return bool
     */
    abstract public function authenticate($username, $password);

    /**
     * @param $username
     * @return mixed
     */
    abstract public function getUserInfo($username);

}
