<?php namespace Zhimei\sso\Facades;

use Illuminate\Support\Facades\Facade;

class SsoServer extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'SsoServer'; }

}