<?php

use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $assets = new LasseLehtinen\Assets\Assets();
    $this->assets = $assets;
});

test('can get get authentication token', function () {
    expect($this->assets->getAuthToken())->toBeString();
});

test('can search for assets', function () {
    $searchResults = $this->assets->search(query: 'mick haupt');
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(1);
});

test('can browse folders', function () {
    $browseResults = $this->assets->browse(path: '/');
    expect($browseResults)->toBeArray();
    expect(count($browseResults))->toBeGreaterThan(0);
});

test('can upload files', function () {
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/', metadata: ['gtin' => 1234567890123]);
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Check that metadata is updated as well
    expect($createResults)->toHaveProperty('metadata');
    expect($createResults->metadata)->toHaveProperty('gtin');
    expect($createResults->metadata->gtin)->toBe('1234567890123');
});

test('can bulkupdate files', function () {
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Bulk update the metadata
    $bulkUpdateResults = $this->assets->updatebulk(query: 'id:'.$createResults->id, metadata: ['gtin' => 1234567890123]);
    expect($bulkUpdateResults)->toBeObject();
    expect($bulkUpdateResults)->toHaveProperty('processedCount');
    expect($bulkUpdateResults)->toHaveProperty('errorCount');
    expect($bulkUpdateResults->processedCount)->toBe(1);
    expect($bulkUpdateResults->errorCount)->toBe(0);

    // Query for asset and check that metadata is updated
    $searchResults = $this->assets->search(query: 'id:'.$createResults->id);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(1);
    expect($searchResults->hits[0]->metadata)->toHaveProperty('gtin');
    expect($searchResults->hits[0]->metadata->gtin)->toBe('1234567890123');
});

test('can update metadata', function () {
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Update the metadata
    $updateResults = $this->assets->update(id: $createResults->id, metadata: ['gtin' => 1234567890123]);
    expect($updateResults)->toBeObject();
    expect($updateResults)->toHaveProperty('id');

    // Query for asset and check that metadata is updated
    $searchResults = $this->assets->search(query: 'id:'.$createResults->id);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(1);
    expect($searchResults->hits[0]->metadata)->toHaveProperty('gtin');
    expect($searchResults->hits[0]->metadata->gtin)->toBe('1234567890123');
});

test('can update asset contents', function () {
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    file_put_contents($temporaryFilename, 'foobar');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Update the file
    $updatedTemporaryFilename = tempnam('/tmp', 'ElvisTest');
    file_put_contents($updatedTemporaryFilename, 'foobaz');
    $updateResults = $this->assets->update(id: $createResults->id, filename: $updatedTemporaryFilename);
    expect($updateResults)->toBeObject();
    expect($updateResults)->toHaveProperty('id');

    // Download file and check contents
    $searchResults = $this->assets->search(query: 'id:'.$updateResults->id, appendRequestSecret: true);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(1);
    expect(file_get_contents($searchResults->hits[0]->originalUrl))->toBe('foobaz');
});

test('can remove files', function () {
    // Upload a test file
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Remove file
    $removeResults = $this->assets->remove(ids: [$createResults->id]);
    expect($removeResults)->toHaveProperty('processedCount');
    expect($removeResults)->toHaveProperty('errorCount');
    expect($removeResults->processedCount)->toBeInt(1);
    expect($removeResults->errorCount)->toBeInt(0);

    // Check that file is removed
    $searchResults = $this->assets->search(query: 'id:'.$createResults->id);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(0);
});

test('can remove files asynchronously', function () {
    // Upload a test file
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('id');
    expect($createResults->id)->toBeString();

    // Remove file
    $removeResults = $this->assets->remove(ids: [$createResults->id], async: true);
    expect($removeResults)->toHaveProperty('processId');
    expect($removeResults->processId)->toBeString();
});

test('can move/rename files', function () {
    // Upload a test file
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('metadata');
    expect($createResults->metadata)->toHaveProperty('assetPath');
    expect($createResults->metadata->assetPath)->toBeString();

    // Move file
    $moveResults = $this->assets->move(source: $createResults->metadata->assetPath, target: $createResults->metadata->assetPath.'-new');
    expect($moveResults)->toHaveProperty('processedCount');
    expect($moveResults)->toHaveProperty('errorCount');
    expect($moveResults->processedCount)->toBeInt(1);
    expect($moveResults->errorCount)->toBeInt(0);

    // Check that file is renamed
    $searchResults = $this->assets->search(query: 'id:'.$createResults->id);
    expect($searchResults)->toBeObject();
    expect($searchResults->hits)->toHaveCount(1);
    expect($searchResults->hits[0]->metadata)->toHaveProperty('assetPath');
    expect($searchResults->hits[0]->metadata->assetPath)->toBe($createResults->metadata->assetPath.'-new');
});

test('can create authorization keys', function () {
    // Upload a test file
    $temporaryFilename = tempnam('/tmp', 'ElvisTest');
    $createResults = $this->assets->create(filename: $temporaryFilename, folderPath: '/Users/elvis-package-testing/');
    expect($createResults)->toBeObject();
    expect($createResults)->toHaveProperty('metadata');
    expect($createResults->metadata)->toHaveProperty('assetPath');
    expect($createResults->metadata->assetPath)->toBeString();

    // Create expiry date that is 48 in the future
    $expiryDate = new DateTime;
    $expiryDate->add(new DateInterval('PT48H'));

    // authKey for spesific asset ids
    $createAuthKey = $this->assets->createAuthKey(subject: 'foobar', validUntil: $expiryDate->format('Y-m-d'), assetIds: [$createResults->id]);
    expect($createAuthKey)->toBeObject();
    expect($createAuthKey)->toHaveProperty('authKey');
    expect($createAuthKey)->toHaveProperty('webClientUrl');
    expect($createAuthKey)->toHaveProperty('desktopClientUrl');
    expect($createAuthKey)->toHaveProperty('mobileClientUrl');

    // General authKey to upload files
    $createAuthKey = $this->assets->createAuthKey(subject: 'foobar', validUntil: $expiryDate->format('Y-m-d'), requestUpload: true, importFolderPath: '/Users/elvis-package-testing');
    expect($createAuthKey)->toBeObject();
    expect($createAuthKey)->toHaveProperty('authKey');
    expect($createAuthKey)->toHaveProperty('webClientUrl');
    expect($createAuthKey)->toHaveProperty('desktopClientUrl');
    expect($createAuthKey)->toHaveProperty('mobileClientUrl');
});

test('throws exception when trying to login with incorrect password', function () {
    Config::set('woodwing-assets.username', 'foobar');
    Config::set('woodwing-assets.password', 'foobar');
    $assets = new LasseLehtinen\Assets\Assets();
})->throws(Exception::class);
