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

/* The result of running one or more stateful functions. */
class State {
    /* The final state. */
    public /* mixed */ $state;
    /* The function result. */
    public /* mixed */ $value;
    public function __construct (/* mixed */ $state, /* mixed */ $value) {
        $this->state = $state;
        $this->value = $value;
    }
}

/* get_state
Returns a stateful function that takes the current state and returns it as both the state and the result. This is used to get the
current state while running a series of stateful functions.

Example usage:
    $m = new StateMonad ();
    $code = '
        bind (\'x\', get_state ());
        $x += 1;
        do (set_state ($x));
        $x += 1;
        unit ($x);
    ';
    $f = $m->monad_eval ($code);
    $state = 1;
    $result = $f ($state);
    // $result->state === 2
    // $result->value === 3

Remarks:
A stateful function has the following signature.
initial state -> updated state * function result

@return - A stateful function.
    @state - The current state.
    @return - A State.
        @state - The current state.
        @value - The current state.
*/
function get_state () : callable {
    return function ($state) : State {
        return new State ($state, $state);
    };
}

/* set_state
Returns a stateful function that takes the current state and returns a new state. This is used to update the state while running
a series of stateful functions.

Example usage:
    $m = new StateMonad ();
    $code = '
        bind (\'x\', get_state ());
        $x += 1;
        do (set_state ($x));
        $x += 1;
        unit ($x);
    ';
    $f = $m->monad_eval ($code);
    $state = 1;
    $result = $f ($state);
    // $result->state === 2
    // $result->value === 3

Remarks:
A stateful function has the following signature.
initial state -> updated state * function result

@state - The new state.
@return - A stateful function.
    @state - The current state.
    @return - A State.
        @state - The new state.
        @value - null.
*/
function set_state ($state) : callable {
    return function () use ($state) : State {
        return new State ($state, null);
    };
}

/* A State monad, which can be used to compose stateful functions.

Example usage:
See ../Samples/StateMonadCesaro.php.
*/
class StateMonad extends Monad {
    
    /* StateMonad->unit
    Promotes a value to a stateful function.

    Example usage:
        $m = new StateMonad ();
        $code = 'unit (1);';
        $f = $m->monad_eval ($code);
        $state = 2;
        $result = $f ($state);
        // $result->state === 2
        // $result->value === 1

    Remarks:
    None.
    
    @value - The value to promote to the stateful function.
    @return - The stateful function.
    */
    public function unit (/* mixed */ $value) : callable {
        return function (/* mixed */ $state) use ($value) : State {
            return new State ($state, $value);
        };
    }

    /* StateMonad->bind
    Binds the result of a stateful function for the rest of the monadic code.

    Example usage:
        $m = new StateMonad ();
        $code = '
            bind (\'x\', get_state ());
            $x += 1;
            unit ($x);
        ';
        $f = $m->monad_eval ($code);
        $state = 1;
        $result = $f ($state);
        // $result->state === 1
        // $result->value === 2

    Remarks:
    None.

    @result - The stateful function whose result should be bound.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    /* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    public function bind (/* callable */ $result, callable $rest) : callable {
        return function (/* mixed */ $state) use ($result, $rest) : State {
            $new_state = $result ($state);
            $stateful_f = $rest ($new_state->value);
            return $stateful_f ($new_state->state);
        };
    }

    /* StateMonad->monad_do
    Runs a stateful function.

    Example usage:
        $m = new StateMonad ();
        $code = '
            monad_do (set_state (2));
            unit (1);
        ';
        $f = $m->monad_eval ($code);
        $state = 1;
        $result = $f ($state);
        // $result->state === 2
        // $result->value === 1

    Remarks:
    None.

    @result - The stateful function to run.
    @rest - A function that represents the remaining monadic code.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    public function monad_do (/* callable */ $result, callable $rest) : callable {
        return function (/* mixed */ $state) use ($result, $rest) : State {
            $new_state = $result ($state);
            $stateful_f = $rest ();
            return $stateful_f ($new_state->state);
        };
    }

/* We do not implement Monad->zero (), Monad->combine (), Monad->delay (), or Monad->run (). */

    public function __construct () {
        parent::__construct ('callable');
    }
}

?>