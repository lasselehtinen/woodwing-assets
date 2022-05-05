<?php

namespace LasseLehtinen\Assets;

use Exception;
use GuzzleHttp\Client;

class Assets
{
    /**
     * Guzzle HTTP client
     * @var Client
     */
    private $client;

    /**
     * Authentication token
     * @var string
     */
    private $authToken;

    public function __construct() {
        // Create new HTTP client
        $this->client = new Client([
            'base_uri' => config('woodwing-assets.endpoint')
        ]);

        // Login to get authToken
        $this->authToken = $this->getAuthToken();
    }

    /**
     * Search
     *
     * Wrapper for the search API, returns the hits found. You can find more information at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search.
     *
     * @param string $query Actual Lucene query, you can find more details in https://helpcenter.woodwing.com/hc/en-us/articles/360041854172-The-Assets-Server-query-syntax
     * @param int $start First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
     * @param int $num Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
     * @param string $sort The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
     * @param string $metadataToReturn Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
     * @param bool $appendRequestSecret When set to true will append an encrypted code to the thumbnail, preview and original URLs.
     * @param bool $returnHighlightedText When set to true or when it is not passed, any found text is highlighted.
     * @param bool $returnThumbnailHits Collections returned in the results have an additional array with up to 4 thumbnailHits. These are minimal sets of metadata for 4 of the assets contained by the Collections.
     * @return object List of search results
     */
    public function search (
        string $query,
        int $start = 0,
        int $num = 50,
        string $sort = 'assetCreated-desc',
        string $metadataToReturn = 'all',
        bool $appendRequestSecret = false,
        bool $returnHighlightedText = true,
        bool $returnThumbnailHits = false,
    )
    {
        $response = $this->client->request('POST', 'search', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->authToken,
            ],
            'query' => [
                'q' => $query,
                'start' => $start,
                'num' => $num,
                'sort' => $sort,
                'metadataToReturn' => $metadataToReturn,
                'appendRequestSecret' => $appendRequestSecret,
                'returnHighlightedText' => $returnHighlightedText,
                'returnThumbnailHits' => $returnThumbnailHits,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Get the authToken
     * @return string
     */
    public function getAuthToken() {
        $response = $this->client->request('POST', 'apilogin', [
            'query' => [
                'username' => config('woodwing-assets.username'),
                'password' => config('woodwing-assets.password'),
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Did not receive JSON response');
        }

        if (property_exists($body, 'loginSuccess') === false) {
            throw new Exception('Unknown response for login attempt.');
        }

        if ($body->loginSuccess === false) {
            throw new Exception('Could not login: ' . $body->loginFaultMessage);
        }

        return $body->authToken;
    }
}
