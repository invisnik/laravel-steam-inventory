# Steam Inventory parser for Laravel 5
[![Latest Stable Version](https://poser.pugx.org/invisnik/laravel-steam-inventory/v/stable?format=flat-square)](https://packagist.org/packages/invisnik/laravel-steam-inventory)
[![Total Downloads](https://poser.pugx.org/invisnik/laravel-steam-inventory/downloads?format=flat-square)](https://packagist.org/packages/invisnik/laravel-steam-inventory)
[![License](https://poser.pugx.org/invisnik/laravel-steam-inventory/license?format=flat-square)](https://packagist.org/packages/invisnik/laravel-steam-inventory)

## Installation Via Composer
Add this to your `composer.json` file, in the require object:

```javascript
"invisnik/laravel-steam-inventory": "dev-master"
```

After that, run `composer install` to install the package.

Add the service provider to `app/config/app.php`, within the `providers` array.

```php
'providers' => [
	// ...
	Invisnik\LaravelSteamInventory\ServiceProvider::class,
]
```

Lastly, publish the config file and configure it.

```
php artisan vendor:publish
```

## Usage example

Soon...
