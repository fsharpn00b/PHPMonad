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
require_once '../Monads/SeqMonad.php';

/* Tests for SeqMonad. */

/**
@backupGlobals disabled
*/
class SeqMonadTest extends PHPUnit_Framework_TestCase
{
    /**
    @dataProvider test_provider
    */
    public function test (string $code, callable $expected_result) /* : void */ {
        $m = new SeqMonad ();
        $result = $m->monad_eval ($code);
        $this->assertEquals ($result, $expected_result);
    }
    
    public static function test_provider () : array {
        return array (
            /* SeqMonad->unit () */
            array ('unit (1);', function () { return new SeqNodeSome (1, function () { return new SeqNodeNone (); }); }),
            /* SeqMonad->combine () */
            array ('
                unit2 (get_empty_seq ());
                unit2 (get_empty_seq ());
            ', get_empty_seq ()),
            /* SeqMonad->delay () */
            /* SeqMonad->delay () simply removes the delay. */
            array ('unit (1);', function () { return new SeqNodeSome (1, function () { return new SeqNodeNone (); }); }),
        );
    }

    /**
    @dataProvider test_provider_2
    */
    public function test_2 (/* mixed */ $result, /* mixed */ $expected_result) /* : void */ {
        $this->assertEquals ($result, $expected_result);
    }
        
    public static function test_provider_2 () : array {
        return array (
            /* seq_unit */
            array (seq_unit (1), function () { return new SeqNodeSome (1, function () { return new SeqNodeNone (); }); }),
            /* get_empty_seq */
            array (get_empty_seq (), function () { return new SeqNodeNone (); }),
            /* counter */
            /* $start === $end */
            array (counter (new SeqMonad (), 0, 0), function () {
                return new SeqNodeSome (0, function () {
                    return new SeqNodeNone ();
                });
            }),
            /* $start < $end */
            array (counter (new SeqMonad (), 0, 1), function () {
                return new SeqNodeSome (0, function () {
                    return new SeqNodeSome (1, function () {
                        return new SeqNodeNone ();
                    });
                });
            }),
            /* $start > $end */
            array (counter (new SeqMonad (), 1, 0), get_empty_seq ()),
            /* seq_iter */
            array (eval ('
                $s = counter (new SeqMonad (), 0, 2);
                global $result;
                $result = \'\';
                seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
                return $result;
            '), '0 1 2 '),
            /* seq_take */
            array (eval ('
                $s = counter (new SeqMonad (), 0, 10);
                $s = seq_take (3, $s);
                global $result;
                $result = \'\';
                seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
                return $result;
            '), '0 1 2 ')
        );
    }
    
    public function test_class_usage_example () /* : void */ {
        function fibonacci (SeqMonad $m, float $a, float $b) : callable {
            $rec = function () use ($m, $a, $b) : SeqNode { return new SeqNodeSome ($a, fibonacci ($m, $b, $a + $b)); };
            $code = 'unit2 ($rec);';
            return $m->monad_eval ($code, get_defined_vars ());
        }

        $seq = fibonacci (new SeqMonad (), 1, 1);
        $seq = seq_take (10, $seq);
        global $result;
        $result = '';
        seq_iter (function ($x) { global $result; $result .= "$x "; }, $seq);
        $this->assertEquals ($result, '1 1 2 3 5 8 13 21 34 55 ');
    }
    
    public function test_seq_unit_usage_example () /* : void */ {
        $result = seq_unit (1);
        $result_ = $result ();
        $this->assertEquals ($result_->item, 1);
        $this->assertEquals (($result_->next) (), new SeqNodeNone ());
    }
    
    public function test_get_empty_seq_usage_example () /* : void */ {
        $result = get_empty_seq ();
        $result_ = $result ();
        $this->assertEquals ($result_, new SeqNodeNone ());
    }

    public function test_unit_usage_example () /* : void */ {
        $m = new SeqMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        $this->assertEquals ($result_->item, 1);
        $this->assertEquals (($result_->next) (), new SeqNodeNone);
    }

    public function test_combine_usage_example () /* : void */ {
        $m = new SeqMonad ();
        $s1 = counter ($m, 0, 1);
        $s2 = counter ($m, 2, 3);
        $code = '
            unit2 ($s1);
            unit2 ($s2);
        ';
        $s3 = $m->monad_eval ($code, get_defined_vars ());
        global $result;
        $result = '';
        seq_iter (function ($x) { global $result; $result .= "$x "; }, $s3);
        $this->assertEquals ($result, '0 1 2 3 ');
    }

    /* SeqMonad->delay () has no usage example, as it is for internal use only. */
    public function test_delay_usage_example () /* : void */ {
    }

    public function test_counter_usage_example () /* : void */ {
        $m = new SeqMonad ();
        $s = counter ($m, 0, 2);
        global $result;
        $result = '';
        seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
        $this->assertEquals ($result, '0 1 2 ');
    }

    public function test_seq_iter_usage_example () /* : void */ {
        $m = new SeqMonad ();
        $s = counter ($m, 0, 2);
        global $result;
        $result = '';
        seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
        $this->assertEquals ($result, '0 1 2 ');
    }

    public function test_seq_take_usage_example () /* : void */ {
        /* This was already defined in test_class_usage_example. */
        /*
        function fibonacci (SeqMonad $m, float $a, float $b) {
            $rec = function () use ($m, $a, $b) { return new SeqNodeSome ($a, fibonacci ($m, $b, $a + $b)); };
            $code = 'unit2 ($rec);';
            return $m->monad_eval ($code, get_defined_vars ());
        }
        */

        $seq = fibonacci (new SeqMonad (), 1, 1);
        $seq = seq_take (10, $seq);
        global $result;
        $result = '';
        seq_iter (function ($x) { global $result; $result .= "$x "; }, $seq);
        $this->assertEquals ($result, '1 1 2 3 5 8 13 21 34 55 ');
    }
}

?>