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
require_once '../Monads/MaybeMonad.php';

/* Helper functions. */

if (false === function_exists ('get_maybe_some')) {
    function get_maybe_some (/* mixed */ $value) : Maybe {
        return new Maybe (true, $value);
    }
}
if (false === function_exists ('get_maybe_none')) {
    function get_maybe_none () : Maybe {
        return new Maybe (false, null);
    }
}

/* Tests for MaybeMonad. */

/**
@backupGlobals disabled
*/
class MaybeMonadTest extends PHPUnit_Framework_TestCase
{
    /**
    @dataProvider test_provider
    */
    public function test (string $code, Maybe $expected_result) /* : void */ {
        $m = new MaybeMonad ();
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, $expected_result);
    }

    public static function test_provider () : array {
        return array (
            /* MaybeMonad->unit () */
            array ('unit (1);', get_maybe_some (1)),
            /* MaybeMonad->unit2 () */
            array ('unit2 (get_maybe_some (1));', get_maybe_some (1)),
            /* MaybeMonad->bind () */
            array ('
                bind (\'x\', get_maybe_some (1));
                unit ($x);
            ', get_maybe_some (1)),
            array ('
                bind (\'x\', get_maybe_none ());
                unit (1);
            ', get_maybe_none ()),
            /* MaybeMonad->monad_do () */
            array ('
                monad_do (get_maybe_some (2));
                unit (1);
            ', get_maybe_some (1)),
            array ('
                monad_do (get_maybe_none ());
                unit (1);
            ', get_maybe_none ()),
            /* MaybeMonad->zero () */
            array ('// Do nothing;', get_maybe_none ()),
            /* MaybeMonad->combine () */
            array ('
                unit (1);
                unit (2);
                unit (3);
            ', get_maybe_some (6)),
            /* MaybeMonad->delay () */
            /* MaybeMonad->delay () simply removes the delay. */
            array ('unit (1);', get_maybe_some (1))
        );
    }
    
    public function test_class_usage_example () /* : void */ {
        function div_by (float $a, float $b) : Maybe {
            if (0 === $b) {
                return new Maybe (false, null);
            }
            else {
                return new Maybe (true, $a / $b);
            }
        }        
        
        $m = new MaybeMonad ();
        $result = $m->monad_eval ('
            bind (\'x\', div_by (10.0, 5.0));
            bind (\'y\', div_by (20.0, $x));
            bind (\'z\', div_by (10.0, $y));
            unit ($z);
        ');
        if (true === $result->is_some) {
            $z = $result->value;
        }
        else {
            return;
        }
        $this->assertEquals ($z, 1);
        
        $result = div_by (10.0, 5.0);
        if (true === $result->is_some) {
            $x = $result->value;
        }
        else {
            return;
        }

        $result = div_by (20.0, $x);
        if (true === $result->is_some) {
            $y = $result->value;
        }
        else {
            return;
        }

        $result = div_by (10.0, $y);
        if (true === $result->is_some) {
            $z = $result->value;
        }
        else {
            return;
        }
        $this->assertEquals ($z, 1);
    }
    
    public function test_unit_usage_example () /* : void */ {
        $m = new MaybeMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_some (1));
    }
    
    public function test_unit2_usage_example () /* : void */ {
        
    }

    public function test_bind_usage_example () /* : void */ {
        $m = new MaybeMonad ();
        $code = '
            bind (\'x\', new Maybe (true, 1));
            $x += 1;
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_some (2));
    }

    public function test_do_usage_example () /* : void */ {
        function Maybe_test_run () {
            return new Maybe (true, null);
        }
        function Maybe_test_stop () {
            return new Maybe (false, null);
        }
        
        $m = new MaybeMonad ();
        $code = '
            monad_do (Maybe_test_run ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_some (1));

        $code = '
            monad_do (Maybe_test_stop ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_none ());
    }

    public function test_zero_usage_example () /* : void */ {
        $m = new MaybeMonad ();
        $code = '
            bind (\'x\', new Maybe (true, 1));
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_none ());
    }

    public function test_combine_usage_example () /* : void */ {
        $m = new MaybeMonad ();
        $code = '
            unit (1);
            unit2 (new Maybe (true, 2));
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, get_maybe_some (3));
    }
    
    /* MaybeMonad->delay () has no usage example, as it is for internal use only. */
    public function test_delay_usage_example () /* : void */ {
    }
    
}

?>