<?php

namespace Esyede;

class Fuzzy
{
    const DID_NOT_MATCH = 0;
    const SECOND_STARTS_WITH_FIRST = 1;
    const SECOND_CONTAINS_FIRST = 2;
    const FIRST_STARTS_WITH_SECOND = 3;
    const FIRST_CONTAINS_SECOND = 4;
    const LEVENSHTEIN_DISTANCE_CHECK = 5;
    const LONGEST_COMMON_SUBSTRING_CHECK = 6;

    public $maximum = 0.3;
    public $minimum = 0.7;

    protected $data;
    protected $attributes;
    protected $keyword;

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Search using Levenshtein Distance (LD) Algorithm and
     * Longest Common Substring (LCS) Algorithm.
     *
     * @param string|null  $keyword
     * @param string|array $keyword
     *
     * @return array
     */
    public function search($keyword = null, $attributes)
    {
        $keyword = $keyword ? strtolower($keyword) : strtolower($this->keyword);
        $this->attributes = is_array($attributes) ? $attributes : [$attributes];

        $results = [];

        if (! $keyword) {
            return [];
        }

        foreach ($this->data as $array) {
            $found = false;

            foreach ($this->attributes as $attribute) {
                if ($found || ! isset($array[$attribute])) {
                    continue;
                }

                $value = strtolower($array[$attribute]);

                if (! $value) {
                    continue;
                }

                $type = self::DID_NOT_MATCH;

                if (strpos($keyword, $value) !== false && strpos($keyword, $value) === 0) {
                    $type = self::SECOND_STARTS_WITH_FIRST;
                    $value = strlen($value);
                } elseif (strpos($keyword, $value) > 0) {
                    $type = self::SECOND_CONTAINS_FIRST;
                    $value = strlen($value);
                } elseif (strpos($value, $keyword) !== false && strpos($value, $keyword) === 0) {
                    $type = self::FIRST_STARTS_WITH_SECOND;
                    $value = strlen($value);
                } elseif (strpos($value, $keyword) > 0) {
                    $type = self::FIRST_CONTAINS_SECOND;
                    $value = strlen($value);
                } elseif ($this->check($distance = $this->levenshtein($value, $keyword), $keyword)) {
                    $type = self::LEVENSHTEIN_DISTANCE_CHECK;
                    $value = $distance / strlen($keyword);
                } else {
                    $lcs = $this->longest($value, $keyword);
                    $similarity = strlen($lcs) / strlen($keyword);

                    if ($similarity > $this->minimum) {
                        $type = self::LONGEST_COMMON_SUBSTRING_CHECK;
                        $value = strlen($lcs) / strlen($value) * (-1);
                    }
                }

                if ($type !== self::DID_NOT_MATCH) {
                    array_push($results, [$array, $attribute, $type, $value]);
                    $found = true;
                }
            }
        }

        usort($results, [$this, 'sort']);

        return $results;
    }

    /**
     * Check whether Levenshtein Distance is small enough.
     *
     * @param int    $distance
     * @param string $string
     *
     * @return bool
     */
    protected function check($distance, $string)
    {
        return ($distance / strlen($string)) <= $this->maximum;
    }

    /**
     * Get Longest Common Substring.
     *
     * @param string $first
     * @param string $second
     *
     * @return string
     */
    protected function longest($first, $second)
    {
        $length1 = strlen($first);
        $length2 = strlen($second);
        $result = '';

        if ($length1 === 0 || $length2 === 0) {
            return $result;
        }

        $sub = [];

        for ($i = 0; $i < $length1; $i++) {
            $sub[$i] = [];

            for ($j = 0; $j < $length2; $j++) {
                $sub[$i][$j] = 0;
            }
        }

        $size = 0;

        for ($i = 0; $i < $length1; $i++) {
            for ($j = 0; $j < $length2; $j++) {
                if ($first[$i] === $second[$j]) {
                    if ($i === 0 || $j === 0) {
                        $sub[$i][$j] = 1;
                    } else {
                        $sub[$i][$j] = $sub[$i - 1][$j - 1] + 1;
                    }

                    if ($sub[$i][$j] > $size) {
                        $size = $sub[$i][$j];
                        $result = '';
                    }

                    if ($sub[$i][$j] === $size) {
                        $result = substr($first, $i - $size + 1, $size);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Sort arrays based on it's type and value.
     *
     * @param array $first
     * @param array $second
     *
     * @return int
     */
    protected function sort(array $first, array $second)
    {
        if ($first[2] === $second[2]) {
            if ($first[3] === $second[3]) {
                return 0;
            } else {
                return $first[3] < $second[3] ? -1 : 1;
            }
        } else {
            return $first[2] < $second[2] ? -1 : 1;
        }
    }

    /**
     * Wrap buil-in levenshtein function.
     * See: https://www.php.net/manual/en/function.levenshtein.php#113702
     *
     * @param  string $first
     * @param  string $second
     *
     * @return int
     */
    protected function levenshtein($first, $second)
    {
        $characters = [];

        $first = $this->ascii($first, $characters);
        $second = $this->ascii($second, $characters);

        return levenshtein($first, $second);
    }

    /**
     * Convert an UTF-8 encoded string to a single-byte string
     * to improve levenstein accuracy.
     * See: https://www.php.net/manual/en/function.levenshtein.php#113702
     *
     * @param  string $string
     * @param  array  &$mapings
     *
     * @return string
     */
    protected function ascii($string, &$mapings)
    {
        // Find all multibyte characters.
        $matches = [];

        if (! preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $string, $matches)) {
            return $string; // Plain ascii string
        }

        // Update the encoding map with the characters not already met.
        foreach ($matches[0] as $match) {
            if (! isset($mapings[$match])) {
                $mapings[$match] = chr(128 + count($mapings));
            }
        }

        // Finally, remap non-ascii characters.
        return strtr($string, $mapings);
    }
}
