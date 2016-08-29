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

Known Issues
- You cannot use control flow constructs in monadic code except for if, else if, and else.
- You cannot define a function, either named or anonymous, inside monadic code. However, named functions that are visible in the
scope where the monadic code is evaluated are also visible to the monadic code. Also, you can assign an anonymous function to a
variable and then make the variable visible to the monadic code by passing the result of get_defined_vars () to
Monad->monad_eval ().
