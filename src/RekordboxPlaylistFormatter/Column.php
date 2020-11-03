<?php 

namespace Oxcrime\RekordboxPlaylistFormatter;

use ReflectionClass;

class Column {

    // Column names from Rekordbox playlist export file
    // Created from Rekordbox 6.2 exports (11/1/2020)
    public const TRACK_NUMBER = "#";
    public const TITLE = "Track Title";
    public const ARTIST = "Artist";
    public const BPM = "BPM";
    public const KEY = "Key";
    public const GENRE = "Genre";
    public const RATING = "Rating";
    public const TIME = "Time";
    public const DATE_ADDED = "Date Added";
    public const COMMENTS = "Comments";
    public const BITRATE = "Bitrate";
    public const YEAR = "Year";
    public const MY_TAG = "My Tag";
    public const PLAY_COUNT = "DJ Play Count";
    public const COLOR = "Color";
    public const KUVO_MESSAGE = "KUVO Message";
    public const DATE_CREATED = "Date Created";
    public const FILE_LOCATION = "Location";
    public const FILE_NAME = "File Name";
    public const BITDEPTH = "Bitdepth";
    public const SAMPLE_RATE = "Sample Rate";
    public const RELEASE_DATE = "Release Date";
    public const ORIGINAL_ARTIST = "Original Artist";
    public const LABEL = "Label";
    public const REMIXER = "Remixer";
    public const MIX_NAME = "Mix Name";
    public const FILE_TYPE = "File Type";
    public const LYRICIST = "Lyricist";
    public const COMPOSER = "Composer";
    public const ALBUM_TRACK_NUMBER = "Track number";
    public const ALBUM_ARTIST = "Album Artist";
    public const DISC_NUMBER = "Disc number";
    public const FILE_SIZE = "Size";
 
    public function getAll()
    {
        $columns = (new ReflectionClass($this))->getConstants();
        ksort($columns);
        return $columns;
    }

}