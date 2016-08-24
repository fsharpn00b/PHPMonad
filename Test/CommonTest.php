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

/**
@backupGlobals disabled
*/
class CommonTest extends PHPUnit_Framework_TestCase
{
    /* partially_apply */
    
/**
@dataProvider partially_apply_provider
*/
    public function test_partially_apply (callable $f, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals ($f (), $expected_result);
    }

    public static function partially_apply_provider () : array {
        $f = function (int $a, int $b, int $c) : int {
            return $a + $b + $c;
        };
        $expected_result = $f (1, 2, 3);
        
        $test1 = function () use ($f) {
            $f_ = partially_apply ($f, 1);
            $f__ = partially_apply ($f_, 2);
            return $f__ (3);
        };
        $test2 = function () use ($f) {
            $f_ = partially_apply ($f, 1);
            /* Try partially applying the function to no parameters. It should have no effect. */
            $f__ = partially_apply ($f_);
            $f___ = partially_apply ($f__, 2);
            return $f___ (3);
        };
        $test3 = function () use ($f) {
            $f_ = partially_apply ($f, 1);
            /* Try partially applying the function to all parameters, then applying it to unit. */
            $f__ = partially_apply ($f_, 2);
            $f___ = partially_apply ($f__, 3);
            return $f___ ();
        };
        
        return array (
            'Normal' =>     array ($test1, $expected_result),
            'No params' =>  array ($test2, $expected_result),
            'All params' => array ($test3, $expected_result)
        );
    }

/**
@dataProvider compose_provider
*/
    public function test_compose (array $funcs, /* mixed */ $expected_result) /* : void */ {
        $result = compose (1, $funcs);
        $this->assertEquals ($result, $expected_result);
    }

    public static function compose_provider () : array {
        $f = function (int $x) : int {
            return $x + 1;
        };
    
        return array (
            'Compose 0 times' =>    array (array(), 1),
            'Compose 1 time' =>     array (array ($f), 2),
            'Compose 2 times' =>    array (array ($f, $f), 3)
        );
    }
    
/**
@dataProvider is_obj_type_provider
*/
    public function test_is_obj_type (/* mixed */ $value, string $type, bool $expected_result) /* : void */ {
        $result = is_obj_type ($value, $type);
        $this->assertEquals ($result, $expected_result);
    }

    public static function is_obj_type_provider () : array {
        return array (
            'array' =>      array (array (), 'array', true),
            'object' =>     array (new Maybe (false, null), 'Maybe', true),
            'callable' =>   array ('is_obj_type', 'callable', true),
            'Failure' =>    array (false, 'Maybe', false)
        );
    }

/**
@dataProvider get_type_provider
*/
    public function test_get_type (/* mixed */ $value, string $expected_result) /* : void */ {
        $result = get_type ($value);
        $this->assertEquals ($result, $expected_result);
    }

    public static function get_type_provider () : array {
        return array (
            'integer' =>    array (1, 'integer'),
            'Maybe' =>      array (new Maybe (false, null), 'Maybe'),
            'array' =>      array (array (), 'array'),
            'Closure' =>    array (function () {}, 'Closure')
        );
    }
    
/**
@dataProvider starts_with_provider
*/
    public function test_starts_with (string $haystack, string $needle, bool $expected_result) /* : void */ {
        $result = starts_with ($haystack, $needle);
        $this->assertEquals ($result, $expected_result);
    }

    public static function starts_with_provider () : array {
        return array (
            'Success' =>        array ('abcde', 'abc', true),
            'Wrong Case' =>     array ('abcde', 'ABC', false),
            'Too Long' =>       array ('abc', 'abcde', false)
        );
    }
}

?>