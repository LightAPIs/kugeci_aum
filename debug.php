<?php
require('kugeci.php');

class AudioStationResult {
    private $items;
    public function __construct() {
        $this->items = array();
    }

    public function addTrackInfoToList($artist, $title, $id, $partialLyric) {
        printf('<br />');
        printf('artist = %s\n', $artist);
        printf('title = %s\n', $title);
        printf('id = %s\n', $id);
        printf('partialLyric = %s\n', $partialLyric);
        printf('<br />');

        array_push($this->items, array(
            'artist' => $artist,
            'title' => $title,
            'id' => $id,
            'partialLyric' => $partialLyric,
        ));
    }

    public function addLyrics($lyric, $id) {
        printf('<br />');
        printf('song id: %s\n', $id);
        printf('song lyric:\n');
        printf('***** BEGIN OF LYRIC *****\n');
        printf('%s\n', $lyric);
        printf('***** END OF LYRIC *****\n');
        printf('<br />');
    }

    public function getFirstItem() {
        if (count($this->items) > 0) {
            return $this->items[0];
        }
        return null;
    }
}

$title = '谢谢你的爱';
$artist = '刘德华';

echo '测试开始...<br />变量:<$title="谢谢你的爱"; $artist="刘德华"><br />';
$testObj = new AudioStationResult();
$downloader = (new ReflectionClass('Kugeci'))->newInstance();
$count = $downloader->getLyricsList($artist, $title, $testObj);
if ($count > 0) {
    $item = $testObj->getFirstItem();
    $downloader->getLyrics($item['id'], $testObj);
} else {
    echo '<br />没有查找的任何歌词！<br />';
}
