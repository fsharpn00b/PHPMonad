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

// TODO3 Add zip_2, which takes multiple arrays and converts them to a single array of cross-sections, similar to multi_reduce.
// TODO2 Params should be reordered so that array param comes last. This helps with composition.

/* Note this statement must be first. */
declare (strict_types = 1);

require_once '../Utils/Common.php';

/* Higher-order functions for arrays. */
class ArrayUtils {

    /* ArrayUtils::all
    Tests whether all values in the input array satisfy the given predicate.

    Example usage:
        $a = array (1, 'a');
        $result = ArrayUtils::all ($a, 'is_numeric');
        // $result === false

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to test. If the array is empty, @return is true.
    @f - The predicate function. Signature:
        mixed @value - The current array value.
        bool @return - True if the value satisfies the predicate; otherwise, false.
    @return - True if all values in @a satisfy the predicate; otherwise, false.
    */
    public static function all (array $a, callable $f) : bool {
        $result = true;
        if (empty($a)) {
            return $result;
        }
        foreach ($a as $value) {
            if (false === $f ($value)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /* ArrayUtils: any
    Tests whether any value in the input array satisfies the given predicate.

    Example usage:
        $a = array (1, 'a');
        $result = ArrayUtils::any ($a, 'is_numeric');
        // $result === true

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to test. If the array is empty, @return is false.
    @f - The predicate function. Signature:
        mixed @value - The current array value.
        bool @return - True if the value satisfies the predicate; otherwise, false.
    @return - True if any value in @a satisfies the predicate; otherwise, false.
    */
    public static function any (array $a, callable $f) : bool {
        $result = false;
        if (empty($a)) {
            return $result;
        }
        foreach ($a as $value) {
            if (true === $f ($value)) {
                $result = true;
                break;
            }
        }
        return $result;
    }
    
    /* ArrayUtils::append
    Appends the given key and value to the input array and returns the updated array. Unlike array_push, this function does not
    modify the input array.

    Example usage:
        $result = ArrayUtils::append (array ('a' => 1), 'b', 2);
        // $result === array ('a' => 1, 'b' => 2)

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    This function uses array_merge. See:
    https://secure.php.net/manual/en/function.array-merge.php

    "If the input arrays have the same string keys, then the later value for that key will overwrite the previous one. If,
    however, the arrays contain numeric keys, the later value will not overwrite the original value, but will be appended."

    That is, array_merge (array (0 => 'a'), array (0 => 'b')) === array (0 => 'a', 1 => 'b')

    "Values in the input array with numeric keys will be renumbered with incrementing keys starting from zero in the result
    array."

    That is, array_merge (array (2 => 'a'), array (5 => 'b')) === array (0 => 'a', 1 => 'b')

    @a - The array to append the value to.
    @key - The key to append. Can be null.
    @value - The value to append. Can be null.
    @return - The result of appending @key => @value to @a.
    */
    public static function append (array $a, /* mixed */ $key, /* mixed */ $value) : array {
        if (true === is_null ($key)) {
            /* array_merge expects two arrays, so we wrap $value in an extra array layer, which is removed in the merge. */
            $result = array_merge ($a, array ($value));
        }
        else {
            $result = array_merge ($a, array ($key => $value));
        }
        return $result;
    }

    /* ArrayUtils::flatten
    Flattens the input array by one level and returns the updated array.

    Example usage:
        $a = array (
            1,
            array (2, 3)
        );
        $result = ArrayUtils::flatten ($a);
        // $result === array (1, 2, 3)

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to flatten. If the array is empty, @return is an empty array.
    @return - The result of flattening @a.
    */
    public static function flatten (array $a) : array {
        /* Make both the keys and values explicit. */
        $a = ArrayUtils::uncombine ($a);
        return array_reduce ($a, function (array $acc, StdClass $kv) : array {
            /* Append the current item to the accumulator. The way of doing so depends on whether the item is itself an array. */
            if (false === is_array ($kv->value)) {
                $acc = ArrayUtils::append ($acc, $kv->key, $kv->value);
            }
            else {
                $acc = array_merge ($acc, $kv->value);
            }
            return $acc;
        }, array ());
    }

    /* ArrayUtils::group_by
    Groups the elements in the input array according to a key selector function.

    Example usage:
        $a = array ('1', '12', '1234', '12345');
        $result = ArrayUtils::group_by ($a, function (string $value) : string {
            if (strlen ($value) < 3) {
                return 'short';
            }
            else {
                return 'long';
            }
        });
        // $result === array ('short' => array ('1', '12'), 'long' => array ('1234', '12345'));

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to group. If the array is empty, @return is an empty array.
    @f - The key selector function. Signature:
        mixed @value - The current array value.
        mixed @return - The key derived from @value.
    @return - The result of grouping @a.
    */
    public static function group_by (array $a, callable $f) : array {
        /* Make both the keys and values explicit. We want to retain the keys when we sort the values into groups. */
        $a = ArrayUtils::uncombine ($a);
        return array_reduce ($a, function (array $acc, StdClass $kv) use ($f) /* : mixed */ {
            /* Apply the key selector function to the value to get the group key. */
            $group_key = $f ($kv->value);
            /* If the accumulator already a group with this key, get the group. If not, create the group. */
            if (true === isset ($acc[$group_key])) {
                $group_value = $acc[$group_key];
            }
            else {
                $group_value = array ();
            }
            /* Append the original key and value to the group. */
            $group_value = ArrayUtils::append ($group_value, $kv->key, $kv->value);
            /* Add (or update) the group to the accumulator using the group key. */
            $acc[$group_key] = $group_value;
            return $acc;
        }, array ());
    }

    /* ArrayUtils::head
    Returns a Maybe that contains the key and value of the first item in the input array. If the input array is empty, returns an
    empty Maybe.
    
    Example usage:
        $a = array ('a' => 1, 'b' => 2, 'c' => 3);
        $result = ArrayUtils::head ($a);
        $item = $result->value;
        // $item->key === 'a'
        // $item->value === 'b'
        
        $a = array ();
        $result = ArrayUtils::head ($a);
        // $result === new Maybe (false, null)
    
    Remarks
    This function does not modify the input array.
    This function does not recurse into nested arrays.
        
    @a - The input array.
    @return - A Maybe.
        If @a is not empty:
            @is_some - true.
            @value - An object.
                @key - The key of the first item in @a.
                @value - The value of the first item in @a.
        If @a is empty:
            @is_some - false.
            @value - null.
    */
    public static function head (array $a) : Maybe {
        if (true === empty ($a)) {
            return new Maybe (false, null);
        }
        else {
            $a = array_slice ($a, 0, 1);
            reset ($a);
            return new Maybe (true, (object) ['key' => key ($a), 'value' => current ($a)]);
        }
    }

    /* ArrayUtils::head_value
    Returns a Maybe that contains the value of the first item in the input array. If the input array is empty, returns an empty
    Maybe.
    
    Example usage:
        $a = array ('a' => 1, 'b' => 2, 'c' => 3);
        $result = ArrayUtils::head_value ($a);
        // $result === new Maybe (true, 1)
        
        $a = array ();
        $result = ArrayUtils::head_value ($a);
        // $result === new Maybe (false, null)
    
    Remarks
    This function does not modify the input array.
    This function does not recurse into nested arrays.
        
    @a - The input array.
    @return - A Maybe.
        If @a is not empty:
            @is_some - true.
            @value - The value of the first item in @a.
        If @a is empty:
            @is_some - false.
            @value - null.
    */
    public static function head_value (array $a) : Maybe {
        $result = ArrayUtils::head ($a);
        if (false === $result->is_some) {
            return $result;
        }
        else {            
            return new Maybe (true, $result->value->value);
        }
    }

    /* ArrayUtils::map_join
    Applies the given transform to the input array and then reduces the transformed array using join.

    Example usage:
        $a = array ('a', 'b', 'c');
        $result = ArrayUtils::map_join ($a, 'strtoupper', ', ');
        // $result === 'A, B, C'

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to reduce. If the array is empty, @return is an empty string.
    @map_f - The map function. Signature:
        mixed @value - The current array value.
        mixed @return - The transformed value.
    @glue - A string to insert between each pair of array elements. Defaults to an empty string.
    @return - The result of reducing @a.
    */
    public static function map_join (array $a, callable $f, string $glue = '') : string {
        return implode ($glue, array_map ($f, $a));
    }

    /* ArrayUtils::map_reduce
    Applies the given transform to the input array and then reduces the transformed array to a single value.

    Example usage:
        function map ($value) { return $value * 2; }
        function reduce ($acc, $value) { return $acc + $value; }
        $a = array (1, 2, 3);
        $result = ArrayUtils::map_reduce ($a, 'map', 'reduce', 10);
        // $result === 22

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to reduce. If the array is empty, @return is @initial.
    @map_f - The map function. Signature:
        mixed @value - The current array value.
        mixed @return - The transformed value.
    @reduce_f - The reducer function. Signature:
        mixed @acc - The accumulator state.
        mixed @value - The current array value.
        mixed @return - The updated accumulator state.
    @initial - The initial state of the accumulator.
    @return - The result of transforming and reducing @a.
    */
    public static function map_reduce (array $a, callable $map_f, callable $reduce_f, /* mixed */ $initial = null) /* : mixed */ {
        return array_reduce (array_map ($map_f, $a), $reduce_f, $initial);
    }

    /* ArrayUtils::map_reduce_2
    Applies the given transform to the input array and then reduces the transformed array to a single value, using
    ArrayUtils::reduce_2.

    Example usage:
        $a = array (1, 2, 3);
        $result = ArrayUtils::map_reduce_2 ($a, 'map', 'reduce');
        // $result === 12

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.
    ArrayUtils::reduce_2 is similar to array_reduce, except that the first value in the array is used as the initial state of the
    accumulator, and the walk through the array starts with the second value. For more information about array_reduce, see:
    https://secure.php.net/manual/en/function.array-reduce.php

    @a - The array to reduce. If the array is empty, @return is null. If the array contains only one value, @return is that
    value.
    @map_f - The map function. Signature:
        mixed @value - The current array value.
        mixed @return - The transformed value.
    @reduce_f - The reducer function. Signature:
        mixed @acc - The accumulator state.
        mixed @value - The current array value.
        mixed @return - The updated accumulator state.
    @return - The result of transforming and reducing @a.
    */
    public static function map_reduce_2 (array $a, callable $map_f, callable $reduce_f) /* : mixed */ {
        return self::reduce_2 (array_map ($map_f, $a), $reduce_f);
    }

    /* We are not currently using this, but it works, so we are keeping it for future reference. */
    /* The $initial parameter is non-optional because there is a non-optional parameter ($a) after it. We could put it after
    ($a), but we wanted to keep the array parameters together.
    The $a parameter is to make sure the caller passes at least one array. array_map uses a similar signature.
    See:
    https://secure.php.net/manual/en/function.array-map.php
    https://secure.php.net/manual/en/functions.arguments.php#functions.variable-arg-list
    */
    private static function multi_reduce (callable $f, /* mixed */ $initial, array $a, ... /* array */ $arrays) /* : mixed */ {
        /* Combine the array parameters. */
        $arrays = ArrayUtils::prepend ($a, null, $arrays);
        /* Get the minimum length of the arrays. */
        $min_length = min (array_map (function (array $a) : int { return count ($a); }, $arrays));
        $acc = $initial;
        for ($loop = 0; $loop < $min_length; $loop++) {
            /* Create a cross section of the arrays. */
            $params = array_map (function (array $a) use ($loop) : array { return $a[$loop]; }, $arrays);
            /* Prepend the accumulator to the cross section. */
            $params = ArrayUtils::prepend ($acc, null, $params);
            /* Apply the reducer function to the accumulator and the cross section. */
            $acc = call_user_func_array ($f, $params);
        }
        return $acc;
    }

    /* ArrayUtils::prepend
    Prepends the given key and value to the input array and returns the updated array. Unlike array_unshift, this function does
    not modify the input array.

    Example usage:
        $result = ArrayUtils::prepend (array ('b' => 2), 'a', 1);
        // $result === array ('a' => 1, 'b' => 2)

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    This function uses array_merge. See:
    https://secure.php.net/manual/en/function.array-merge.php

    "If the input arrays have the same string keys, then the later value for that key will overwrite the previous one. If,
    however, the arrays contain numeric keys, the later value will not overwrite the original value, but will be appended."

    That is, array_merge (array (0 => 'a'), array (0 => 'b')) === array (0 => 'a', 1 => 'b')

    "Values in the input array with numeric keys will be renumbered with incrementing keys starting from zero in the result
    array."

    That is, array_merge (array (2 => 'a'), array (5 => 'b')) === array (0 => 'a', 1 => 'b')

    @a - The array to prepend the value to.
    @key - The key to prepend. Can be null.
    @value - The value to prepend. Can be null.
    @return - The result of prepending @key => @value to @a.
    */
    public static function prepend (array $a, /* mixed */ $key, /* mixed */ $value) : array {
        if (true === is_null ($key)) {
            /* array_merge expects two arrays, so we wrap $value in an extra array layer, which is removed in the merge. */
            $result = array_merge (array ($value), $a);
        }
        else {
            $result = array_merge (array ($key => $value), $a);
        }
        return $result;
    }

    /* ArrayUtils::reduce_2
    Reduces the input array to a single value.
    Similar to array_reduce (see: https://secure.php.net/manual/en/function.array-reduce.php), except that the first value in
    the input array is used as the initial state of the accumulator, and the walk through the input array starts with the second
    value.

    Example usage:
        $a = array (1, 2, 3);
        $result = ArrayUtils::reduce_2 ($a, 'reduce');
        // $result === 6

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to reduce. If the array is empty, @return is null. If the array contains only one value, @return is that
    value.
    @f - The reducer function. Signature:
        mixed @acc - The accumulator state.
        mixed @value - The current array value.
        mixed @return - The updated accumulator state.
    @return - The result of reducing @a.
    */
    public static function reduce_2 (array $a, callable $f) /* : mixed */ {
        if (empty($a)) {
            return null;
        }
        else if (1 === count($a)) {
            return $a[0];
        }
        else {
            /* We use array_slice instead of array_shift so as not to modify the original array. */
            return array_reduce (array_slice ($a, 1), $f, $a[0]);
        }
    }

    /* ArrayUtils::sort_by
    Sorts the input array according to a key selector function.

    Example usage:
        $a = array ('abc', 'ab', 'a');
        $result = ArrayUtils::sort_by ($a, function (string $value) : int {
            return strlen ($value);
        });
        // $result === array ('a', 'ab', 'abc');

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The array to sort. If the array is empty, @return is an empty array.
    @f - The key selector function. Signature:
        mixed @value - The current array value.
        mixed @return - The key derived from @value.
    @return - The result of sorting @a.
    */
    public static function sort_by (array $a, callable $f) : array {
        /* ksort takes the array by reference and modifies it in place, so we can't pass it the result of group_by directly. */
        $groups = ArrayUtils::group_by ($a, $f);
        ksort ($groups);
        return ArrayUtils::flatten ($groups);
    }

    /* ArrayUtils::tail
    Returns a new array that contains all items in the input array except the first item.
    
    Example usage:
        $a = array (1, 2, 3);
        $result = ArrayUtils::tail ($a);
        // $result === new array (2, 3)

    Remarks
    This function does not modify the input array.
    This function does not recurse into nested arrays.
    
    @a - The input array.
    @return - The tail of the input array.
    */
    public static function tail (array $a) : array {
        return array_slice ($a, 1);
    }

    /* ArrayUtils::try_find
    Finds the first item in the input array that satisfies the given predicate. If such an item is found, returns a Maybe that
    contains the key and value of the item. If no such item is found, returns an empty Maybe.
    
    Example usage:
        $a = array ('a' => 1, 'b' => 2, 'c' => 3);
        $f = function ($key, $value) { return ($key === 'a' && $value === 1); };
        $result = ArrayUtils::try_find ($a, $f);
        $item = $result->value;
        // $item->key === 'a'
        // $item->value === 1
        
    Remarks
    This function does not modify the input array.
    This function does not recurse into nested arrays.
        
    @a - The input array.
    @f - The predicate. Signature:
        mixed @key - The current array key.
        mixed @value - The current array value.
        bool @return - True if the value satisfies the predicate; otherwise, false.
    @return - A Maybe.
        If an item in @a satisfies predicate @f:
            @is_some - true.
            @value - An object.
                @key - The key of the item.
                @value - The value of the item.
        If no item in @a satisfies predicate @f:
            @is_some - false.
            @value - null.
    */
    public static function try_find (callable $f, array $a) : Maybe {
        $result = new Maybe (false, null);
        if (empty ($a)) {
            return $result;
        }
        foreach ($a as $key => $value) {
            if (true === $f ($key, $value)) {
                $result = new Maybe (true, (object) ['key' => $key, 'value' => $value]);
                break;
            }
        }
        return $result;
    }
    
    /* ArrayUtils::uncombine
    Transforms the input array to an array of key-value pairs based on the keys and values of the input array.

    Example usage:
        $result = ArrayUtils::uncombine (array ('a' => 1, 'b' => 2));
        // $result === array (
        //     (object) array ('key' => 'a', 'value' => 1),
        //     (object) array ('key' => 'b', 'value' => 2))

    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    This function is useful in combination with higher-order functions such as array_reduce that only take one array and do not
    provide access to the keys of the array.

    For example, if you wanted to reduce an array using both its keys and values, you would write:
        array_reduce (ArrayUtils::uncombine ($a), function ($acc, StdClass $kv) {
            $key = $kv->key;
            $value = $kv->value;
            // ...
        }, $initial);

    @a - The input array. If the array is empty, @return is an empty array.
    @return - An array of key-value pairs.
    */
    public static function uncombine (array $a) {
        return array_map (function ($fst, $snd) {
            return (object) array ('key' => $fst, 'value' => $snd);
        }, array_keys ($a), array_values ($a));
    }

    /* ArrayUtils::windowed
    Transforms the input array into a 2D array where each index contains an overlapping window of array items taken from the
    input array.
    This is useful for processing arrays where you need to evaluate each item in relation to other items before or after it.

    Example usage:
        1. Window size 1.
        $a = array (0, 1, 2);
        $result = ArrayUtils::windowed ($a, 1);
        // $result === array (array (0), array (1), array (2));

        2. Window size 2.
        $a = array (0, 1, 2);
        $result = ArrayUtils::windowed ($a, 2);
        // $result === array (array (0, 1), array (1, 2));

        3. Window size 3.
        $a = array (0, 1, 2);
        $result = ArrayUtils::windowed ($a, 3);
        // $result === array (array (0, 1, 2));
        
    Remarks:
    This function does not modify the input array.
    This function does not recurse into nested arrays.

    @a - The input array.
    @size - The window size.
        If @size is less than or equal to 0, @return is an empty array.
        If @size is greater than the count of @a, @return is an empty array.
    @return - A 2D array where each index contains a window of size @size taken from @a.
    */
    public static function windowed (array $a, int $size) : array {
        if ($size <= 0 || $size > count ($a)) {
            return array ();
        }
        else {
            $acc = (object) ['queue' => array (), 'result' => array ()];
            $acc = array_reduce ($a, function (StdClass $acc, /* mixed */ $item) use ($size) {
/* Add the current item to the current window. */
                $acc->queue = ArrayUtils::append ($acc->queue, null, $item);
/* If the current window is full... */
                if (count ($acc->queue) === $size) {
/* Add the current window to the accumulator. */
                    $acc->result = ArrayUtils::append ($acc->result, null, $acc->queue);
/* Drop the earliest item from the current window. */
                    $acc->queue = array_slice ($acc->queue, 1);
                }
                return $acc;
            }, $acc);
            return $acc->result;
        }
    }

    /* ArrayUtils::zip
    Combines two input arrays into a single 2D array. This function is different from array_combine, which combines two input
    arrays into a single 1D array whose keys are the values of the first input array and whose values are the values of the
    second input array.

    Example usage:
        $a = array ('a', 'b');
        $b = array (1, 2, 3);
        $result = ArrayUtils::zip ($a, $b);
        // $result === array (array ('a', 1), array ('b', 2), array (null, 3))

    Remarks:
    This function does not modify the input arrays.
    This function does not recurse into nested arrays.

    This function uses array_map. This means that if one input array is longer than the other, the shorter input array is
    extended with elements to match the length of the longer input array. See:
    https://secure.php.net/manual/en/function.array-map.php

    @a1 - The first array to zip.
    @a2 - The second array to zip.
    @return - A 2D array where each index contains the values for the corresponding indices in @a1 and @a2.
    */
    public static function zip (array $a1, array $a2) : array {
        return array_map (function ($fst, $snd) {
            return array ($fst, $snd);
        }, $a1, $a2);
    }
}

?>