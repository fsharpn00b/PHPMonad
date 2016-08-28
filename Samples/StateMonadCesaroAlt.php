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
require_once '../Monads/Monad.php';
require_once '../Monads/MaybeMonad.php';
require_once '../Monads/StateMonad.php';

/* This file contains an alternate implementation of Haskell's replicateM () function that is more intuitive and less tied to a
particular monad (namely the State monad) than the version in StateMonadCesaro.php.

See:
https://stackoverflow.com/questions/21369028/own-replicatem-implementation
*/

/* sequence
Combines an array of monadic type values into a single monadic type value.

Example usage:
TODO2

Remarks:
None.

@m - The monad related to the monadic type of the contents of @l.
@l - The monadic type values to combine into a single monadic type value.
@return - The combined monadic type value.
*/
function sequence (Monad $m, array $l) /* : monadic type */ {
    return array_reduce ($l, function (/* monadic type */ $acc, /* monadic type */ $item) use ($m) /* : monadic type */ {
        return $m->monad_eval (
            /* We want to transform the following:
            M<'a>, M<'a array>
            into the following:
            M<'a : 'a array>
            where M is the monadic type, 'a is the inner type, and : means "prepend".
            Extract the current item and the accumulator from their monadic types. */
            'bind (\'item_\', $item);
            bind (\'acc_\', $acc);' .
            /* Prepend the current item to the accumulator and then promote the result to the monadic type.
            For each iteration, $acc contains either (1) the initial value or (2) the partially evaluated monadic code for the
            previous iteration of the function passed to array_reduce (). As a result, all the bind statements from all
            iterations are evaluated first, then all the unit statements from all iterations. So the first time the unit
            statement is evaluated, it is evaluated with the "innermost" result of binding $item. So we add the result to $acc
            with prepend () instead of append ().

            For example, the following code:
                $stateful_f = replicateM (new StateMonad (), 3, function ($state) { return new State ($state + 1, $state + 2); });
                $result = $stateful_f (1);
            is evaluated as follows:
                ($state = 1)
                bind $item
                ($state = 2, $value = 3)
                bind $acc where $acc =
                    bind $item
                    ($state = 3, $value = 4)
                    bind $acc where $acc =
                        bind $item
                        ($state = 4, $value = 5)
                        bind $acc where $acc = array ()
                        unit ($value : $acc)
                        ($state = 4, $acc = array (5))
                    unit ($value : acc)
                    // Note the value of $value depends on the scope, whereas the value of $state is threaded through all scopes.
                    ($state = 4, $acc = array (4, 5))
                unit ($value : acc)
                ($state = 4, $acc = array (3, 4, 5))
            */
            'unit (ArrayUtils::prepend ($acc_, null, $item_));'
        , get_defined_vars ());
    }, $m->unit (array ()));
}

/* replicate
Return an array that contains the specified value repeated the specified number of times.

Example usage:
$result = replicate (3, true);
// $result === array (true, true, true);

Remarks:
None.

@n - The number of times to repeat @value.
@value - The value with which to populate the array.
@return - An array that contains @value repeated @n times.
*/
function replicate (int $n, /* mixed */ $value) : array {
    return array_fill (0, $n, $value);
}

/* replicateM
Return an array that contains the inner value of the specified monadic type value, repeated the specified number of times.

Example usage:
$result = replicateM (new MaybeMonad (), 3, new Maybe (true, 1));
// We use == rather than === because ===, when applied to objects, tests for identity.
// $result == new Maybe (true, array (1, 1, 1))

$stateful_f = replicateM (new StateMonad (), 3, function ($state) { return new State ($state + 1, $state + 2); });
$result = $stateful_f (1);
// We use == rather than === because ===, when applied to objects, tests for identity.
// $result == new State (4, array (3, 4, 5))

Remarks:
replicateM transforms the following:
M<'a>
into the following:
M<'a array>
where M is the monadic type and 'a is the inner type.

@m - The monad related to the monadic type of @value.
@n - The number of times to repeat the inner value of @value.
@value - The monadic type value whose inner value is used to populate the array.
@return - An array the contains the inner value of @value repeated @n times.
*/
function replicateM (Monad $m, int $n, /* monadic type */ $value) /* : monadic type */ {
    return sequence ($m, replicate ($n, $value));
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
    $result = replicateM (new StateMonad (), $trials, $experiment);
    /* Apply the stateful function to the initial state. */
    $result = $result (initial_rand_seed);
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
// $result === 3.3646329246

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