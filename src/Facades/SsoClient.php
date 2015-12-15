<?php namespace Zhimei\sso\Facades;

use Illuminate\Support\Facades\Facade;

class SsoClient extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'SsoClient'; }

}