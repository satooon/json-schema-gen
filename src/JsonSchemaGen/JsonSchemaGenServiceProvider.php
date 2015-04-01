<?php namespace Satooon\JsonSchemaGen;

use Illuminate\Support\ServiceProvider;

class JsonSchemaGenServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('satooon/json-schema-gen');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('jsonSchemaGen',function(){
			return new JsonSchemaGen;
		});

		$this->app['JsonSchemaGen'] = $this->app->share(function($app)
		{
			return new JsonSchemaGenCommand;
		});
		$this->commands('JsonSchemaGen');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
