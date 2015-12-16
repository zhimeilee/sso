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
    abstract public function getUserByUsername($username);

    /**
     * @param $user_id
     * @return mixed
     */
    abstract public function getUserById($user_id);

}
