<?php

class SteamInventoryTest extends Orchestra\Testbench\TestCase
{
    /**
     * @var string
     */
    protected $steamId = '76561198233097000';

    /**
     * @var int
     */
    protected $appid = 730;

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return ['Invisnik\LaravelSteamInventory\ServiceProvider'];
    }

    public function testGettingItems()
    {
        $items = $this->app['steam-inventory']
            ->loadInventory($this->steamId, $this->appid)->getInventory();

        $this->assertTrue($items->count() > 0);
    }

    public function testGettingDescriptions()
    {
        $items = $this->app['steam-inventory']
            ->loadInventory($this->steamId, $this->appid)->getDescriptions();

        $this->assertTrue($items->count() > 0);
    }

    public function testGettingItemsWithDescriptions()
    {
        $items = $this->app['steam-inventory']
            ->loadInventory($this->steamId, $this->appid)->getInventoryWithDescriptions();

        $this->assertTrue($items->count() > 0);
    }
}