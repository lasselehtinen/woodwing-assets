<?php

use Illuminate\Support\Facades\Config;

beforeEach(function () {
    // Set credentials
    Config::set('woodwing-assets.endpoint', 'https://elvis.bonnierbooks.fi/services/');
    Config::set('woodwing-assets.username', 'guest');
    Config::set('woodwing-assets.password', 'guest');

    $assets = new LasseLehtinen\Assets\Assets();
    $this->assets = $assets;
});

test('can get get authentication token', function () {
    expect($this->assets->getAuthToken())->toBeString();
});

test('can search for assets', function () {
    $searchResults = $this->assets->search(query: 'Jari Tervo', num: 2);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(2);
});

test('throws exception when trying to login with incorrect password', function () {
    Config::set('woodwing-assets.username', 'foobar');
    Config::set('woodwing-assets.password', 'foobar');
    $assets = new LasseLehtinen\Assets\Assets();
})->throws(Exception::class);
