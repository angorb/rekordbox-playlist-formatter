<?php

namespace Oxcrime\RekordboxPlaylistFormatter;

class Key
{
    public const UNICODE_FLAT_CHARACTER = "\u{266D}";

    private const NATURAL = 0;
    private const SHARP = 1;
    private const FLAT = 2;

    private const MINOR = 0;
    private const MAJOR = 1;

    public const FORMAT_RELATIVE_FULLTEXT = 0;
    public const FORMAT_RELATIVE_SHORT = 1;
    public const FORMAT_ACCIDENTAL_FULLTEXT = 3;
    public const FORMAT_ACCIDENTAL_SHORT = 4;
    public const FORMAT_ACCIDENTAL_UNICODE = 5;

    private static $relative = [
        self::MINOR => [
            self::FORMAT_RELATIVE_FULLTEXT => "Minor",
            self::FORMAT_RELATIVE_SHORT => "Min.",
        ],
        self::MAJOR => [
            self::FORMAT_RELATIVE_FULLTEXT => "Major",
            self::FORMAT_RELATIVE_SHORT => "Maj.",
        ],
    ];

    private static $accidental = [
        self::NATURAL => [
            self::FORMAT_ACCIDENTAL_FULLTEXT => '',
            self::FORMAT_ACCIDENTAL_SHORT => '',
            self::FORMAT_ACCIDENTAL_UNICODE => '',
        ],
        self::SHARP => [
            self::FORMAT_ACCIDENTAL_FULLTEXT => ' Sharp',
            self::FORMAT_ACCIDENTAL_SHORT => '#',
            self::FORMAT_ACCIDENTAL_UNICODE => '#',
        ],
        self::FLAT => [
            self::FORMAT_ACCIDENTAL_FULLTEXT => ' Flat',
            self::FORMAT_ACCIDENTAL_SHORT => 'b',
            self::FORMAT_ACCIDENTAL_UNICODE => self::UNICODE_FLAT_CHARACTER,
        ],
    ];

    // TODO match notation to Rekordbox key notation
    private static $conversions = [
        'a20' => '1a',
        'b01' => '1b',
        'e20' => '2a',
        'f11' => '2b',
        'b20' => '3a',
        'd21' => '3b',
        'f00' => '4a',
        'a21' => '4b',
        'c00' => '5a',
        'e21' => '5b',
        'g00' => '6a',
        'b21' => '6b',
        'd00' => '7a',
        'f01' => '7b',
        'a00' => '8a',
        'c01' => '8b',
        'e00' => '9a',
        'g01' => '9b',
        'b00' => '10a',
        'd01' => '10b',
        'f10' => '11a',
        'a01' => '11b',
        'd20' => '12a',
        'e01' => '12b',
    ];

    private $key = null;
    private $relativeFormat = self::FORMAT_RELATIVE_FULLTEXT;
    private $accidentalFormat = self::FORMAT_ACCIDENTAL_UNICODE;

    private function __construct(string $key, ?array $options)
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }

        $this->key = $key;

        return $this;
    }

    /**
     * Creates a new instance of Key from an arbitrary string
     * @param string $key
     * @param null|array $options
     * @return Key|void
     */
    public static function parse(string $key, ?array $options = null)
    {

        $key = strtolower(trim($key)); // normalize the string

        // is the key in camelot notation?
        if (self::isCamelotKey($key)) {
            return new self(array_search($key, self::$conversions), $options);
        }

        // try to parse out the string
        $stringSegments = explode(" ", $key);

        if (count($stringSegments) === 2) {
            // ex. 'F# Minor', 'G Major'
            $pitch = substr($stringSegments[0], 0, 1);
            $accidental = self::NATURAL;
            if (strlen($stringSegments[0]) === 2) {
                $accidental = self::keywordAsInt(substr($stringSegments[0], 1, 1));
            }
            $relative = self::keywordAsInt(rtrim($stringSegments[1], " \t."));
        }

        if (count($stringSegments) === 3) {
            $pitch = $stringSegments[0];
            $accidental = self::keywordAsInt($stringSegments[1]);
            $relative = rtrim($stringSegments[2], " \t.");
        }

        $parsedKey = $pitch . $accidental . $relative;
        if (array_key_exists($parsedKey, self::$conversions)) {
            return new self($parsedKey, $options);
        }

        throw new Exceptions\InvalidFormatException("Unable to parse string '{$key}':{$parsedKey}");
    }

    public static function isCamelotMatch(string $a, string $b)
    {
        $splitA = self::splitCamelotKey($a);
        $splitB = self::splitCamelotKey($b);

        if(abs($splitA[0] - $splitB[0]) > 1){
            return false;
        }

        if($splitA[1] !== $splitB[1] && $splitA[0] !== $splitB[0]){
            return false;
        }

        return true;
    }

    private static function keywordAsInt(string $string)
    {

        if (is_numeric($string)) {
            return $string;
        }

        switch ($string) {
            case 'b':
            case self::UNICODE_FLAT_CHARACTER:
            case 'flat':
                return self::FLAT;
                break;
            case '#':
            case 'sharp':
                return self::SHARP;
                break;
            case 'major':
            case 'maj.':
            case 'maj':
                return self::MAJOR;
                break;
            case 'minor':
            case 'min.':
            case 'min':
                return self::MINOR;
                break;
            default:
                return 999; /// invalid / not found
                break;
        }
    }

    public function toCamelot()
    {
        return strtoupper(self::$conversions[$this->key]);
    }

    public function toString()
    {
        $pitch = strtoupper(substr($this->key, 0, 1));
        $accidental = substr($this->key, 1, 1);
        $relative = substr($this->key, 2, 1);

        return $pitch
        . self::$accidental[$accidental][$this->accidentalFormat]
        . " "
        . self::$relative[$relative][$this->relativeFormat];
    }

    /**
     * Set the formatting options.
     * @param array $options
     * @return void
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option) {
            switch ($option) {
                case self::FORMAT_RELATIVE_FULLTEXT:
                    $this->relativeFormat = self::FORMAT_RELATIVE_FULLTEXT;
                    break;
                case self::FORMAT_RELATIVE_SHORT:
                    $this->relativeFormat = self::FORMAT_RELATIVE_SHORT;
                    break;
                case self::FORMAT_ACCIDENTAL_FULLTEXT:
                    $this->accidentalFormat = self::FORMAT_ACCIDENTAL_FULLTEXT;
                    break;
                case self::FORMAT_ACCIDENTAL_SHORT:
                    $this->accidentalFormat = self::FORMAT_ACCIDENTAL_SHORT;
                    break;
                case self::FORMAT_ACCIDENTAL_UNICODE:
                    $this->accidentalFormat = self::FORMAT_ACCIDENTAL_UNICODE;
                    break;
            }
        }
    }

    private static function splitCamelotKey(string $key)
    {
        $alpha = substr($key, -1); // split alpha component
        $num = str_replace($alpha, '', $key); // split numeric component
        return [$num, $alpha];
    }

    public static function isCamelotKey(string $key)
    {

        $keyLength = strlen($key);
        // check that string is 2-3 characters long (e.g. "1A", "12B")
        if ($keyLength < 2 || $keyLength > 3) {
            return false;
        }

        // split the key into alpha and numeric parts
        $key = strtolower($key);
        $camelotKey = self::splitCamelotKey($key);

        // ensure the numeric key is in range (1-12)
        if ($camelotKey[0] < 1 || $camelotKey[0] > 12) {
            return false;
        }

        // ensure the only alpha characters are a or b
        if ($camelotKey[1] !== 'a' && $camelotKey[1] !== 'b') {
            return false;
        }

        return true;
    }
}
