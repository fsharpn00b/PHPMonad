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
require_once 'Monad.php';

/* A Maybe monad, which can be used to compose functions that return the Maybe type.

Example usage:
    function div_by (float $a, float $b) : Maybe {
        if (0 === $b) {
            return new Maybe (false, null);
        }
        else {
            return new Maybe (true, $a / $b);
        }
    }

    $m = new MaybeMonad ();
    $result = $m->monad_eval ('
        bind (\'x\', div_by (10.0, 5.0));
        bind (\'y\', div_by (20.0, $x));
        bind (\'z\', div_by (10.0, $y));
        unit ($z);
    ');
    if (true === $result->is_some) {
        $z = $result->value;
    }
    else {
        return;
    }
    // $z === 1

Note that without the Maybe monad, the code above would look like the following.
    $result = div_by (10.0, 5.0);
    if (true === $result->is_some) {
        $x = $result->value;
    }
    else {
        return;
    }

    $result = div_by (20.0, $x);
    if (true === $result->is_some) {
        $y = $result->value;
    }
    else {
        return;
    }

    $result = div_by (10.0, $y);
    if (true === $result->is_some) {
        $z = $result->value;
    }
    else {
        return;
    }
    // $z === 1
*/
class MaybeMonad extends Monad {

    /* MaybeMonad->unit
    Promotes a value to the Maybe type.

    Example usage:
        $m = new MaybeMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        // $result->is_some === true
        // $result->value === 1

    Remarks:
    None.
    
    @value - The value to promote to the Maybe type.
    @return - The Maybe type value.
    */
    public function unit (/* mixed */ $value) : Maybe {
        return new Maybe (true, $value);
    }

    /* MaybeMonad->bind
    Binds the contents of a Maybe value for the rest of the monadic code. If the value's is_some field is set to false, this
    function returns the Maybe value.

    Example usage:
        $m = new MaybeMonad ();
        $code = '
            bind (\'x\', new Maybe (true, 1));
            $x += 1;
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        // $result->is_some === true
        // $result->value === 2

    Remarks:
    None.

    @result - The Maybe value whose contents should be bound.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - If @result->value is true, @return is the result of running rest of the monadic code. If @result->value is false,
    @return is @result.
    */
    /* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    public function bind (/* Maybe */ $result, callable $rest) : Maybe {
        if (true === $result->is_some) {
            return $rest ($result->value);
        }
        else {
            return $result;
        }
    }

    /* MaybeMonad->monad_do
    Processes a Maybe value. If the value's is_some field is set to true, this function runs the rest of the monadic code. If the
    value's is_some field is set to false, this function returns the Maybe value.
    
    Example usage:
        function run () {
            return new Maybe (true, null);
        }
        function stop () {
            return new Maybe (false, null);
        }
        
        $m = new MaybeMonad ();
        $code = '
            monad_do (run ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        // $result->is_some === true
        // $result->value === 1

        $code = '
            monad_do (stop ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        // $result->is_some === false
        // $result->value === null
        
    Remarks:
    None.

    @result - The input Maybe value.
    @rest - A function that represents the remaining monadic code.
        @return - The result of running the rest of the monadic code.
    @return - If @result->value is true, @return is the result of running the rest of the monadic code. If @result->value is
    false, @return is @result.
    */
    public function monad_do (/* Maybe */ $result, callable $rest) : Maybe {
        if (true === $result->is_some) {
            return $rest ();
        }
        else {
            return $result;
        }
    }

    /* MaybeMonad->zero
    Returns an empty Maybe value.

    Example usage:
        $m = new MaybeMonad ();
        $code = '
            bind (\'x\', new Maybe (true, 1));
        ';
        $result = $m->monad_eval ($code);
        // $result->is_some === false
        // $result->value === null
    
    Remarks:
    This method is called when the last statement in the the monadic code does not call unit () or unit2 ().

    @return - An empty Maybe value.
    */
    public function zero () : Maybe {
        return new Maybe (false, null);
    }
    
    /* MaybeMonad->combine
    Returns the result of combining two Maybe values.

    Example usage:
        $m = new MaybeMonad ();
        $code = '
            unit (1);
            unit2 (new Maybe (true, 2));
        ';
        $result = $m->monad_eval ($code);
        // $result->is_some === true
        // $result->value === 3

    Remarks:
    This method is called when the monadic code calls unit () more than once, calls unit2 () more than once, or calls unit () and
    unit2 () at least once each.
    For more information, see the comments for Monad->combine ().
    
    @value1 - The first Maybe value to combine.
    @value2 - The second Maybe value to combine.
    @return - The combined Maybe value.
    */
    public function combine (/* Maybe */ $value1, /* Maybe */ $value2) : Maybe {
        if (true === $value1->is_some && false === $value2->is_some) {
            return $value1;
        }
        else if (false === $value1->is_some && true === $value2->is_some) {
            return $value2;
        }
        else if (true === $value1->is_some && true === $value2->is_some) {
// TODO2 This breaks for values that can't be added.
            return new Maybe (true, $value1->value + $value2->value);
        }
        else {
            return new Maybe (false, null);
        }
    }

    /* We implement Monad->combine (), so we must implement Monad->delay (). */
    /* MaybeMonad->delay
    Removes the delay from a delayed Maybe value.
    
    Example usage:
    This function is for internal use only.
    
    Remarks:
    None.
    
    @f - The delayed Maybe value.
    @return - The Maybe value.
    */
    public function delay (callable $f) : Maybe {
        return $f ();
    }
    
    /* We do not implement Monad->delay () or Monad->run (). */
    
    public function __construct () {
        parent::__construct ('Maybe');
    }
}

?>