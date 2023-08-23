<?php

namespace ManageItWA\LaravelMailtrapDriver;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Arr;

class MailtrapServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * Extends the available transports and adds the `mailtrap` transport.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['swift.transport']->extend('mailtrap', function () {
            $config = $this->app['config']->get('services.mailtrap', []);

            return new MailtrapTransport(
                $this->guzzle($config),
                $config['token'],
            );
        });
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     *
     * @param  array  $config
     * @return \GuzzleHttp\Client
     */
    protected function guzzle($config)
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [],
            'connect_timeout',
            60,
        ));
    }
}
