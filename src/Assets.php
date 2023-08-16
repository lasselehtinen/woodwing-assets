<?php

namespace LasseLehtinen\Assets;

use Exception;
use GuzzleHttp\Client;

class Assets
{
    /**
     * Guzzle HTTP client.
     *
     * @var Client
     */
    private $client;

    /**
     * Authentication token.
     *
     * @var string
     */
    private $authToken;

    public function __construct()
    {
        // Create new HTTP client
        $this->client = new Client([
            'base_uri' => config('woodwing-assets.endpoint').'/services/',
        ]);

        // Login to get authToken
        $this->authToken = $this->getAuthToken();
    }

    /**
     * Search.
     *
     * Wrapper for the search API, returns the hits found. You can find more information at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search.
     *
     * @param  string  $query                 Actual Lucene query, you can find more details in https://helpcenter.woodwing.com/hc/en-us/articles/360041854172-The-Assets-Server-query-syntax
     * @param  int  $start                 First hit to be returned. Starting at 0 for the first hit. Used to skip hits to return 'paged' results. Default is 0.
     * @param  int  $num                   Number of hits to return. Specify 0 to return no hits, this can be useful if you only want to fetch facets data. Default is 50.
     * @param  string  $sort                  The sort order of returned hits. Comma-delimited list of fields to sort on. Read more at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
     * @param  string  $metadataToReturn      Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://helpcenter.woodwing.com/hc/en-us/articles/360041851432-Assets-Server-REST-API-search
     * @param  bool  $appendRequestSecret   When set to true will append an encrypted code to the thumbnail, preview and original URLs.
     * @param  bool  $returnHighlightedText When set to true or when it is not passed, any found text is highlighted.
     * @param  bool  $returnThumbnailHits   Collections returned in the results have an additional array with up to 4 thumbnailHits. These are minimal sets of metadata for 4 of the assets contained by the Collections.
     * @return object List of search results
     */
    public function search(
        string $query,
        int $start = 0,
        int $num = 50,
        string $sort = 'assetCreated-desc',
        string $metadataToReturn = 'all',
        bool $appendRequestSecret = false,
        bool $returnHighlightedText = true,
        bool $returnThumbnailHits = false,
    ) {
        $response = $this->client->request('POST', 'search', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
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
     * Browse.
     *
     * This call is designed to allow you to browse folders and show their subfolders and collections, similar to how folder browsing works in the Elvis desktop client.
     *
     * @param  string  $path              The path to the folder in Elvis you want to list.
     * @param  string  $fromRoot          Allows returning multiple levels of folders with their children. When specified, this path is listed, and all folders below it up to the 'path' will have their children returned as well.
     * @param  bool  $includeFolders    Indicates if folders should be returned. Optional. Default is true.
     * @param  bool  $includeAsset      Indicates if files should be returned. Optional. Default is true, but filtered to only include 'container' assets.
     * @param  string  $includeExtensions A comma separated list of file extensions to be returned. Specify 'all' to return all file types.
     * @return object An array of folders and assets.
     */
    public function browse(
        string $path,
        string $fromRoot = null,
        ?bool $includeFolders = true,
        ?bool $includeAsset = true,
        ?string $includeExtensions = '.collection, .dossier, .task'
    ) {
        $response = $this->client->request('POST', 'browse', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'path' => $path,
                'fromRoot' => $fromRoot,
                'includeFolders' => $includeFolders,
                'includeAsset' => $includeAsset,
                'includeExtensions' => $includeExtensions,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Create.
     *
     * Upload and create an asset.
     *
     * @param  string  $filename         The file to be created in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
     * @param  string  $folderPath       Path of the folder where the file is uploaded
     * @param  array  $metadata         Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
     * @param  string  $metadataToReturn Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @return (object) Information about the newly created asset
     */
    public function create(
        string $filename,
        string $folderPath = null,
        array $metadata = null,
        ?string $metadataToReturn = 'all',
    ) {
        $response = $this->client->request('POST', 'create', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'folderPath' => $folderPath,
                'metadata' => (! empty($metadata)) ? json_encode($metadata) : null,
                'metadataToReturn' => $metadataToReturn,
            ],
            'multipart' => [
                [
                    'name' => 'Filedata',
                    'contents' => fopen($filename, 'r'),
                ],
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Update.
     *
     * Update an asset.
     *
     * @param  string  $id         The file to be updated in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
     * @param  string  $filename         The file to be updated in Elvis. If you do not specify a filename explicitly through the metadata, the filename of the uploaded file will be used.
     * @param  array  $metadata         Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
     * @param  string  $metadataToReturn Comma-delimited list of metadata fields to return in hits. It is good practice to always specify just the metadata fields that you need. This will make the searches faster because less data needs to be transferred over the network. Read more at https://elvis.tenderapp.com/kb/api/rest-search
     * @return (object) Information about the updated asset
     */
    public function update(
        string $id,
        string $filename = null,
        array $metadata = null,
        ?string $metadataToReturn = 'all',
    ) {
        // Form request
        $request = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'id' => $id,
                'metadata' => (! empty($metadata)) ? json_encode($metadata) : null,
                'metadataToReturn' => $metadataToReturn,
            ],
        ];

        if (! empty($filename)) {
            $request['multipart'] = [
                [
                    'name' => 'Filedata',
                    'contents' => fopen($filename, 'r'),
                ],
            ];
        }

        $response = $this->client->request('POST', 'update', $request);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Remove.
     *
     * Remove one or more assets. This will remove only assets, no folders.
     *
     * @param  string  $query      A query that matches all assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
     * @param  array  $ids        Array containing the assetId's for the assets to be removed. Be careful with this and make sure you test your query using a search call to prevent removing assets that you did not want to be removed.
     * @param  string  $folderPath The folderPath of the folder to remove. All assets and subfolders will be removed.
     * @param  bool  $async      When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
     * @return (object) Either processedCount or processId depending if async is true or false
     */
    public function remove(
        string $query = null,
        ?array $ids = [],
        string $folderPath = null,
        ?bool $async = false
    ) {
        if ($ids !== null && is_array($ids)) {
            $idsCommaSeparated = implode(',', $ids);
        } else {
            $idsCommaSeparated = null;
        }

        $response = $this->client->request('POST', 'remove', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'q' => $query,
                'ids' => $idsCommaSeparated,
                'folderPath' => $folderPath,
                'async' => $async ? 'true' : 'false',
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Updatebulk.
     *
     * This call updates the metadata of multiple existing assets in Elvis.
     *
     * @param  string  $query    A query matching the assets that should be updated
     * @param  array  $metadata Array containing the metadata for the asset as an array. Key is the metadata field name and value is the actual value.
     * @param  bool  $async    When true, the process will run asynchronous in the background. The call will return immediate with the processId. By default, the call waits for the process to finish and then returns the processedCount.
     * @return (object) Either processedCount or processId depending if async is true or false
     */
    public function updatebulk(
        string $query,
        array $metadata,
        ?bool $async = false
    ) {
        $response = $this->client->request('POST', 'updatebulk', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'q' => $query,
                'async' => $async ? 'true' : 'false',
                'metadata' => (! empty($metadata)) ? json_encode($metadata) : null,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Move / rename.
     *
     * Move or rename a folder or a single asset.
     *
     * @param  string  $source              Either a folderPath or assetPath of the folder or asset to be moved or renamed.
     * @param  string  $target              The folderPath or assetPath to which the folder or asset should be moved or renamed. If the parent folder is the same as in the source path, the asset will be renamed, otherwise it will be moved.)
     * @param  string  $folderReplacePolicy Policy used when destination folder already exists. Aither AUTO_RENAME (default), MERGE or THROW_EXCEPTION.
     * @param  string  $fileReplacePolicy   Policy used when destination asset already exists. Either AUTO_RENAME (default), OVERWRITE, OVERWRITE_IF_NEWER, REMOVE_SOURCE, THROW_EXCEPTION or DO_NOTHING
     * @param  string  $filterQuery         When specified, only source assets that match this query will be moved.
     * @param  bool  $flattenFolders      When set to true will move all files from source subfolders to directly below the target folder. This will 'flatten' any subfolder structure.
     * @return (object) Either processedCount or processId depending if async is true or false
     */
    public function move(
        string $source,
        string $target,
        ?string $folderReplacePolicy = 'AUTO_RENAME',
        ?string $fileReplacePolicy = 'AUTO_RENAME',
        ?string $filterQuery = '*:*',
        ?bool $flattenFolders = false
    ) {
        $response = $this->client->request('POST', 'move', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'source' => $source,
                'target' => $target,
                'folderReplacePolicy' => $folderReplacePolicy,
                'fileReplacePolicy' => $fileReplacePolicy,
                'flattenFolders' => $flattenFolders,
                'filterQuery' => $filterQuery,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Undocumented function
     *
     * @param  string  $subject                   AuthKey subject
     * @param  string  $validUntil                Expiry date, in one of the date formats supported by Assets Server
     * @param  array  $assetIds                   Array of asset id's to share, do not specify for a pure upload request (requestUpload must be true is this case)
     * @param  string|null  $description 	        AuthKey description that will be shown to receiver of the link.
     * @param  bool|null  $downloadOriginal 	Allow downloading original files. Setting this to true will automatically force downloadPreview to true as well.
     * @param  bool|null  $downloadPreview 	Allow viewing and downloading previews. Setting this to false will only show thumbnails and will also force downloadOriginal to false.
     * @param  bool|null  $requestApproval     Request for approval.
     * @param  bool|null  $requestUpload       Allow uploading new files, must be true when asset id's is not specified.
     * @param  string  $containerId               Container asset id which uploaded files are related to. Only relevant when requestUpload=true.
     * @param  array|null  $containerIds          Container asset id which uploaded files are related to. Only relevant when requestUpload=true.
     * @param  string  $importFolderPath     folderPath where files are uploaded. Required when requestUpload=true.
     * @param  string  $notifyEmail          Email address to send notifications to when upload or approval is finished. Only relevant when requestUpload=true or requestApproval=true.
     * @param  string|null  $sort                 Client setting, specify a comma-delimited list of fields to sort the results on. Follows the same behavior as sort in REST - search call, see also REST - search.
     * @param  array|null  $downloadPresetIds     Comma-delimited list of Download Preset IDs. Allows the downloading of renditions generated according to these Preset settings.
     * @param  bool|null  $watermarked         Shows watermarks on thumbnails and previews in Shared Links.
     * @return (object) Returns object with authKey, webClientUrl, desktopClientUrl and mobileClientUrl properties
     */
    public function createAuthKey(
        string $subject,
        string $validUntil,
        ?array $assetIds = [],
        string $description = null,
        ?bool $downloadOriginal = false,
        ?bool $downloadPreview = false,
        ?bool $requestApproval = false,
        ?bool $requestUpload = false,
        ?string $containerId = '',
        ?array $containerIds = [],
        ?string $importFolderPath = '',
        ?string $notifyEmail = '',
        ?string $sort = 'assetCreated-desc',
        ?array $downloadPresetIds = [],
        ?bool $watermarked = false,
    ) {
        $response = $this->client->request('POST', 'createAuthKey', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'subject' => $subject,
                'validUntil' => $validUntil,
                'assetIds' => implode(',', $assetIds),
                'description' => $description,
                'downloadOriginal' => $downloadOriginal,
                'downloadPreview' => $downloadPreview,
                'requestApproval' => $requestApproval,
                'requestUpload' => $requestUpload,
                'containerId' => $containerId,
                'containerIds' => implode(',', $containerIds),
                'importFolderPath' => $importFolderPath,
                'notifyEmail' => $notifyEmail,
                'sort' => $sort,
                'downloadPresetIds' => implode(',', $downloadPresetIds),
                'watermarked' => $watermarked,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Create relation.
     *
     * @param  string  $relationType      The type of relation to create. See https://helpcenter.woodwing.com/hc/en-us/articles/360041852112
     * @param  string  $target1Id         The id of the asset on one side of the relation. If the relation type is a directional relation, this must be the id of the 'parent'-side.
     * @param  string  $target2Id         The id of the asset on the other side of the relation. If the relation type is a directional relation, this must be the id of the 'child'-side.
     * @return object
     */
    public function createRelation(
        string $relationType,
        string $target1Id,
        string $target2Id,
    ) {
        $response = $this->client->request('POST', 'createRelation', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'relationType' => $relationType,
                'target1Id' => $target1Id,
                'target2Id' => $target2Id,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Remove relation.
     *
     * @param  array  $relationIds    A comma-delimited list of relation ids to be removed. To find the relation ids, use a relation search.
     * @return object
     */
    public function removeRelation(
        array $relationIds,
    ) {
        $response = $this->client->request('POST', 'removeRelation', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
            'query' => [
                'relationIds' => implode(',', $relationIds),
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Checkout.
     *
     * @param  string  $assetId      ID of the asset to be checked out
     * @return object
     */
    public function checkout(
        string $assetId,
    ) {
        $response = $this->client->request('POST', 'checkout/'.$assetId, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return $body;
    }

    /**
     * Undo checkout.
     *
     * @param  string  $assetId      ID of the asset on which the checkout is undone
     * @return bool
     */
    public function undoCheckout(
        string $assetId,
    ) {
        $response = $this->client->request('POST', 'undocheckout/'.$assetId, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        return is_object($body);
    }

    /**
     * Logout
     *
     * @return bool
     */
    public function logout()
    {
        $response = $this->client->request('POST', 'logout', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->authToken,
            ],
        ]);

        $body = json_decode($response->getBody()->getContents());

        unset($this->authToken);

        return $body->logoutSuccess;
    }

    /**
     * Get the authToken.
     *
     * @return string
     */
    public function getAuthToken()
    {
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
            throw new Exception('Could not login: '.$body->loginFaultMessage);
        }

        return $body->authToken;
    }
}
