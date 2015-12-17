SSO Server
===

SSO server SSO authentication in Laravel 5.x & Lumen

### Installation
Require this package in your composer.json and run composer update.
```
    "Zhimei/sso": "dev-master"
```
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
After publishing config, please config the items in your config/sso.php
```php
    'sso_server' => [
                'model'         => env('SSO_MODEL'),  //It's abstract of Zhimei\sso\SsoServerModeAbstract
                'driver'        => env('SSO_SERVER_DRIVER', 'file'),  //file or memcached (recommend memcached)
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
####For Both:
App\Models\SsoUser
```php
<?php namespace App\Models;

use Zhimei\sso\SsoServerModeAbstract;

class SsoUser extends SsoServerModeAbstract  {

    /**
         * @param $username
         * @param $password
         * @return bool
         */
        public function authenticate($username, $password){
            $user = User::where('username', $username)->first();
            if(empty($user)){
                return false;
            }
            if(!password_verify($password, $user->password)){
                return false;
            }
            return true;
        }
    
        /**
         * @param $username
         * @return null
         */
        public function getUserByUsername($username){
            $user = User::where('username', $username)->first();
            if(empty($user)){
                return null;
            }
            return $user->toArray();
        }
    
        /**
         * @param $user_id
         */
        public function getUserById($user_id){
    
        }

}
```

###Usage
Add the follow code to your app/Http/routes.php
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
                      $method = \Request::input('command');
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
### Installation
Require this package in your composer.json and run composer update.
```
    "Zhimei/sso": "dev-master"
```
    
After updating composer, add the ServiceProvider to the providers array in config/app.php
```php
    Zhimei\sso\SsoClientServiceProvider::class
```
As well as the Facade :
```php
    'SsoClient' => 'Zhimei\sso\Facades\SsoClient'
```
Then publish the package's config using one of those methods :
```
    $ php artisan config:publish Zhimei/sso
```
###Configuration
Add to .env
```
    SSO_SERVER_URL  =
    SSO_APP_ID      =
    SSO_APP_SECRET  =
```
###Available Functions
Get URL to attach session at SSO server.
```php
    app('SsoClient')->getAttachUrl();
```
Attach our session to the user's session on the SSO server.
```php
    /**
     * Attach our session to the user's session on the SSO server.
     * @param null $state
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    return app('SsoClient')->attach();
```
Get user information.
```php
    app('SsoClient')->getUserInfo();
```
Logout at sso server
```php
    app('SsoClient')->logout();
```

###Usage examples
In your routes.php use the following code:
```php
#for Lumen
$app->get('login', function()use($app){
    if($app['SsoClient']::isAttached()){
        $userInfo = app('SsoClient')->getUserInfo();
        if(empty($userInfo)){
            return app('SsoClient')->reAttach();
        }
        return $userInfo;
    }else{
        return $app['SsoClient']::attach();
    }
});
$app->get('logout', function()use($app){
    $app['SsoClient']->logout();
});

#for Laravel
Route::get('login', function(){
    if(\SsoClient::isAttached()){
        $userInfo = app('SsoClient')->getUserInfo();
        if(empty($userInfo)){
            return app('SsoClient')->reAttach();
        }
        return $userInfo;
    }else{
        return \SsoClient::attach();
    }
});
Route::get('logout', function(){
    \SsoClient::logout();
});
```