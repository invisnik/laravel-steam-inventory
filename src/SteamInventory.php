<?php namespace Invisnik\LaravelSteamInventory;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;

use InvalidArgumentException;

class SteamInventory
{

    /**
     * @var CacheManager $cache Caching layer
     */
    protected $cache;

    /**
     * @var Collection $collection The Collection instance
     */
    protected $collection;

    /**
     * @var integer $cacheTime Number of minutes to cache a Steam ID's inventory
     */
    protected $cacheTime;

    /**
     * @var string $cacheTag The Cache tag that will be used for all items
     */
    protected $cacheTag;

    /**
     * @var string $language
     */
    protected $language;

    /**
     * @var mixed The last inventory that was pulled
     */
    protected $currentData;

    /**
     * @var string The user steamid
     */
    protected $steamid;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * SteamInventory constructor.
     * @param CacheManager $manager
     */
    public function __construct(CacheManager $manager)
    {
        $this->cache = $manager->driver();
        $this->collection = new Collection();

        $this->cacheTag = Config::get('steam-inventory.cache_tag', 'steam.inventory');
        $this->cacheTime = Config::get('steam-inventory.cache_time', 60);
        $this->language = Config::get('steam-inventory.language', 'english');

        $this->guzzleClient = new GuzzleClient;
    }

    /**
     * Load the inventory for a Steam ID
     *
     * @param integer $steamId
     * @param int $appId
     * @param int $contextId
     * @return SteamInventory
     */
    public function loadInventory($steamId, $appId = 730, $contextId = 2): SteamInventory
    {
        $this->steamid = $steamId;

        if ($this->cache->tags($this->cacheTag)->has($steamId)) {
            $this->currentData = $this->cache->tags($this->cacheTag)->get($steamId);
            // Return the cached data
            return $this;
        }

        $inventory = $this->getSteamInventory($steamId, $appId, $contextId, $this->language);

        if (is_array($inventory)) {
            $minutes = $this->cacheTime;
            $this->cache->tags($this->cacheTag)->put($steamId, $inventory, $minutes);

            $this->currentData = $inventory;
        }

        return $this;
    }

    /**
     * Fetches the inventory of the Steam ID
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @param  string $lang
     * @return array
     */
    private function getSteamInventory($steamId, $appId, $contextId, $lang): array
    {
        $steamId = $this->cleanSteamId($steamId);
        $this->checkInfo($steamId, $appId, $contextId);

        $url = $this->steamApiUrl($steamId, $appId, $contextId, $lang);

        $response = $this->guzzleClient->get($url);
        $json = json_decode($response->getBody(), true);

        return $json;
    }

    /**
     * Returns a formatted Steam API Url
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @param  string $lang
     * @param  bool $tradable
     * @return string
     */
    private function steamApiUrl($steamId, $appId, $contextId, $lang = 'english', $tradable = true): string
    {
        return 'http://steamcommunity.com/inventory/' . $steamId . '/' . $appId . '/' . $contextId . '?l=' . $lang . '&tradable=' . (int)$tradable;
    }

    /*
    |--------------------------------------------------------------------------
    | Returning Data
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Returns the current Inventory data
     *
     * @return Collection
     */
    public function getInventory(): Collection
    {
        if (!$this->currentData) {
            return false;
        }

        $data = Arr::get($this->currentData, 'assets');

        return $this->collection->make($data);
    }

    /**
     * Returns the current Inventory Description data
     *
     * @return Collection
     */
    public function getDescriptions(): Collection
    {
        if (!$this->currentData) {
            return false;
        }

        $data = Arr::get($this->currentData, 'descriptions', false);
        $data = $this->collection->make($data);

        $items = $this->parseItemDescriptions($data);

        return $items;
    }

    /**
     * Returns the current Inventory data with descriptions
     * @param int $contextid
     * @return Collection
     */
    public function getInventoryWithDescriptions($contextid = 2): Collection
    {
        if (!Arr::get($this->currentData, 'success')) {
            return false;
        }

        $inventory = Arr::get($this->currentData, 'assets');
        $descriptions = Arr::get($this->currentData, 'descriptions');
        $data = array_map(function ($item) use ($descriptions, $contextid) {
            foreach ($descriptions as $desc) {
                if ($desc['classid'] == $item['classid'] && $desc['instanceid'] == $item['instanceid']) {
                    foreach ($desc as $key => $value) {
                        if (isset($desc[$key])) {
                            $item[$key] = $desc[$key];
                        }
                    }
                }
            }
            $tags = $this->parseItemTags(Arr::get($item, 'tags', []));
            $item['tags'] = $tags;
            $item['contextid'] = $contextid;
            return $item;
        }, $inventory);

        $items = $this->collection->make($data);

        return $items;

    }

    /**
     * Clears the user inventory cache
     * @param $steamid
     */
    public function clearCache($steamid): void
    {
        $this->cache->tags('steam.inventory')->forget($steamid);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    public function parseItemDescriptions(Collection $data): Collection
    {
        $items = $this->collection->make();

        if ($data->count() == 0) {
            return $items;
        }

        foreach ($data as $dataItem) {
            $name = trim(last(explode('|', Arr::get($dataItem, 'name'))));
            $desc = $this->parseItemDescription(Arr::get($dataItem, 'descriptions', []));
            $tags = $this->parseItemTags(Arr::get($dataItem, 'tags', []));
            $cat = Arr::get($tags, 'Category', '');

            $array = [
                'appid' => Arr::get($dataItem, 'appid'),
                'classid' => Arr::get($dataItem, 'classid'),
                'instanceid' => Arr::get($dataItem, 'instanceid'),
                'name' => $name,
                'market_name' => Arr::get($dataItem, 'market_name'),
                'weapon' => Arr::get($tags, 'Weapon'),
                'type' => Arr::get($tags, 'Type'),
                'quality' => Arr::get($tags, 'Quality'),
                'exterior' => Arr::get($tags, 'Exterior'),
                'collection' => Arr::get($tags, 'Collection'),
                'stattrack' => (stripos($cat, 'StatTrak') !== false) ? true : false,
                'icon_url' => Arr::get($dataItem, 'icon_url'),
                'icon_url_large' => Arr::get($dataItem, 'icon_url_large'),
                'description' => $desc,
                'name_color' => '#' . Arr::get($dataItem, 'name_color'),
            ];

            $items->push(json_decode(json_encode($array)));

            unset($desc, $tags, $cat, $array);
        }

        return $items;
    }

    /**
     * Parse an item's description about the item
     *
     * @param  StdClass $description
     * @return string
     */
    protected function parseItemDescription($description): string
    {
        $description = json_decode(json_encode($description), true);

        return trim(Arr::get($description, '2.value'));
    }

    /**
     * Parses an item's tags into a usable array
     *
     * @param  array $tags
     * @return array
     */
    protected function parseItemTags(array $tags): array
    {
        if (!count($tags)) {
            return [];
        }

        $parsed = [];

        foreach ($tags as $tag) {
            $categoryName = Arr::get($tag, 'category');
            $tagName = Arr::get($tag, 'localized_tag_name');

            $parsed[$categoryName] = $tagName;
        }

        return $parsed;
    }



    /*
    |--------------------------------------------------------------------------
    | Steam ID Cleanup & Check
    |--------------------------------------------------------------------------
    |
    |
    */

    /**
     * Checks if all the variables are numbers
     *
     * @param  integer $steamId
     * @param  integer $appId
     * @param  integer $contextId
     * @return bool
     */
    protected function checkInfo($steamId, $appId, $contextId): bool
    {
        if (!is_numeric($steamId) || !is_numeric($appId) || !is_numeric($contextId)) {
            throw new InvalidArgumentException('One or more variables are invalid: `steamId`, `appId`, `contextId`. They must be numeric!');

            return false;
        }

        return true;
    }

    /**
     * Prepares the Steam ID for usage against most copy/paste problems
     *
     * @param  string $steamId
     * @return string
     */
    protected function cleanSteamId($steamId): string
    {
        $steamId = trim($steamId);

        if (is_numeric($steamId)) {
            return $steamId;
        }

        return $this->steamIdTo64($steamId);
    }

    /**
     * Convert a Steam ID to 64 bit, if it isn't already
     *
     * @param  string $steamId
     * @return string
     */
    protected function steamIdTo64($steamId): string
    {
        if (strlen($steamId) === 17) {
            return $steamId;
        }

        $steamId = explode(':', $steamId);
        $steamId = bcadd((bcadd('76561197960265728', $steamId[1])), (bcmul($steamId[2], '2')));
        $steamId = str_replace('.0000000000', '', $steamId);

        return $steamId;
    }

}
