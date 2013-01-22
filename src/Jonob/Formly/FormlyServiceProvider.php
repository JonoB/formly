<?php namespace Jonob\Formly;

use Illuminate\Support\ServiceProvider;

class FormlyServiceProvider extends ServiceProvider {

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
        $this->package('jonob/formly');
    }

    /**
     * Register the {{full_package}} service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['formly'] = $this->app->share(function($app)
        {
            return new Formly;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('formly');
    }

}