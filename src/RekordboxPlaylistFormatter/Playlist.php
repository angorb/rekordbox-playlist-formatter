<?php

namespace Oxcrime\RekordboxPlaylistFormatter;

class Playlist
{

    private const DELIMITER = "\t";

    private $columns;
    private $tracklist;

    /**
     * @param string $filename 
     * @return $this 
     */
    public function __construct(string $filename)
    {
        $inFile = fopen($filename, 'r') or die('no file');

        $rowIndex = 0;
        $tracklist = [];
        while ($row = fgetcsv($inFile, 0, self::DELIMITER)) {

            // strip non-alphanumeric ASCII characters since the Rekordbox export file is trash
            array_walk($row, array($this, 'stripInvalidCharacters'));

            // handle the header row of the input file
            if ($rowIndex++ === 0) {
                $this->columns = $row;
                foreach ($row as $index => $column) {
                    if ($column === Column::TRACK_NUMBER) {
                        $trackNumberIndex = $index;
                    }

                }
                continue;
            }

            if (count($row) !== count($this->columns)) {
                continue;
            }

            if (!isset($trackNumberIndex)) {
                $tracklist[] = array_combine($this->columns, $row);
                continue;
            }

            $tracklist[$row[$trackNumberIndex]] = array_combine($this->columns, $row);
        }

        $this->tracklist = $tracklist;

        return $this;
    }

    /** 
     * Returns the number of tracks in the playlist
     * 
     * @return int  
     * */
    public function getTrackCount()
    {
        return count($this->tracklist);
    }

    /**
     * Returns track information for the playlist
     * 
     * @param null|array $columns 
     * @return array 
     */
    public function getTracklist(?array $columns = null)
    {
        $tracklist = $this->tracklist;
        if (!empty($columns)) {
            $tracklist = $this->limitRecordset($columns);
        }
        return $tracklist;
    }

    /** 
     * Returns a list of data columns available in the Playlist
     * 
     * @return array|null 
     * */
    public function getColumns()
    {
        return $this->columns;
    }

    /** 
     * Returns an associative array (hours, minutes, seconds) containing the sum of
     * the length of the tracks in the Playlist
     * 
     * @return (string|int)[]  
     * */
    public function getTime()
    {

        $this->assertColumnExists(Column::TIME);

        $hours = $minutes = $seconds = 0;
        foreach ($this->tracklist as $track) {
            $time = explode(":", $track[Column::TIME]);
            $segments = count($time);
            $hours += $time[$segments - 3] ?? 0;
            $minutes += $time[$segments - 2];
            $seconds += $time[$segments - 1];

            if ($minutes > 59) {
                $hours++;
                $minutes = $minutes % 60;
            }

            if ($seconds > 59) {
                $minutes++;
                $seconds = $seconds % 60;
            }
        }

        return [
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }

    /** 
     * Returns the average tempo (BPM) of the tracks in the playlist
     * 
     * @return int|float  
     * */
    public function getAverageBpm()
    {
        $this->assertColumnExists(Column::BPM);
        $bpm = array_column($this->tracklist, Column::BPM);
        return array_sum($bpm) / count($bpm);
    }

    /** 
     * Returns an array of genre statistics for the Playlist
     * (genre name, # of tracks, % of total)
     * 
     * @return array  */
    public function getGenres()
    {
        $this->assertColumnExists(Column::GENRE);

        $genres = [];
        $totalCount = $this->getTrackCount();
        foreach ($this->tracklist as $track) {
            $name = empty($track[Column::GENRE]) ? 'Unknown' : $track[Column::GENRE];
            $key = strtolower(str_replace(" ", "", $name)); // normalize
            $count = empty($genres[$key]) ? 1 : $genres[$key]['count'] + 1;
            $percent = $count / $totalCount * 100;
            $genres[$key] = [
                'name' => $name,
                'count' => $count,
                'percent' => $percent,
            ];
        }

        uasort($genres, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $genres;
    }


    /**
     * Asserts that a specified column is available for the track data in the Playlist
     * 
     * @param string $columnName 
     * @return void 
     */
    private function assertColumnExists(string $columnName)
    {
        assert(in_array($columnName, $this->columns), "Check for required column '{$columnName}'");
    }

    /**
     * Returns the parsed tracklist as JSON. If $download is indicated, 
     * force a download of the file via HTTP header content-type
     * 
     * @param bool $download 
     * @return string|false 
     */
    public function getJson(bool $download)
    {
        $json = json_encode($this->tracklist, JSON_PRETTY_PRINT);

        if($download){
            header('Content-disposition: attachment; filename=file.json');
            header('Content-type: application/json');
            echo $json;
        }

        return $json;
    }
    
    /**
     * Removes invalid characters (non-ascii, invisibles) from the strings read from the input file
     * 
     * @param string $string 
     * @param mixed $key 
     * @return void 
     */
    private function stripInvalidCharacters(string &$string, $key)
    {
        $string = preg_replace('/[\x00-\x1F\x7F\xFF\xFE]/', '', $string);
    }

    
    /**
     * Removes all columns not specified in $columns from the track data array 
     * 
     * @param array $columns 
     * @return array 
     */
    private function limitRecordset(array $columns)
    {
        $recordset = [];
        foreach ($this->tracklist as $trackIndex => $track) {
            foreach ($track as $propertyName => $property) {
                if (in_array($propertyName, $columns)) {
                    $recordset[$trackIndex][$propertyName] = $property;
                }
            }
        }
        return $recordset;
    }
}
