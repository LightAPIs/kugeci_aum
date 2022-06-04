<?php
require('phpQuery.php');

class Kugeci {
    private $site = 'https://www.kugeci.com/';
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.63 Safari/537.36';
    private $mArtist = '';
    private $mTitle = '';
    public function __construct() {}

    public function getLyricsList($artist, $title, $info) {
        $artist = trim($artist);
        $this->mArtist = $artist;
        $title = trim($title);
        $this->mTitle = $title;
        $list = $this->search($title);
        if (count($list) === 0) {
            return 0;
        }

        $exactMatchArray = array();
        $partialMatchArray = array();
        foreach ($list as $item) {
            $lowTitle = strtolower($title);
            $lowSong = strtolower($item['song']);

            if ($lowTitle === $lowSong) {
                array_push($exactMatchArray, $item);
            } elseif (strpos($lowSong, $lowTitle) !== false || strpos($lowTitle, $lowSong) !== false) {
                array_push($partialMatchArray, $item);
            }
        }

        $songArray = array();
        if (count($exactMatchArray) > 0) {
            $songArray = $exactMatchArray;
        } elseif (count($partialMatchArray) > 0) {
            $songArray = $partialMatchArray;
        }

        if (count($songArray) === 0) {
            return 0;
        }

        $foundArray = array();
        foreach ($songArray as $item) {
            $lowArtist = strtolower($artist);
            foreach ($item['singers'] as $singer) {
                $lowSinger = strtolower($singer);
                if (strpos($lowArtist, $lowSinger) !== false) {
                    array_push($foundArray, $item);
                    break;
                }
            }
        }

        usort($foundArray, array($this, 'compare'));
        foreach ($foundArray as $item) {
            $info->addTrackInfoToList(implode('&', $item['singers']), $item['song'], $item['id'], $item['date']);
        }
        return count($foundArray);
    }

    public function getLyrics($id, $info) {
        $lyric = $this->downloadLyric($id);
        if ($lyric === '') {
            return false;
        }

        $info->addLyrics($lyric, $id);
        return true;
    }

    private function search($word) {
        $results = array();
        $url = $this->site . 'search?q=' . urlencode($word);
        $items = phpQuery::newDocument($this->getContent($url))->find('#tablesort tbody tr');
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
                        $id = $this->getIdFromSrc($href);
                    }
                } elseif (strpos($href, '/singer/') !== false) {
                    array_push($singers, $text);
                }
            }
            $date = trim(pq($ele)->find('td.date')->text());

            if ($song !== '' && $id !== '' && count($singers) > 0) {
                array_push($results, array('song' => $song, 'id' => $id, 'singers' => $singers, 'date' => $date));
            }
        }

        return $results;
    }

    private function getContent($url) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate,br');
        curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    private function downloadLyric($songId) {
        $res = '';
        $downloadLrcUrl = $this->site . 'download/lrc/' . $songId;
        $res = $this->getContent($downloadLrcUrl);
        if ($res === '') {
            $downloadTxtUrl = $this->site . 'download/txt/' . $songId;
            $res = $this->getContent($downloadTxtUrl);
        }
        return $res;
    }

    private function compare($lhs, $rhs) {
        $scoreTitleL = $this->getStringSimilarPercent($this->mTitle, $lhs['song']);
        $scoreTitleR = $this->getStringSimilarPercent($this->mTitle, $rhs['song']);
        $scoreArtistL = $this->getStringSimilarPercent($this->mArtist, implode('&', $lhs['singers']));
        $scoreArtistR = $this->getStringSimilarPercent($this->mArtist, implode('&', $rhs['singers']));

        return $scoreTitleR + $scoreArtistR - $scoreTitleL - $scoreArtistL;
    }

    private static function getStringSimilarPercent($lhs, $rhs)
    {
        similar_text($lhs, $rhs, $percent);
        return $percent;
    }

    private static function getIdFromSrc($src) {
        if (preg_match('/\/song\/\w+/i', $src)) {
            preg_match('/\/song\/(\w+)/i', $src, $matches);
            return $matches[1];
        }
        return '';
    }
}

