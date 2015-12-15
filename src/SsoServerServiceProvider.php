<?php namespace Zhimei\sso;

use Illuminate\Support\ServiceProvider;

class SsoServerServiceProvider extends ServiceProvider {

    /**
	 * Bootstrap the application.
	 *
	 * @return void
	 */
	public function boot()
	{
        if(function_exists('config_path')){
            $this->publishes([
                __DIR__.'/config/sso.php' => config_path('sso.php'),
            ]);
        }
        $this->loadViewsFrom(__DIR__.'/views', 'sso');
	}

    /**
     * Register the service provider.
     * @throws SsoAuthenticationException
     */
	public function register()
	{

        $config_server = $this->config();
        if (!$config_server['model']){
            throw new SsoAuthenticationException("SSO server model not specified");
        }
        if(!empty($config_server)) {
            $this->app['SsoServer'] = $this->app->share(function () use ($config_server) {
                $model = app()->make($config_server['model']);
                return new SsoServer($config_server, $model);
            });
        }
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('SsoServer');
	}

    /**
     * 兼容Laravel & Lumen的配置文件
     * @return array|mixed
     */
    public function config(){
        if(strpos(app()->version(), 'Lumen')===false){
            return config('sso.sso_server');
        }else{
            $clients = [];
            for($i=1;;$i++){
                $app_id     = env('SSO_CLIENT_APP_ID_'.$i);
                $app_secret = env('SSO_CLIENT_APP_SECRET_'.$i);
                $return_url = env('SSO_CLIENT_APP_RETURN_URL_'.$i);
                if(empty($app_id) || empty($app_secret) || empty($return_url)){
                    break;
                }
                $clients[$app_id] = ['app_id'=>$app_id, 'app_secret'=>$app_secret, 'return_url'=>$return_url];
            }
            return [
                'model'         => env('SSO_MODEL'),
                'driver'        => env('SSO_SERVER_DRIVER', 'file'),  //file or memcached or database
                'clients'       => $clients,
            ];
        }
    }

}
