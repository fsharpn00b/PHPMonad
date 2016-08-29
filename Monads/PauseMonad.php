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

/* Either a step in a coroutine, or the result of the coroutine. */
abstract class PauseStep {}
/* The result of a coroutine. */ 
class PauseStepContinue extends PauseStep {
    public /* mixed */ $result;
    public function __construct (/* mixed */ $result) {
        $this->result = $result;
    }
}
/* A step in a coroutine. */
class PauseStepPaused extends PauseStep {
    public /* callable : unit -> PauseStep */ $process;
    public function __construct (callable /* : unit -> PauseStep */ $process) {
        $this->process = $process;
    }
}

/* yield_
Returns a coroutine that is used to pause another coroutine.

Example usage:
    $m = new PauseMonad ();
    $code = '
        monad_do (yield_ (null));
        unit (1);
    ';
    $result = $m->monad_eval ($code);
    $result_ = $result ();
    $result__ = ($result_->process) ();
    $result___ = $result__->result;
    // $result___ === 1

Remarks:
None.

@x - The result of the coroutine.
@return - The coroutine.
*/
function yield_ ($x) : callable {
    return function () use ($x) {
        return new PauseStepPaused (function () use ($x) {
            return new PauseStepContinue ($x);
        });
    };
}

/* A Pause monad, which can be used to create a coroutine.

Example usage:
See ../Samples/Coroutine.php.
*/
class PauseMonad extends Monad {
    
    /* PauseMonad->unit
    Creates a coroutine from a value.
    
    Example usage:
        $m = new PauseMonad ();
        $code = '
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = $result_->result;
        // $result__ === 1
    
    Remarks:
    None.
    
    @value - The input value.
    @return - The coroutine.
    */
    /* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    public function unit (/* mixed */ $value) : callable /* : unit -> PauseStep */ {
//        echo sprintf ("Evaluating item: %d\n", $value);
        return function () use ($value) { return new PauseStepContinue ($value); };
    }

    /* PauseMonad->bind
    Binds the result of a coroutine for the rest of the monadic code.
    
    Example usage:
        $m = new PauseMonad ();
        $code = '
            bind (\'x\', yield_ (1));
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = ($result_->process) ();
        $result___ = $result__->result;
        // $result___ === 1
    
    Remarks:
    None.
    
    @result - The coroutine whose result should be bound.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    public function bind (/* callable : unit -> PauseStep */ $result, callable /* : mixed -> callable : unit -> PauseStep */ $rest) : callable /* : unit -> PauseStep */ {
        return function () use ($result, $rest) {
            $result_ = $result ();
            if (true === is_obj_type ($result_, 'PauseStepContinue')) {
                $process = $rest ($result_->result);
                return $process ();
            }
            else if (true === is_obj_type ($result_, 'PauseStepPaused')) {
                return new PauseStepPaused ($this->bind ($result_->process, $rest));
            }
            else {
                throw new Exception (sprintf ('PauseMonad->bind () was applied to a coroutine step of an unknown type: %s.', get_type ($result_)));
            }
        };
    }

    /* PauseMonad->monad_do
    Runs a coroutine.
    
    Example usage:
        $m = new PauseMonad ();
        $code = '
            monad_do (yield_ (null));
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = ($result_->process) ();
        $result___ = $result__->result;
        // $result___ === 1
    
    Remarks:
    None.
    
    @result - The coroutine to run.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */    
    public function monad_do (/* callable : unit -> PauseStep */ $result, callable /* : unit -> callable : unit -> PauseStep */ $rest) : callable /* : unit -> PauseStep */ {
        /* No value is bound as a result of this call. $rest simply calls Monad->eval_helper (). See Monad->do_helper (). */
        return $this->bind ($result, $rest);
    }
    
    public function __construct () {
        parent::__construct ('callable');
    }
}

?>