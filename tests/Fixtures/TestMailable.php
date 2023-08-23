<?php

namespace ManageItWA\LaravelMailtrapDriver\Tests\Fixtures;

use Illuminate\Mail\Mailable;

class TestMailable extends Mailable
{
    public function build()
    {
        return $this->from('test@manageit.com.au', 'Manage It Test')
            ->to('postmaster@manageit.com.au')
            ->view('test.email', [
                'name' => 'Ben',
            ])
            ->text('test.email_plain', [
                'name' => 'Ben',
            ])
            ->attach(__DIR__ . '/attachments/logo.png');
    }
}
