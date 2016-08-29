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
require_once '../Monads/PauseMonad.php';

/* Tests for PauseMonad. */

/**
@backupGlobals disabled
*/
class PauseMonadTest extends PHPUnit_Framework_TestCase
{
    /**
    @dataProvider test_provider
    */
    public function test (string $code, callable $expected_result) /* : void */ {
        $m = new PauseMonad ();
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, $expected_result);
    }
    
    public static function test_provider () : array {
        return array (
            /* PauseMonad->unit */
            array ('unit (1);', function () { return new PauseStepContinue (1); }),
            /* PauseMonad->bind */
            array ('bind (\'x\', yield_ (1)); unit ($x);', function () {
                 return new PauseStepPaused (function () {
                    return new PauseStepContinue (1);
                });               
            }),
            /* PauseMonad->monad_do */
            array ('monad_do (yield_ (null)); unit (1);', function () {
                return new PauseStepPaused (function () {
                    return new PauseStepContinue (1);
                });
            })
        );
    }

    public function test_yield () /* : void */ {
        $expected_result = function () {
            return new PauseStepPaused (function () {
                return new PauseStepContinue (null);
            });
        };
        $this->assertEquals (yield_ (null), $expected_result);
    }
    
    /* See ../Samples/Coroutine.php. */
    public function test_class_usage_example () /* : void */ {

    }

    public function test_yield_usage_example () /* : void */ {
        $m = new PauseMonad ();
        $code = '
            monad_do (yield_ (null));
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = ($result_->process) ();
        $result___ = $result__->result;
        $this->assertEquals ($result___, 1);
    }
    
    public function test_unit_usage_example () /* : void */ {
        $m = new PauseMonad ();
        $code = '
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = $result_->result;
        $this->assertEquals ($result__, 1);
    }
    
    public function test_bind_usage_example () /* : void */ {
        $m = new PauseMonad ();
        $code = '
            bind (\'x\', yield_ (1));
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = ($result_->process) ();
        $result___ = $result__->result;
        $this->assertEquals ($result___, 1);
    }
    
    public function test_monad_do_usage_example () /* : void */ {
        $m = new PauseMonad ();
        $code = '
            monad_do (yield_ (null));
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $result__ = ($result_->process) ();
        $result___ = $result__->result;
        $this->assertEquals ($result___, 1);
    }
}

?>