SSO Server
===

SSO server SSO authentication in Laravel 5.x & Lumen

### Installation
Require this package in your composer.json and run composer update.
    "Zhimei/sso": "dev-master"

####For Laravel 5:
After updating composer, add the ServiceProvider to the providers array in config/app.php
```php
    Zhimei\sso\SsoServerServiceProvider::class
```
As well as the Facade :
```php
    'SsoServer' => 'Zhimei\sso\Facades\SsoServer'
```
Then publish the package's config using one of those methods :
```
    $ php artisan config:publish Zhimei/sso
```
After publishing config, please config the items in config/sso.php
```php
    'sso_server' => [
                'model'         => env('SSO_MODEL'),
                'driver'        => env('SSO_SERVER_DRIVER', 'file'),  //file or memcached
                'clients'       => [
                    //'app_id'              => ['app_id'=>'app_id', 'app_secret'=>'app_secret', 'return_url'=>'return_url'],
                    'app_id_client_www'     => ['app_id'=>'app_id_client_www', 'app_secret'=>'app_secret_24A234FDG34S54GS', 'return_url'=>'http://www.zhimei360.com/'],
                    //...
                ],
    
            ],
```

####For Lumen :
After updating composer, register ServiceProvider in bootstrap/app.php
```php
    $app->register(Zhimei\sso\SsoServerServiceProvider::class);
```
configure in .env
```php
     SSO_MODEL=App\Models\SsoUser  ####It's abstract of Zhimei\sso\SsoServerModeAbstract
     SSO_SERVER_DRIVER=file
     SSO_CLIENT_APP_ID_1=app_id_1
     SSO_CLIENT_APP_SECRET_1=asdfsdfdf34rfdfE
     SSO_CLIENT_APP_RETURN_URL_1=http://sso/
    # SSO_CLIENT_APP_ID_2=
    # SSO_CLIENT_APP_SECRET_2=
    # SSO_CLIENT_APP_RETURN_URL_2=
    # SSO_CLIENT_APP_ID_3=
    # SSO_CLIENT_APP_SECRET_3=
    # SSO_CLIENT_APP_RETURN_URL_3=
```
App\Models\SsoUser
```php
<?php namespace App\Models;

use Zhimei\sso\SsoServerModeAbstract;

class SsoUser extends SsoServerModeAbstract  {

    public function authenticate($username, $password){
        return true;
    }

    public function getUserInfo($username){

    }

    public function login(){
        return time();
    }

}
```

###Usage
Add the code to your app/Http/routes.php
```php
    #for Lumen
    $app->get('/', function(){
        $method = app('request')->input('command');
        try{
            $return = app('SsoServer')->{$method}();
        }catch (\Exception $e){
            if($method=='attach'){
                throw new \Exception($e->getMessage());
            }else {
                return ['fail' => true, 'msg' => $e->getMessage()];
            }
        }
        return $return;
    });
    $app->post('login', ['as'=>'sso.login', function()use($app){
        return $app['SsoServer']->login();
    }]);
    
    #for Laravel
    Route::get('/', function(){
                      $method = app('request')->input('command');
                      try{
                          $return = app('SsoServer')->{$method}();
                      }catch (\Exception $e){
                          if($method=='attach'){
                              throw new \Exception($e->getMessage());
                          }else {
                              return ['fail' => true, 'msg' => $e->getMessage()];
                          }
                      }
                      return $return;
                  });
    Route::post('login', ['as'=>'sso.login', function()use(){
                       return \SsoServer::login();
                   }]);
```

SSO Client
===

