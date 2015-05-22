Usage
-----

```php
// Create api
$api = new Smite\API(DEV_ID, AUTH_KEY);

// returns object by default
$api->preferFormat('array');
// will now return assoc arrays

$api->useLanguage('en');

// get player info
$api->request('/getplayer', $playerName);

// get God info (language code)

```

