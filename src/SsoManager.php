<?php namespace Zhimei\sso;


trait SsoManager {

    /**
     * @param $parameters
     * @return string
     */
    public function getSignature($parameters, $app_secret){
        ksort($parameters);
        $paramstring = "";
        foreach($parameters as $key => $value)
        {
            if($key=='signature'){
                continue;
            }
            $paramstring .= $key . "=" . $value."&";
        }
        $paramstring .= "key=" . $app_secret;
        $paramstring = strtoupper(md5($paramstring));
        return $paramstring;
    }

    /**
     * Dynamically pass methods to the default connection.
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws \Exception
     */
	public function __call($method, $parameters)
	{
        if(isset($this->model) && method_exists($this->model, $method)){
            return call_user_func_array(array($this->model, $method), $parameters);
        }
        throw new \Exception("Command not found!");
	}

}
