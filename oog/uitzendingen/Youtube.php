<?php

namespace oog\uitzendingen;

use oog\uitzendingen\providers\AbstractGoogleClientProvider;

class Youtube
{

    private $provider;

    function __construct(AbstractGoogleClientProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Fetches the available Youtube Categories
     * @return array
     */
    public function getCategories()
    {
        $client = $this->provider->getGoogleClient();
        $youtube = new \Google_Service_YouTube($client);

        $cleaned = [];

        try {
            $videoCategories = $youtube->videoCategories->listVideoCategories(
                'snippet',
                [
                    'hl' => 'nl_NL',
                    'regionCode' => 'NL'
                ]);

            foreach ($videoCategories['items'] as $category) {
                if ($category['snippet']['assignable']) {
                    $cleaned[] = [
                        'id' => $category['id'],
                        'title' => $category['snippet']['title']
                    ];

                }
            }
        } catch (\Exception $e) {
            error_log('Cannot get categories');
        }

        return $cleaned;
    }

    public function updateVideo($id, $meta)
    {

        try {
            $client = $this->provider->getGoogleClient();
            $youtube = new \Google_Service_YouTube($client);

            $listResponse = $youtube->videos->listVideos('snippet,status', ['id' => $id]);
            if (empty($listResponse)) {
                // video not found
            } else {
                $video = $listResponse[0];
                $snippet = $video['snippet'];
                $status = $video['status'];
                $status['privacyStatus'] = 'public';
                $snippet['categoryId'] = $meta['youtube_category'];
                $snippet['description'] = html_entity_decode($meta['description'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $snippet['title'] = html_entity_decode($meta['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $snippet['tags'] = $meta['tags'];

                $youtube->videos->update('snippet,status', $video);
            }
        } catch (\Google_Service_Exception $e) {
            error_log($e->getMessage());
            if (function_exists('add_filter')) {
                add_filter('redirect_post_location', [$this, 'add_notice_query_var'], 99);
            }
        }
    }

    public function add_notice_query_var($location)
    {
        if (function_exists('remove_filter') && function_exists('add_query_arg')) {
            remove_filter('redirect_post_location', [$this, 'add_notice_query_var'], 99);
            return add_query_arg(['uitzendingNotice' => Uitzending::NOTICE_YOUTUBE_FAIL], $location);
        }
    }

    /**
     * Returns a list of videos
     * @param bool $private
     * @return array
     */
    public function getVideos($private = false)
    {
        $client = $this->provider->getGoogleClient();
        $youtube = new \Google_Service_YouTube($client);
        $result = [];
        setlocale(LC_TIME, ['nl_NL', 'nl_NL.utf8']);

        try {
            $filter = [];
            $part = ['snippet'];

            if ($private) {
                $filter['mine'] = 'true';
                $part[] = 'status';
            }

            $channelsResponse = $youtube->channels->listChannels('contentDetails', $filter);

            foreach ($channelsResponse['items'] as $channel) {
                // Extract the unique playlist ID that identifies the list of videos
                // uploaded to the channel, and then call the playlistItems.list method
                // to retrieve that list.
                $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

                $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems(join(',', $part), [
                    'playlistId' => $uploadsListId,
                    'maxResults' => 50
                ]);

                $result = array_merge($result, $playlistItemsResponse['items']);

                /*foreach ($playlistItemsResponse['items'] as $playlistItem) {
                    if (!$private || $playlistItem['status']['privacyStatus'] === 'private') {
                        $result[$playlistItem['snippet']['resourceId']['videoId']] = sprintf(
                            '%s (%s)',
                            $playlistItem['snippet']['title'],
                            $playlistItem['snippet']['resourceId']['videoId']);
                    }
                }*/
            }

        } catch (\Google_Service_Exception $e) {
            error_log(sprintf('A service error occurred: %s', htmlspecialchars($e->getMessage())));

        } catch (\Google_Exception $e) {
            error_log(sprintf('An client error occurred: %s', htmlspecialchars($e->getMessage())));
        }

        return $result;
    }

//    public function getVideo($id)
//    {
//        $client = $this->provider->getGoogleClient();
//        $youtube = new \Google_Service_YouTube($client);
//        setlocale(LC_TIME, ['nl_NL', 'nl_NL.utf8']);
//        $youtube->videos->call()
//
//    }
}