<?php namespace Jonob\Formly;

use Illuminate\Support\Facades\Facade;

class FormlyFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'formly'; }

}