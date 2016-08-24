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

/* The result of an action that might succeed or fail. Failure is not necessarily an error. */
class Maybe {
    public /* bool */ $is_some;
    public /* mixed */ $value;

    public function __construct (bool $is_some, /* mixed */ $value) {
        $this->is_some = $is_some;
        $this->value = $value;
    }
}

/* partially_apply
Partially applies the function to the parameters and returns a new function that accepts the remaining parameters.

Example usage:
    $f = function (int $a, int $b, int $c) : int {
        return $a + $b + $c;
    };
    $f_ = partially_apply ($f, 1);
    $f__ = partially_apply ($f_, 2);
    echo $f__ (3);
    // Output: 6

Remarks:
    Source:
    https://gist.github.com/jdp/2201912

@1 - The function to partially apply.
@2..@n - The parameters to apply the function to.
@return - A new function that accepts the remaining parameters to @1.
*/
function partially_apply () : callable {
    /* Get the parameters passed to this function. Note the first one is the function to apply. */
    $applied_args = func_get_args ();
    return function () use ($applied_args) /* : mixed */ {
        /* Append the parameters passed to this function to those previously passed to partially_apply (). */
        $params = array_merge ($applied_args, func_get_args ());
        /* Apply the function to the parameters. */
        return call_user_func_array ($params[0], array_slice ($params, 1));
    };
}

/* compose
Applies the first function to the input. Each remaining function is then applied to the result of the previous function.

Example usage:
    $f = function (int $x) : int {
        return $x + 1;
    };
    echo compose (1, array ($f, $f, $f));
    // Output: 4
    
Remarks:
    Each function in @funcs must take one parameter. The parameter type must be the same as the return type.
    
@input - The input.
@funcs - The functions to compose.
@return - The result of the last function in @funcs.
*/
function compose (/* mixed */ $input, array $funcs) {
    return array_reduce ($funcs, function ($acc, $item) {
        return $item ($acc);
    }, $input);
}

/* is_obj_type
Returns true if the specified value is an object of the specified type; otherwise, returns false.

Example usage:
    $result = new NoResult ();
    $is_no_result = is_obj_type ($result, 'NoResult');
    // $is_no_result === true

Remarks:
If @value is not an object, this function returns false.

@value - The value.
@type - The type.
@return - True if @value is of type @type; otherwise, false.
*/
function is_obj_type (/* mixed */ $value, string $type) : bool {
    return (
        (true === is_object ($value) && 0 === strcmp (get_class ($value), $type)) ||
        (0 === strcmp ($type, gettype ($value))) ||
        (true === is_array ($value) && 0 === strcmp ($type, 'array')) ||
        (true === is_callable ($value) && 0 === strcmp ($type, 'callable'))
        );
}

/* get_type
Returns the type name of a simple type or object.

Example usage:
    $x = 3;
    $type = get_type ($x);
    // $type === 'integer'
    $type = get_type (array ());
    // $type === 'array'
    $type = get_type (function () {});
    // $type === 'Closure'
    
Remarks:
None.

@value - The value.
@return - The type name of @value.
*/
function get_type (/* mixed */ $value) : string {
    return is_object ($value) ? get_class ($value) : gettype ($value);
}
    
/* starts_with
Returns true if the specified string starts with the other specified string. Comparison is case-sensitive.

Example usage:
    $result = starts_with ('abcde', 'abc');
    // $result === true
    
    $result = starts_with ('abcde', 'ABC');
    // $result === false
    
    $result = starts_with ('abc', 'abcde');
    // $result === false

Remarks:
    If @needle is longer than @haystack, @return is false.

@haystack - The string to search in.
@needle - The string to search for.
@return - True if @haystack starts with @needle; otherwise, false.
*/
function starts_with (string $haystack, string $needle) : bool {
     $length = strlen ($needle);
     return (0 === strcmp (substr ($haystack, 0, $length), $needle));
}
?>