<?php
class LastFM
{
    private $apiKey;
    private $baseUrl = 'http://ws.audioscrobbler.com/2.0/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    private function makeRequest($method, $params = [])
    {
        $params['api_key'] = $this->apiKey;
        $params['method'] = $method;
        $params['format'] = 'json';

        $url = $this->baseUrl . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getArtistInfo($artist)
    {
        return $this->makeRequest('artist.getInfo', [
            'artist' => $artist,
            'autocorrect' => 1
        ]);
    }

    public function getTrackInfo($artist, $track)
    {
        return $this->makeRequest('track.getInfo', [
            'artist' => $artist,
            'track' => $track
        ]);
    }

    public function getSimilarArtists($artist)
    {
        return $this->makeRequest('artist.getSimilar', [
            'artist' => $artist,
            'limit' => 10
        ]);
    }

    public function getTopTracks($artist)
    {
        return $this->makeRequest('artist.getTopTracks', [
            'artist' => $artist,
            'limit' => 10
        ]);
    }
}