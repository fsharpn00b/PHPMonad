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
require_once '../Monads/StateMonad.php';

/* Tests for StateMonad. */

/**
@backupGlobals disabled
*/
class StateMonadTest extends PHPUnit_Framework_TestCase
{

    /**
    @dataProvider test_provider
    */
    public function test (string $code, /* mixed */ $initial_state, State $expected_result) /* : void */ {
        $m = new StateMonad ();
        $f = $m->monad_eval ($code);
        $result = $f ($initial_state);
        $this->assertEquals ($result, $expected_result);
    }

    public static function test_provider () : array {
        return array (
            /* StateMonad->unit () */
            array ('unit (2);', 1, new State (1, 2)),
            /* StateMonad->unit2 () */
            array ('unit2 (get_state ());', 1, new State (1, 1)),
            /* StateMonad->bind () */
            array ('
                bind (\'x\', get_state ());
                unit ($x);
            ', 1, new State (1, 1)),
            /* The State monad has no notion of "None", so we do not call bind () or monad_do () with "None". */
            /* StateMonad->monad_do () */
            array ('
                monad_do (set_state (1));
                unit (2);
            ', 3, new State (1, 2))
            /* We do not implement Monad->zero () or Monad->combine () for the State monad. */
        );
    }

    /* See ../Samples/StateMonadCesaro.php. */
    public function test_class_usage_example () /* : void */ {

    }
    
    public function test_unit_usage_example () /* : void */ {
        $m = new StateMonad ();
        $code = 'unit (1);';
        $f = $m->monad_eval ($code);
        $state = 2;
        $result = $f ($state);
        $this->assertEquals ($result, new State (2, 1));
    }
    
    public function test_bind_usage_example () /* : void */ {
        $m = new StateMonad ();
        $code = '
            bind (\'x\', get_state ());
            $x += 1;
            unit ($x);
        ';
        $f = $m->monad_eval ($code);
        $state = 1;
        $result = $f ($state);
        $this->assertEquals ($result, new State (1, 2));
    }

    public function test_do_usage_example () /* : void */ {
        $m = new StateMonad ();
        $code = '
            monad_do (set_state (2));
            unit (1);
        ';
        $f = $m->monad_eval ($code);
        $state = 1;
        $result = $f ($state);
        $this->assertEquals ($result, new State (2, 1));
    }

    /* We do not implement Monad->zero () or Monad->combine () for the State monad. */
    
    public function test_zero_usage_example () /* : void */ {
    }

    public function test_combine_usage_example () /* : void */ {
    }

}

?>