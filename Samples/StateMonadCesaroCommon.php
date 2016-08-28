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

/* This is an implementation of the Cesaro method for estimating pi, based on the implementation provided in Structure and
Interpretation of Computer Programs (SICP) by Abelson and Sussman.

Abelson and Sussman use their Cesaro method implementation to show that we sometimes need mutation to separate management of
state (in this case, a random number seed) from the rest of a program. However, they were reckoning without the State monad,
which lets us handle state management abstractly.

This file contains the functions used by both StateMonadCesaro.php and StateMonadCesaroAlt.php. Those files provide alternate
implementations of Haskell's replicateM () function.

See:
SICP (text).
https://mitpress.mit.edu/sicp/full-text/book/book-Z-H-20.html#%_sec_3.1.2
SICP (video). See 1:10:40 for a listing of the Cesaro program without mutation or the State monad.
https://www.youtube.com/watch?v=jl8EHP1WrWY&list=PLB63C06FAF154F047&index=9
Haskell implementation that uses the State monad instead of mutation.
https://pbrisbin.com/posts/random_numbers_without_mutation/
*/

/* Note this statement must be first. */
declare (strict_types = 1);

require_once '../Utils/Common.php';
require_once '../Monads/StateMonad.php';

const initial_rand_seed = 0;

/* get_rand
Generates a pseudo-random number based on a seed.

Example usage:
$result = get_rand (0);
// $result === 963932192

Remarks:
get_rand () simulates a pure pseudo-random number generator such as you might find in a functional language.
A pure function (1) has no side effects such as mutation, input or output, and (2) is referentially transparent; that is, when
applied to a given value, it always returns the same result.
A pure function cannot generate a true random number. To do so, (1) it would require input, which is a side effect, and (2) it
would not return the same result every time, so it would not be referentially transparent.

mt_rand () seems to use an internal mutable random number seed, which means it is not pure. You can also set this internal random
number seed by calling mt_srand (). mt_rand () does seem to be referentially transparent, as it seems to always return the same
value when mt_srand () is applied to a given random number seed.

The manual page description for mt_srand () says:
"Seeds the random number generator with seed or with a random value if no seed is given."
Assuming mt_srand () generates the "random" seed value with the same algorithm used by mt_rand (), this is only adding a layer of
pseudo-randomness.

Our implementation of the Cesaro method starts with a random number seed of 0. Thereafter, it uses the previously generated
random number as the seed for the next.

For more information see:
https://programmers.stackexchange.com/questions/202908/how-do-functional-languages-handle-random-numbers
https://secure.php.net/manual/en/function.mt-srand.php

This is a stateful function. A stateful function has the following signature.
initial state -> updated state * function result

@rand_seed - The random number seed.
@return - A tuple.
    @state - The updated random number seed.
    @value - A pseudo-random number.
*/
function get_rand (int $seed) : State {
    mt_srand ($seed);
    $result = mt_rand ();
    return new State ($result, $result);
}

/* gcd
Returns the greatest common denominator of two numbers.

Example usage:
$result = gcd (2, 4);
// $result === 2

Remarks:
None.

@x - The first number.
@y - The second number.
@return - The greated common denominator of @x and @y.
*/
function gcd (int $x, int $y) : int {
    if (0 === $y) {
        return $x;
    }
    else {
        return gcd ($y, ($x % $y));
    }
}

/* cesaro
Returns true if two pseudo-random numbers are both prime numbers.

Example usage:
$result = cesaro (0);
// Starting with random number seed 0, the first two pseudo-random numbers returned by make_rand are 963932192, 1631776918,
// neither of which is a prime number.
// $result === false

Remarks:
This is a stateful function, as it has the following signature. 
initial state -> updated state * function result

@rand_seed - A random number seed.
@return - A tuple.
    @state - The updated random seed.
    @value - True if both pseudo-random numbers are prime numbers.
*/
function cesaro (int $rand_seed) : State {
    $result1 = get_rand ($rand_seed);
    $result2 = get_rand ($result1->state);
    return new State ($result2->state, (1 === gcd ($result1->value, $result2->value)));
}

?>