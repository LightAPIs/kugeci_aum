<?php
require('phpQuery.php');

class AumKuHandler {
    public static $siteSearch = 'https://www.kugeci.com/search?q=';
    public static $siteDownloadLrc = 'https://www.kugeci.com/download/lrc/';
    public static $siteDownloadTxt = 'https://www.kugeci.com/download/txt/';
    public static $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36';

    public static function getContent($url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate,br');
        curl_setopt($curl, CURLOPT_USERAGENT, self::$userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result === false ? '' : $result;
    }

    public static function search($title, $artist) {
        $results = array();
        $url = self::$siteSearch . urlencode($title);
        $items = phpQuery::newDocument(self::getContent($url))->find('#tablesort tbody tr');
        foreach($items as $ele) {
            $song = '';
            $id = '';
            $singers = array();

            $links = pq($ele)->find('td a');
            foreach($links as $li) {
                $href = pq($li)->attr('href');
                $text = trim(pq($li)->text());
                if (strpos($href, '/song/') !== false) {
                    if ($text !== '') {
                        $song = $text;
                        $id = self::getIdFromSrc($href);
                    }
                } elseif (strpos($href, '/singer/') !== false) {
                    array_push($singers, $text);
                }
            }
            $des = trim(pq($ele)->find('td.date')->text());

            if ($song !== '' && $id !== '' && count($singers) > 0) {
                array_push($results, array('song' => $song, 'id' => $id, 'singers' => $singers, 'des' => $des));
            }
        }

        return $results;
    }

    public static function downloadLyric($songId) {
        $res = '';
        $downloadLrcUrl = self::$siteDownloadLrc . $songId;
        $res = self::getContent($downloadLrcUrl);
        if ($res === '') {
            $downloadTxtUrl = self::$siteDownloadTxt . $songId;
            $res = self::getContent($downloadTxtUrl);
        }
        return $res;
    }

    public static function getIdFromSrc($src) {
        if (preg_match('/\/song\/\w+/i', $src)) {
            preg_match('/\/song\/(\w+)/i', $src, $matches);
            return $matches[1];
        }
        return '';
    }
}
