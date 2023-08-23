# Mailtrap Driver for Laravel

This allows a Laravel v6.x install to use the [Mailtrap.io](https://mailtrap.io) sending service API for sending emails, since the official client does not support any version below Laravel 9.

## Usage

Include in Composer:

```bash
composer require manageitwa/laravel-mailtrap-driver
```

The package should be discovered if you have Laravel package auto-discovery enabled. Otherwise, include the service provider in your `app.providers` config:

```php
'providers' => [
    // ...
    ManageItWA\LaravelMailtrapDriver\MailtrapServiceProvider::class,
    //...
],
```

Finally, ensure that you add the following to `config/services.php`:

```php
'mailtrap' => [
    'token' => '', // Set to your API token from Mailtrap
],
```

You may then set your `mail.driver` to `mailtrap` and you're away!
