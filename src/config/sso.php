<?php

return [

        /**
         * for sso client
        */
        'sso_client' => [
            'sso_server_url'    => env('SSO_SERVER_URL', 'localhost'),
            'sso_app_id'        => env('SSO_APP_ID'),
            'sso_app_secret'    => env('SSO_APP_SECRET'),

        ],

        /**
         * for sso server
         */
        'sso_server' => [
            'model'         => env('SSO_MODEL','App\\Models\SsoUser'),
            'driver'        => env('SSO_SERVER_DRIVER', 'file'),  //file or memcached or database
            'clients'       => [
                //'app_id'              => ['app_id'=>'app_id', 'app_secret'=>'app_secret', 'return_url'=>'return_url'],
                'app_id_client_www'     => ['app_id'=>'app_id_client_www', 'app_secret'=>'app_secret_24A234FDG34S54GS', 'return_url'=>'http://www.zhimei360.com/'],
                //...
            ],

        ],

];
