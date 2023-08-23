<?php

namespace ManageItWA\LaravelMailtrapDriver\Tests;

use ManageItWA\LaravelMailtrapDriver\MailtrapServiceProvider;
use Orchestra\Testbench\TestCase;

/**
 * @covers \ManageItWA\LaravelMailtrapDriver\MailtrapServiceProvider
 * @covers \ManageItWa\LaravelMailtrapDriver\MailtrapTransport
 */
class MailtrapTransportTest extends TestCase
{
    public function testCanGetTransport()
    {
        $transport = $this->app['swift.transport']->driver('mailtrap');

        $this->assertInstanceOf(
            \ManageItWA\LaravelMailtrapDriver\MailtrapTransport::class,
            $transport
        );
    }

    public function testCanSendHtmlEmail()
    {
        // Mock client
        $client = \Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $driver = $this->app['swift.transport']->driver('mailtrap');
        $driver->setClient($client);

        $args = [];
        $client
            ->shouldReceive('request')
            ->once()
            ->andReturnUsing(function (...$params) use (&$args) {
                $args = $params;
                return \Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
            });

        /** @var \Illuminate\Mail\Mailer */
        $mailer = $this->app['mailer'];

        // Send test email
        $mailer->html('<h1>Hello World</h1>', function ($mail) {
            $mail
                ->to('ben@manageit.com.au')
                ->subject('Test email');
        });

        // Check arguments passed to Guzzle
        $this->assertEquals('POST', $args[0]);
        $this->assertEquals('https://send.api.mailtrap.io/api/send', $args[1]);

        // Check payload
        $payload = $args[2];
        $this->assertEquals('foo', $payload['headers']['Api-Token']);
        $this->assertEquals([
            'email' => 'example@manageit.com.au',
            'name' => 'Manage It',
        ], $payload['json']['from']);
        $this->assertEquals([
            'email' => 'ben@manageit.com.au',
        ], $payload['json']['to'][0]);
        $this->assertEquals('Test email', $payload['json']['subject']);
        $this->assertEquals('<h1>Hello World</h1>', $payload['json']['html']);
    }

    public function testCanSendMailable()
    {
        // Mock client
        $client = \Mockery::mock(\GuzzleHttp\ClientInterface::class);
        $driver = $this->app['swift.transport']->driver('mailtrap');
        $driver->setClient($client);

        $args = [];
        $client
            ->shouldReceive('request')
            ->once()
            ->andReturnUsing(function (...$params) use (&$args) {
                $args = $params;
                return \Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
            });

        /** @var \Illuminate\Mail\Mailer */
        $mailer = $this->app['mailer'];

        // Send test email
        $mailer->send(new \ManageItWA\LaravelMailtrapDriver\Tests\Fixtures\TestMailable);

        // Check arguments passed to Guzzle
        $this->assertEquals('POST', $args[0]);
        $this->assertEquals('https://send.api.mailtrap.io/api/send', $args[1]);

        // Check payload
        $payload = $args[2];
        $this->assertEquals('foo', $payload['headers']['Api-Token']);
        $this->assertEquals([
            'email' => 'test@manageit.com.au',
            'name' => 'Manage It Test',
        ], $payload['json']['from']);
        $this->assertEquals([
            'email' => 'postmaster@manageit.com.au',
        ], $payload['json']['to'][0]);
        $this->assertEquals('Test Mailable', $payload['json']['subject']);
        $this->assertEquals("<h1>Hi, Ben</h1>\n<p>This is an email.</p>\n", $payload['json']['html']);
        $this->assertEquals("Hi, Ben\nThis is an email.\n", $payload['json']['text']);
        $this->assertCount(1, $payload['json']['attachments']);
        $this->assertEquals('logo.png', $payload['json']['attachments'][0]['filename']);
        $this->assertEquals('image/png', $payload['json']['attachments'][0]['type']);
        $this->assertEquals('attachment', $payload['json']['attachments'][0]['disposition']);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('mail.driver', 'mailtrap');
        $app['config']->set('mail.from', [
            'address' => 'example@manageit.com.au',
            'name' => 'Manage It',
        ]);
        $app['config']->set('services.mailtrap', [
            'token' => 'foo',
        ]);
        $app['config']->set('view.paths', [
            __DIR__ . '/Fixtures/views',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            MailtrapServiceProvider::class,
        ];
    }
}
