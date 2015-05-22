Usage
-----

```php
// Create api
$api = new Smite\API(DEV_ID, AUTH_KEY);

// returns object by default
$api->preferFormat('array');
// will now return assoc arrays

// using IETF language tags
$api->useLanguage('en');

// get player info
$api->request('/getplayer', $playerName);

// get info on silver 3 ladder in season 4
$api->request('/getleagueleaderboard', 'Conquest5v5', 'Silver3', 4);
```

