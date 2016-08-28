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

require_once 'Monad.php';

/* An array monad, which can be used to compose functions that return the array type.

Example usage:
    $m = new ArrayMonad ();
    $result = $m->monad_eval ('
        bind (\'x\', array (1, 2, 3));
        bind (\'y\', array (4, 5, 6));
        unit ($x * $y);
    ');
    // $result === array (4, 5, 6, 8, 10, 12, 12, 15, 18)
*/
class ArrayMonad extends Monad {

    /* ArrayMonad->unit
    Promotes a value to the array type.

    Example usage:
        $m = new ArrayMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        // $result === array (1)

    Remarks:
    None.
    
    @value - The value to promote to the array type.
    @return - The array type value.
    */
    public function unit (/* mixed */ $value) : array {
        return array ($value);
    }

    /* ArrayMonad->bind
    Binds the contents of an array for the rest of the monadic code. If the array is empty, this function returns the empty
    array.

    Example usage:
        $m = new ArrayMonad ();
        $code = '
            bind (\'x\', array (1, 2, 3));
            $x += 1;
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        // $result === array (2, 3, 4);

    Remarks:
    None.

    @result - The array whose contents should be bound.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - If @result is not an empty array, @return is the result of running rest of the monadic code. If @result is an empty
    array, @return is @result.
    */
    /* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    public function bind (/* array */ $result, callable $rest) : array {
        return ArrayUtils::flatten (array_map ($rest, $result));
    }

    /* ArrayMonad->monad_do
    Processes an array. If the array is not empty, this function runs the rest of the monadic code. If the array is empty, this
    function returns the empty array.
    
    Example usage:    
        $m = new ArrayMonad ();
        $code = '
            monad_do (array (1, 2, 3));
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        // $result === array (1, 1, 1)

        $code = '
            monad_do (array ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        // $result === array ()
        
    Remarks:
    None.

    @result - The input array.
    @rest - A function that represents the remaining monadic code.
        @return - The result of running the rest of the monadic code.
    @return - If @result is not empty, @return is the result of running the rest of the monadic code. If @result is empty,
    @return is @result.
    */
    public function monad_do (/* array */ $result, callable $rest) : array {
        /* The $rest function passed to monad_do takes no parameters, so the contents of $result are ignored. */
        return ArrayUtils::flatten (array_map ($rest, $result));
    }

    /* ArrayMonad->zero
    Returns an empty array.

    Example usage:
        $m = new ArrayMonad ();
        $code = '
            bind (\'x\', array (1));
        ';
        $result = $m->monad_eval ($code);
        // $result === array ()
    
    Remarks:
    This method is called when the last statement in the the monadic code does not call unit () or unit2 ().

    @return - An empty array.
    */
    public function zero () : array {
        return array ();
    }
    
    /* ArrayMonad->combine
    Returns the result of combining two arrays.

    Example usage:
        $m = new ArrayMonad ();
        $code = '
            unit (1);
            unit2 (array (2));
        ';
        $result = $m->monad_eval ($code);
        // $result === array (1, 2)

    Remarks:
    This method is called when the monadic code calls unit () more than once, calls unit2 () more than once, or calls unit () and
    unit2 () at least once each.
    For more information, see the comments for Monad->combine ().
    
    @value1 - The first array to combine.
    @value2 - The second array to combine.
    @return - The combined array.
    */
    public function combine (/* array */ $value1, /* array */ $value2) : array {
        return array_merge ($value1, $value2);
    }

    /* We implement Monad->combine (), so we must implement Monad->delay (). */
    /* ArrayMonad->delay
    Removes the delay from a delayed array.
    
    Example usage:
    This function is for internal use only.
    
    Remarks:
    None.
    
    @f - The delayed array.
    @return - The array.
    */
    public function delay (callable $f) : array {
        return $f ();
    }
    
    /* We do not implement Monad->delay () or Monad->run (). */
    
    public function __construct () {
        parent::__construct ('array');
    }
}

?>