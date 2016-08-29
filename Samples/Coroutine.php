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

/* The following code is based on the code in Chapter 5 of Friendly F# by Giulia Costantini and Giuseppe Maggiore, available at:
https://www.amazon.com/Friendly-Fun-game-programming-Book-ebook/dp/B005HHYIWC
*/

/* Note this statement must be first. */
declare (strict_types = 1);

require_once '../Monads/PauseMonad.php';

global $result;
$result = '';

function get_process_step (string $process_name, int $process_step) /* : void */ {
    global $result;
    $result .= sprintf ("Process %s: %d step(s) remaining.\n", $process_name, $process_step);
}

function get_last_process_step (string $process_name) /* : void */ {
    global $result;
    $result .= sprintf ("Process %s finished.\n", $process_name);
}

function get_process (string $process_name, int $step) : callable /* : unit -> PauseStep */ {
    $m = new PauseMonad ();
    $code =
        /* Yield to other processes. */
        "monad_do (yield_ (null));
        if ($step === 0) {
            get_last_process_step ($process_name);
            unit ($process_name);
        }
        else {
            get_process_step ($process_name, $step);
            unit2 (get_process ($process_name, ($step - 1)));
        }
    ";
    return $m->monad_eval ($code);
}

function race (callable /* : unit -> PauseStep */ $p1, callable /* : unit -> PauseStep */ $p2) /* : void */ {
    global $result;
    $p1_ = $p1 ();
    $p2_ = $p2 ();
    if (true === is_obj_type ($p1_, 'PauseStepContinue')) {
        $result .= sprintf ("Process %s finished first.\n", $p1_->result);
    }
    else if (true === is_obj_type ($p2_, 'PauseStepContinue')) {
        $result .= sprintf ("Process %s finished first.\n", $p2_->result);
    }
    else {
        return race ($p1_->process, $p2_->process);
    }
}

$p1 = get_process ('1', 1);
$p2 = get_process ('2', 2);
race ($p1, $p2);

$expected_result = "Process 1: 1 step(s) remaining.\nProcess 2: 2 step(s) remaining.\nProcess 1 finished.\nProcess 2: 1 step(s) remaining.\nProcess 1 finished first.\n";

echo "Expected result:\n$expected_result\n";
echo "Actual result:\n$result";

?>