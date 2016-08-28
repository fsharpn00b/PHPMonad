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

require_once 'StateMonadCesaroCommon.php';
require_once '../Monads/StateMonad.php';

/* repeat
Call a stateful function repeatedly, carrying the state from one call to the next and collecting the function results.

Example usage:
TODO2

Remarks:
repeat () is adapted from Haskell's replicateM () function. See:
https://hackage.haskell.org/package/base-4.9.0.0/docs/Control-Monad.html#v:replicateM
https://hackage.haskell.org/package/base-4.9.0.0/docs/src/Control.Monad.html#replicateM

A stateful function has the following signature.
initial state -> updated state * function result
repeat () would itself be a stateful function except for the @f, @n, and @results parameters.

@f - A stateful function.
    @1 - The initial state.
    @return - A tuple.
        @state - The updated state.
        @value - The function result.
@n - The number of times to repeat @f.
@results - The collected results of @f.
@state - The initial state.
@return - A tuple.
    @state - The updated state.
    @value - The function result.
*/
function repeat (callable $f, int $n, array $results, /* mixed */ $state) : State {
    $m = new StateMonad ();
    $code =
        'if ($n <= 0) {' .
            /* Return the final state and the final function result. */
        '    unit ($results);
        }
        else {' .
            /* Call the stateful function and get the result. This also updates the state. */
            'bind (\'result\', $f);
            $results = ArrayUtils::append ($results, null, $result);' .
            /* Apply the recursive call to the updated state. */
        '    unit2 (partially_apply (\'repeat\', $f, $n - 1, $results));
        }
    ';
    return ($m->monad_eval ($code, get_defined_vars ())) ($state);
}

/* monte_carlo
Run an experiment and return the ratio of successful trials to total trials.

Example usage:
TODO2

Remarks:
None.

@trials - The number of trials to run.
@experiment - A stateful function.
    @1 - The initial state.
    @return - A tuple.
        @state - The updated state.
        @value - The function result.
@return - The ratio of successful trials to total trials.
*/
function monte_carlo (int $trials, callable $experiment) : float {
    $result = repeat ($experiment, $trials, array (), initial_rand_seed);
    /* Discard the final state. */
    $result = $result->value;
    /* Discard failed trials. */
    $result = array_filter ($result);
    $passed = count ($result);
    return $passed / $trials;
}

/* estimate_pi
Returns an estimate of pi using Cesaro's method.

Example usage:
$result = estimate_pi (100);
// $result === 3.3968311024

Remarks:
None.

@trials - The number of trials to run.
@return - The estimate of pi.
*/
function estimate_pi (int $trials) : float {
    return sqrt (6 / (monte_carlo ($trials, 'cesaro')));
}

$i = 0;
while ($i < 5) {
    echo sprintf ("%.10f\n", estimate_pi (100));
    $i++;
}

?>