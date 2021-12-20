<?php

namespace Inzicht\Webauthn\Facade;

use Illuminate\Support\Facades\Facade;

class Webauthn extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Inzicht\Webauthn\Services\Webauthn::class;
    }
}