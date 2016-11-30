<?php


namespace oog\uitzendingen;


use oog\uitzendingen\admin\Admin;

class Youtube
{

    /**
     * Fetches the available Youtube Categories
     * @return array
     */
    public function getCategories() {
        $client = Admin::GetGoogleClient();
        $youtube = new \Google_Service_YouTube($client);

        $cleaned = [];

        try {
            $videoCategories = $youtube->videoCategories->listVideoCategories(
                'snippet',
                [
                    'hl' => 'nl_NL',
                    'regionCode' => 'NL'
                ]);

            foreach($videoCategories['items'] as $category) {
                if($category['snippet']['assignable']) {
                    $cleaned[] = [
                        'id' => $category['id'],
                        'title' => $category['snippet']['title']
                    ];

                }
            }
        } catch(\Exception $e) {
            error_log('Cannot get categories');
        }

        return $cleaned;
    }

    public function updateVideo($id, $meta)
    {
        $ytMeta = [
            'id' => $id,
            'kind' => 'youtube#video',
            'snippet' => [
                'title' => $meta['title'],
                'tags' => $meta['tags'],
                'categoryId' => $meta['youtube_category'],
                'description' => $meta['description']
            ],
            'status' => [
                'privacyStatus' => 'public'
            ]
        ];
        $client = Admin::GetGoogleClient();
        $youtube = new \Google_Service_YouTube($client);

        $listResponse = $youtube->videos->listVideos('snippet,status',['id' => $id]);
        if(empty($listResponse)) {
            // video not found
        } else {
            $video = $listResponse[0];
            $snippet = $video['snippet'];
            $status = $video['status'];
            $status['privacyStatus'] = 'public';
            $snippet['categoryId'] = $meta['youtube_category'];
            $snippet['description'] = $meta['description'];
            $snippet['title'] = $meta['title'];
            $snippet['tags'] = $meta['tags'];

            $youtube->videos->update('snippet,status', $video);
        }
    }

    /**
     * Returns a list of videos
     * @param bool $private
     * @return array
     */
    public function getVideos($private = false)
    {
        $client = Admin::GetGoogleClient();
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
                foreach ($playlistItemsResponse['items'] as $playlistItem) {
                    if (!$private || $playlistItem['status']['privacyStatus'] === 'private') {
                        $result[$playlistItem['snippet']['resourceId']['videoId']] = sprintf(
                            '%s (%s, %s)',
                            $playlistItem['snippet']['title'],
                            strftime('%x %T', strtotime($playlistItem['snippet']['publishedAt'])),
                            $playlistItem['snippet']['resourceId']['videoId']);
                    }
                }
            }

        } catch (\Google_Service_Exception $e) {
            error_log(sprintf('A service error occurred: %s', htmlspecialchars($e->getMessage())));

        } catch (\Google_Exception $e) {
            error_log(sprintf('An client error occurred: %s', htmlspecialchars($e->getMessage())));
        }

        return $result;
    }
}