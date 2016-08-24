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

/* These monads show how monads work and do not need to be tested. */

/* Note this statement must be first. */
declare (strict_types = 1);

require_once '../Utils/Common.php';
require_once 'Monad.php';

class DelayTraceMonad extends Monad {

    protected function unit (/* int */ $value) : int {
        echo sprintf ("return %d\n", $value);
        return $value;
    }

    /* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    protected function combine (/* int */ $value1, /* int */ $value2) : int {
        echo sprintf ("combine: (%d, ?)\n", $value2);
        return $value1 + $value2 ();
    }
    
    protected function delay (callable $f) /* : callable */ {
        echo "delay\n";
        return $f;
    }
    
    protected function run (/* int */ $f) : int {
        echo "run\n";
        return $f ();
    }
    
    public function __construct () {
        parent::__construct ('integer');
    }
}

class NoDelayTraceMonad extends Monad {

    protected function unit (/* int */ $value) : int {
        echo sprintf ("return %d\n", $value);
        return $value;
    }

    protected function combine (/* int */ $value1, /* int */ $value2) : int {
        echo sprintf ("combine: (%d, %d)\n", $value1, $value2);
        return $value1 + $value2;
    }
    
    protected function delay (callable $f) /* : int */ {
        echo "delay\n";
        return $f ();
    }
    
    protected function run (/* int */ $f) : int {
        echo "run\n";
        return $f;
    }
    
    public function __construct () {
        parent::__construct ('integer');
    }
}

$code = '
unit (1);
unit (2);
unit (3);
';

$m = new DelayTraceMonad ();
echo "Delayed Trace Monad:\n";
$result = $m->monad_eval ($code);
echo sprintf ("Result: %d\n\n", $result);

$m = new NoDelayTraceMonad ();
echo "Non-Delayed Trace Monad:\n";
$result = $m->monad_eval ($code);
echo sprintf ("Result: %d\n\n", $result);

?>