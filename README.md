# Steam Inventory parser for Laravel 10
[![Code Climate](https://codeclimate.com/github/invisnik/laravel-steam-inventory/badges/gpa.svg)](https://codeclimate.com/github/invisnik/laravel-steam-inventory)
[![Latest Stable Version](https://poser.pugx.org/invisnik/laravel-steam-inventory/v/stable?format=flat)](https://packagist.org/packages/invisnik/laravel-steam-inventory)
[![Total Downloads](https://poser.pugx.org/invisnik/laravel-steam-inventory/downloads?format=flat)](https://packagist.org/packages/invisnik/laravel-steam-inventory)
[![License](https://poser.pugx.org/invisnik/laravel-steam-inventory/license?format=flat)](https://packagist.org/packages/invisnik/laravel-steam-inventory)
## Dependencies
 - Laravel cache driver, which supports tags. For example `Redis` or `Memcached`
 
## Installation Via Composer
Add this to your `composer.json` file:

```javascript
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/xpiotreee/laravel-steam-inventory"
    },
]
```

And this to your `composer.json` file, in the require object:
```javascript
"invisnik/laravel-steam-inventory": "dev-l10-compatibility"
```

After that, run `composer install` to install the package.

#### Config Files

Lastly, publish the config file and configure it.

```
php artisan vendor:publish
```

## Usage example

```php
namespace App\Http\Controllers;

use Invisnik\LaravelSteamInventory\SteamInventory;

class YourController extends Controller
{
    /**
     * @var SteamInventory
     */
    private $steamInventory;

    public function __construct(SteamInventory $steamInventory)
    {
        $this->steamInventory = $steamInventory;
    }

    public function index()
    {
    	$user = App\User::find(1);
	// $user->steamid = '76561198233097000'
    	$items = $this->steamInventory
		->loadInventory($user->steamid, 730)->getInventoryWithDescriptions();
		
	return view('your.view.file', compact('items'));
    }
}
```
