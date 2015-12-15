<?php namespace Zhimei\sso;

use Illuminate\Support\ServiceProvider;

class SsoClientServiceProvider extends ServiceProvider {

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
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

        $config_client = $this->config();
        if(!empty($config_client)) {
            $this->app['SsoClient'] = $this->app->share(function () use ($config_client) {
                return new SsoClient($config_client);
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
		return array('SsoClient');
	}

    /**
     * 兼容Laravel & Lumen的配置文件
     * @return array|mixed
     */
    public function config(){
        if(strpos(app()->version(), 'Lumen')===false){
            return config('sso.sso_client');
        }else{
            return [
                'sso_server_url'    => env('SSO_SERVER_URL'),
                'sso_app_id'        => env('SSO_APP_ID'),
                'sso_app_secret'    => env('SSO_APP_SECRET'),

            ];
        }
    }

}
