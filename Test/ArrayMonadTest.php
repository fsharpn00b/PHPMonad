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
require_once '../Monads/ArrayMonad.php';

/* Tests for ArrayMonad. */

/**
@backupGlobals disabled
*/
class ArrayMonadTest extends PHPUnit_Framework_TestCase
{
    /**
    @dataProvider test_provider
    */
    public function test (string $code, array $expected_result) /* : void */ {
        $m = new ArrayMonad ();
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, $expected_result);
    }
    
    public static function test_provider () : array {
        return array (
            /* ArrayMonad->unit () */
            array ('unit (1);', array (1)),
            /* ArrayMonad->unit2 () */
            array ('unit2 (array (1));', array (1)),
            /* ArrayMonad->bind () */
            array ('
                bind (\'x\', array (1));
                unit ($x);
            ', array (1)),
            array ('
                bind (\'x\', array ());
                unit (1);
            ', array ()),
            /* ArrayMonad->monad_do () */
            array ('
                monad_do (array (2));
                unit (1);
            ', array (1)),
            array ('
                monad_do (array ());
                unit (1);
            ', array ()),
            /* ArrayMonad->zero () */
            array ('// Do nothing;', array ()),
            /* ArrayMonad->combine () */
            array ('
                unit (1);
                unit (2);
                unit (3);
            ', array (1, 2, 3)),
            /* ArrayMonad->delay () */
            /* ArrayMonad->delay () simply removes the delay. */
            array ('unit (1);', array (1))
        );
    }

    public function test_class_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $result = $m->monad_eval ('
            bind (\'x\', array (1, 2, 3));
            bind (\'y\', array (4, 5, 6));
            unit ($x * $y);
        ');
        $this->assertEquals ($result, array (4, 5, 6, 8, 10, 12, 12, 15, 18));
    }
    
    public function test_unit_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array (1));
    }
    
    public function test_unit2_usage_example () /* : void */ {
        
    }

    public function test_bind_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $code = '
            bind (\'x\', array (1, 2, 3));
            $x += 1;
            unit ($x);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array (2, 3, 4));
    }

    public function test_do_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $code = '
            monad_do (array (1, 2, 3));
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array (1, 1, 1));

        $code = '
            monad_do (array ());
            unit (1);
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array ());
    }

    public function test_zero_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $code = '
            bind (\'x\', array (1));
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array ());
    }

    public function test_combine_usage_example () /* : void */ {
        $m = new ArrayMonad ();
        $code = '
            unit (1);
            unit2 (array (2));
        ';
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, array (1, 2));
    }

    /* ArrayMonad->delay () has no usage example, as it is for internal use only. */
    public function test_delay_usage_example () /* : void */ {
    }
    
}

?>