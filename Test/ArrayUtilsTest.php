<?php

/* Copyright 2016 FSharpN00b.
This file is part of PHP Monad.

PHP Monad is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PHP Monad is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with PHP Monad.  If not, see <http://www.gnu.org/licenses/>. */

/* Note this statement must be first. */
declare (strict_types = 1);

require_once '../Utils/Common.php';
require_once '../Utils/ArrayUtils.php';

/* Tests for ArrayUtils. */

/* Helper functions. */

if (false === function_exists ('get_maybe_some')) {
    function get_maybe_some (/* mixed */ $value) : Maybe {
        return new Maybe (true, $value);
    }
}
if (false === function_exists ('get_maybe_none')) {
    function get_maybe_none () : Maybe {
        return new Maybe (false, null);
    }
}

function map (/* mixed */ $value) /* : mixed */ { return $value * 2; }

function reduce (/* mixed */ $acc, /* mixed */ $value) /* : mixed */  { return $acc + $value; }

/**
@backupGlobals disabled
*/
class ArrayUtilsTest extends PHPUnit_Framework_TestCase
{
/* ArrayUtils::all */

    public function test_all_empty_array () /* : void */ {
        $this->assertTrue (ArrayUtils::all (array (), function (/* mixed */ $value) : bool { return false; }));
    }
    public function test_all_pass () /* : void */ {
        $a = array (1, 2);
        $this->assertTrue (ArrayUtils::all ($a, 'is_numeric'));
    }
    public function test_all_fail () /* : void */ {
        $a = array (1, 'a');
        $this->assertFalse (ArrayUtils::all ($a, 'is_numeric'));
    }

/* ArrayUtils::any */

    public function test_any_empty_array () /* : void */ {
        $a = array ();
        $this->assertFalse (ArrayUtils::any ($a, function (/* mixed */ $value) : bool { return false; }));
    }
    public function test_any_pass () /* : void */ {
        $a = array (1, 'a');
        $this->assertTrue (ArrayUtils::any ($a, 'is_numeric'));
    }
    public function test_any_fail () /* : void */ {
        $a = array ('a', 'b');
        $this->assertFalse (ArrayUtils::any ($a, 'is_numeric'));
    }

/* ArrayUtils::append */

/**
@dataProvider append_provider
*/
    public function test_append (array $input_array, /* mixed */ $key, /* mixed */ $value, array $expected_input_array, array $expected_result) /* : void */ {
        $result = ArrayUtils::append ($input_array, $key, $value);
        /* Verify the input array is unchanged. */
        $this->assertEquals ($input_array, $expected_input_array);
        $this->assertEquals ($result, $expected_result);
    }

    public static function append_provider () : array {
        return array (
            /* Append value with no key to empty array. */
            array (array (), null, 1, array (), array (1)),
            /* Append value with no key to non-empty array. */
            array (array (1), null, 2, array (1), array (1, 2)),
            /* Append value with string key to empty array. */
            array (array (), 'a', 1, array (), array ('a' => 1)),
            /* Append value with string key to array with string keys. */
            array (array ('a' => 1), 'b', 2, array ('a' => 1), array ('a' => 1, 'b' => 2)),
            /* Append value with string key to array with numeric keys. */
            array (array (1), 'b', 2, array (1), array (0 => 1, 'b' => 2))
        );
    }

/* ArrayUtils::flatten */

/**
@dataProvider flatten_provider
*/
    public function test_flatten (array $input_array, array $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::flatten ($input_array), $expected_result);
    }

    public static function flatten_provider () : array {
        return array (
            /* Empty array. */
            array (array (), array ()),
            /* 1-D array. */
            array (array (1, 2, 3), array (1, 2, 3)),
            /* 2-D array. */
            array (array (1, array (2), 3), array (1, 2, 3)),
            /* 3-D array. */
            array (array (1, array (2, array (3))), array (1, 2, array (3)))
        );
    }

/* ArrayUtils::group_by */

    public function test_group_by_empty_array () /* : void */ {
        $this->assertEquals (ArrayUtils::group_by (array (), function (/* mixed */ $ignore) : string { return ''; }), array ());
    }
    public function test_group_by_non_empty_array () /* : void */ {
        $a = array ('1', '12', '1234', '12345');
        $result = ArrayUtils::group_by ($a, function (string $value) : string {
            if (strlen ($value) < 3) {
                return 'short';
            }
            else {
                return 'long';
            }
        });
        $this->assertEquals ($result, array ('short' => array ('1', '12'), 'long' => array ('1234', '12345')));
    }

/* ArrayUtils::head */

/**
@dataProvider head_provider
*/
    public function test_head (array $input_array, Maybe $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::head ($input_array), $expected_result);
    }

    public static function head_provider () : array {
        return array (
            'Empty array' =>    array (array (), get_maybe_none ()),
            '1 item' =>         array (array ('a' => 1), get_maybe_some ((object) ['key' => 'a', 'value' => 1])),
            '1 item, no key' => array (array (1), get_maybe_some ((object) ['key' => 0, 'value' => 1])),
            '> 1 item' =>       array (array ('a' => 1, 'b' => 2, 'c' => 3), get_maybe_some ((object) ['key' => 'a', 'value' => 1]))
        );
    }

/* ArrayUtils::head_value */

/**
@dataProvider head_value_provider
*/
    public function test_head_value (array $input_array, Maybe $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::head_value ($input_array), $expected_result);
    }

    public static function head_value_provider () : array {
        return array (
            'Empty array' =>    array (array (), get_maybe_none ()),
            '1 item' =>         array (array ('a' => 1), get_maybe_some (1)),
            '1 item, no key' => array (array (1), get_maybe_some (1)),
            '> 1 item' =>       array (array ('a' => 1, 'b' => 2, 'c' => 3), get_maybe_some (1))
        );
    }
    
/* ArrayUtils::map_join */

/**
@dataProvider map_join_provider
*/
    public function test_map_join (array $input_array, callable $f, string $glue, string $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::map_join ($input_array, $f, $glue), $expected_result);
    }

    public static function map_join_provider () : array {
        return array (
            array (array (), function () /* : mixed */ { return null; }, '', ''),
            array (array ('a', 'b', 'c'), 'strtoupper', '', 'ABC'),
            array (array ('a', 'b', 'c'), 'strtoupper', ', ', 'A, B, C')
        );
    }

/* ArrayUtils::map_reduce */

/**
@dataProvider map_reduce_provider
*/
    public function test_map_reduce (array $input_array, /* mixed */ $initial, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::map_reduce ($input_array, 'map', 'reduce', $initial), $expected_result);
    }

    public static function map_reduce_provider () : array {
        return array (
            /* Empty input array with no initial value. */
            array (array (), null, null),
            /* Empty input array with initial value. */
            array (array (), 10, 10),
            /* Single-value array with no initial value. */
            array (array (1), null, 2),
            /* Single-value array with initial value. */
            array (array (1), 10, 12),
            /* Multi-value array with no initial value. */
            array (array (1, 2, 3), null, 12),
            /* Multi-value array with initial value. */
            array (array (1, 2, 3), 10, 22)
        );
    }

/* ArrayUtils::map_reduce_2 */

/**
@dataProvider map_reduce_2_provider
*/
    public function test_map_reduce_2 (array $input_array, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::map_reduce_2 ($input_array, 'map', 'reduce'), $expected_result);
    }

    public static function map_reduce_2_provider () : array {
        return array (
            array (array (), null),
            array (array (1), 2),
            array (array (1, 2, 3), 12)
        );
    }

/* ArrayUtils::multi_reduce */

/* We are not currently using ArrayUtils::multi_reduce. This test works. We are keeping it for future reference. */
// Note the _ prefix prevents PHPUnit from calling this.
    public function _test_multi_reduce () /* : void */ {
        $a = array (1 => 2, 3 => 4, 5 => 6);
        $this->assertTrue (12 === ArrayUtils::multi_reduce (function (/* mixed */ $acc, /* mixed */ $x, /* mixed */ $y) /* : mixed */ {
            return $acc + $x + $y;
        }, null, array_keys ($a), array_values ($a)));
    }

/* ArrayUtils::prepend */

/**
@dataProvider prepend_provider
*/
    public function test_prepend (array $input_array, /* mixed */ $key, /* mixed */ $value, array $expected_input_array, array $expected_result) /* : void */ {
        $result = ArrayUtils::prepend ($input_array, $key, $value);
        /* Verify the input array is unchanged. */
        $this->assertEquals ($input_array, $expected_input_array);
        $this->assertEquals ($result, $expected_result);
    }

    public static function prepend_provider () : array {
        return array (
            /* Prepend value with no key to empty array. */
            array (array (), null, 1, array (), array (1)),
            /* Prepend value with no key to non-empty array. */
            array (array (2), null, 1, array (2), array (1, 2)),
            /* Prepend value with string key to empty array. */
            array (array (), 'a', 1, array (), array ('a' => 1)),
            /* Prepend value with string key to array with string keys. */
            array (array ('b' => 2), 'a', 1, array ('b' => 2), array ('a' => 1, 'b' => 2)),
            /* Prepend value with string key to array with numeric keys. */
            array (array (2), 'a', 1, array (2), array ('a' => 1, 0 => 2))
        );
    }

/* ArrayUtils::reduce_2 */

/**
@dataProvider reduce_2_provider
*/
    public function test_reduce_2 (array $input_array, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::reduce_2 ($input_array, 'reduce'), $expected_result);
    }

    public static function reduce_2_provider () : array {
        return array (
            array (array (), null),
            array (array (1), 1),
            array (array (1, 2, 3), 6)
        );
    }

/* ArrayUtils::sort_by */

    public function test_sort_by () /* : void */ {
        $a = array ('abc', 'ab', 'a');
        $result = ArrayUtils::sort_by ($a, function (string $value) : int {
            return strlen ($value);
        });
        $this->assertTrue ($result === array ('a', 'ab', 'abc'));
    }

/* ArrayUtils::tail */

/**
@dataProvider tail_provider
*/
    public function test_tail (array $input_array, array $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::tail ($input_array), $expected_result);
    }

    public static function tail_provider () : array {
        return array (
            'Empty array' =>    array (array (), array ()),
            '1 item' =>         array (array (1), array ()),
            '> 1 item' =>       array (array (1, 2, 3), array (2, 3))
        );
    }

/* ArrayUtils::try_find */

/**
@dataProvider try_find_provider
*/
    public function test_try_find (array $input_array, Maybe $expected_result) /* : void */ {
        $f = function (/* mixed */ $key, /* mixed */ $value) {
            return ($value === 1);
        };
        $this->assertEquals (ArrayUtils::try_find ($f, $input_array), $expected_result);
    }

    public static function try_find_provider () : array {
        return array (
            'Empty array' =>    array (array (), get_maybe_none ()),
            'Success' =>        array (array ('a' => 1), get_maybe_some ((object) ['key' => 'a', 'value' => 1])),
            'Failure' =>        array (array ('a' => 2), get_maybe_none ())
        );
    }
    
/* ArrayUtils::uncombine */

    public function test_uncombine () /* : void */ {
        $a = ArrayUtils::uncombine (array ('a' => 1, 'b' => 2));
        /* We use == rather than === because ===, when applied to objects, tests for identity. */
        $this->assertTrue ($a == array (
            (object) array ('key' => 'a', 'value' => 1),
            (object) array ('key' => 'b', 'value' => 2)));
    }

/* ArrayUtils::windowed */

/**
@dataProvider windowed_provider
*/
    public function test_windowed (array $a, int $size, array $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::windowed ($a, $size), $expected_result);
    }

    public static function windowed_provider () : array {
        return array (
            'Size = 0' =>           array (array (0), 0, array ()),
            'Size > array size' =>  array (array (0), 2, array ()),
            'Size = array size' =>  array (array (0, 1), 2, array (array (0, 1))),
            'Size = 1' =>           array (array (0, 1, 2), 1, array (array (0), array (1), array (2))),
            'Size < array size' =>  array (array (0, 1, 2), 2, array (array (0, 1), array (1, 2)))
        );
    }

/* ArrayUtils::zip */

/**
@dataProvider zip_provider
*/
    public function test_zip (array $input_array_1, array $input_array_2, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals (ArrayUtils::zip ($input_array_1, $input_array_2), $expected_result);
    }

    public static function zip_provider () : array {
        return array (
            array (array ('a', 'b'), array (1, 2), array (array ('a', 1), array ('b', 2))),
            /* Zip arrays with different lengths. */
            array (array ('a'), array (1, 2), array (array ('a', 1), array (null, 2)))
        );
    }
}

?>