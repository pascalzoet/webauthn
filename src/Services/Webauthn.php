<?php

namespace Inzicht\Webauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;
use Inzicht\Webauthn\Models\WebauthnKey;
use Inzicht\Webauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;
use Inzicht\Webauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsFactory;
use Inzicht\Webauthn\Services\Webauthn\PublicKeyCredentialValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class Webauthn extends WebauthnRepository
{
    /**
     * Laravel application.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Configuratoin repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Session manager.
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * Event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new instance of Webauthn.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Application $app, Config $config, Session $session, Dispatcher $events)
    {
        $this->app = $app;
        $this->config = $config;
        $this->session = $session;
        $this->events = $events;
    }

    /**
     * Get datas to register a new key.
     *
     * @param  User  $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function getRegisterData(User $user): PublicKeyCredentialCreationOptions
    {
        return $this->app->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->create($user);
    }

    /**
     * Register a new key.
     *
     * @param  User  $user
     * @param  PublicKeyCredentialCreationOptions  $publicKey
     * @param  string  $data
     * @param  string  $keyName
     * @return WebauthnKey
     */
    public function doRegister(User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName): WebauthnKey
    {
        $publicKeyCredentialSource = $this->app->make(PublicKeyCredentialValidator::class)
            ->validate($publicKey, $data);

        $webauthnKey = $this->create($user, $keyName, $publicKeyCredentialSource);

        $this->forceAuthenticate();

        return $webauthnKey;
    }

    /**
     * Get datas to authenticate a user.
     *
     * @param  User  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function getAuthenticateData(User $user): PublicKeyCredentialRequestOptions
    {
        return $this->app->make(PublicKeyCredentialRequestOptionsFactory::class)
            ->create($user);
    }

    /**
     * Authenticate a user.
     *
     * @param  User  $user
     * @param  PublicKeyCredentialRequestOptions  $publicKey
     * @param  string  $data
     * @return bool
     */
    public function doAuthenticate(User $user, PublicKeyCredentialRequestOptions $publicKey, string $data): bool
    {
        $result = $this->app->make(PublicKeyCredentialValidator::class)
            ->check($user, $publicKey, $data);

        if ($result) {
            $this->forceAuthenticate();

            return true;
        }

        return false;
    }

    /**
     * Force authentication in session.
     *
     * @return void
     */
    public function forceAuthenticate()
    {
        $this->session->put([$this->config->get('webauthn.sessionName') => true]);
    }

    /**
     * Force session logout
     */
    public function forceLogout()
    {
        $this->session->forget($this->config->get('webauthn.sessionName'));
    }

    /**
     * Check authentication of the user in session.
     *
     * @return bool
     */
    public function check(): bool
    {
        return (bool) $this->session->get($this->config->get('webauthn.sessionName'), false);
    }

    /**
     * Test if the user has one webauthn key set or more.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function enabled(User $user): bool
    {
        return (bool) $this->config->get('webauthn.enable', true) && $this->hasKey($user);
    }

    /**
     * Test if the user can register a new token.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function canRegister(User $user): bool
    {
        return ! $this->enabled($user) || $this->check();
    }
}