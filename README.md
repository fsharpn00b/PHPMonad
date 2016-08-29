# PHPMonad
Adds F#-style computation expressions to PHP.

PHPMonad implements the following monads:
- Maybe
- Array
- State
- Pause (also known as the Interrupt or Coroutine monad)
- Sequence

For examples of using these monads, see the Samples folder, as well as the code comments in the Monads/*Monad.php files.

PHPMonad also lets you implement new monads, and supports the following methods:
- bind (let! in F#)
- monad_do (do! in F#)
- unit (return in F#)
- unit2 (return! in F#)
- zero
- combine
- delay
- run

PHPMonad does not yet support the following methods:
- yield
- yield!
- for
- while
- using
- try/with
- try/finally

<h3>Known Issues</h3>
- You cannot use control flow constructs in monadic code except the following: if, else if, else.
- You cannot define a function, either named or anonymous, inside monadic code. However, named functions that are visible in the
scope where the monadic code is evaluated are also visible to the monadic code. Also, you can assign an anonymous function to a
variable and then make the variable visible to the monadic code by passing the result of get_defined_vars () to
Monad->monad_eval ().

<h3>Thank You</h3>
I wrote PHPMonad using what I learned from the following people. PHPMonad could not exist without them.
- Scott Wlaschin (https://fsharpforfunandprofit.com/series/computation-expressions.html)
- Bartosz Milewski (https://bartoszmilewski.com/2011/01/09/monads-for-the-curious-programmer-part-1/)
- Giulia Costantini and Giuseppe Maggiore (https://www.amazon.com/Friendly-Fun-game-programming-Book-ebook/dp/B005HHYIWC)
- Tomas Petricek (http://tryjoinads.org/docs/computations/layered.html)
