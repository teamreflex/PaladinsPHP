# PaladinsPHP

A simple object-oriented approach to data in the Paladins API (a game from Hi-Rez Studios).

For more information about the Paladins API, refer to the [official API documentation](https://docs.google.com/document/d/1OFS-3ocSx-1Rvg4afAnEHlT3917MAK_6eJTR6rzr-BM/).

## Requirements

* PHP 5.4 or greater
* DevID and AuthKey from Hi-Rez (submit [this form](https://fs12.formsite.com/HiRez/form48/secure_index.html))

## Installation

```
$ composer require team-reflex/paladins-api
```

## Usage

```php
// Create api
$api = new Reflex\Paladins\API(DEV_ID, AUTH_KEY);

// optional session caching via many providers
// see https://github.com/onoi/cache/
$api->useCache(new \Onoi\Cache\ZendCache($zendCacheThing));
$api->useCache(new \Onoi\Cache\DoctrineCache($doctrineCacheThing));
$api->useCache(new \Onoi\Cache\MediaWikiCache(wfGetCache(CACHE_ANYTHING)));

// returns objects by default
$api->preferredFormat('array');
// will now return assoc arrays

// returns english language gods and item names by default
$api->preferredLanguage('es');
// use IETF language tag to select language
// (latin america becomes es-419)

// get player info
$playerData = $api->getplayer($playerName);

// get info on silver 3 ladder in season 4
$ladderData = $api->getleagueleaderboard('Conquest5v5', 'Silver3', 4);
```

## Laravel Cache

We have an Illuminate driver for onoi/cache found here: https://github.com/teamreflex/Onoi-Illuminate

## Contributing

1. Fork
2. `git clone`
3. `composer install`
4. Hack
5. Confirm (and write new) passing tests: `vendor/bin/phpunit`
6. Submit pull request

## License

Copyright 2015 Curse, Inc.

Free for you to use under LGPLv3. See [LICENSE](LICENSE) for an abundance of words.
