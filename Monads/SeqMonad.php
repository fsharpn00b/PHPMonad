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

/* Either an item in a sequence, or the terminator of the sequence. */
abstract class SeqNode {}
/* The terminator of a sequence. */
class SeqNodeNone extends SeqNode {}
/* An item in a sequence. */
class SeqNodeSome extends SeqNode {
    public /* mixed */ $item;
    public /* unit -> SeqNode */ $next;
    public function __construct (/* mixed */ $item, callable $next) {
        $this->item = $item;
        $this->next = $next;
    }    
}

/* seq_unit
Creates a sequence from a value.

Example usage:
    $result = seq_unit (1);
    $result_ = $result ();
    // $result_->item === 1
    // ($result_->next) () === new SeqNodeNone () 
    
Remarks:
None.

@value - The input value.
@return - The sequence.
*/
function seq_unit ($value) : callable {
    return function () use ($value) : SeqNode {
        return new SeqNodeSome ($value, function () {
            return new SeqNodeNone ();
        });
    };
}

/* get_empty_seq
Returns an empty sequence.

Example usage:
    $result = get_empty_seq ();
    $result_ = $result ();
    // $result_ === new SeqNodeNone ()

Remarks:
None.

@return - An empty sequence.
*/
function get_empty_seq () : callable {
    return function () {
        return new SeqNodeNone ();
    };
}

/* A Sequence monad, which can be used to create a lazily evaluated sequence.

Example usage:
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
    // $result === '1 1 2 3 5 8 13 21 34 55 '
*/
class SeqMonad extends Monad {
    
    /* SeqMonad->unit
    Creates a sequence from a value.

    Example usage:
        $m = new SeqMonad ();
        $code = 'unit (1);';
        $result = $m->monad_eval ($code);
        $result_ = $result ();
        // $result_->item === 1
        // ($result_->next) () === new SeqNodeNone () 

    Remarks:
    None.
    
    @value - The input value.
    @return - The sequence.
    */
    protected function unit (/* mixed */ $value) : callable {
//        echo sprintf ("Evaluating item: %d\n", $value);
        return seq_unit ($value);
    }

/* We want to combine the first and second sequences. The naive way to do this is to evaluate the first sequence to the last node, then set its $next field to the first node of the second sequence. However, we do not want to evaluate the first sequence.
Instead, we return a node whose $item field is copied from the current node of the first sequence, but whose $next field points to a closure. The closure checks to see if there are any more nodes in the first sequence. If so, it recursively calls combine with the next node in the first sequence, and does not evaluate the second sequence. This recursion continues until we finish evaluating the first sequence. At that point, the closure simply returns the second sequence.
See the implementation of combine here:
http://tryjoinads.org/docs/computations/layered.html
*/
/* Note we cannot override the parameter types in inherited functions, but we can override the return value types. */
    /* SeqMonad->combine
    Combines two sequences.
    
    Example usage:
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
        // $result === '0 1 2 3 '
    
    Remarks:
    None.
    
    @value1 - The first sequence.
    @value2 - The second sequence.
    @return - The combined sequence.
    */
    protected function combine (/* callable : unit -> SeqNode */ $value1, /* callable : unit -> SeqNode */ $value2) : callable {
        return function () use ($value1, $value2) : SeqNode {
            $value1_ = $value1 ();
            if (true === is_obj_type ($value1_, 'SeqNodeSome')) {
                return new SeqNodeSome ($value1_->item, $this->combine ($value1_->next, $value2));
            }
            else {
                return $value2 ();
            }
        };
    }

    /* We implement Monad->combine (), so we must implement Monad->delay (). */
    /* SeqMonad->delay
    Runs a delayed sequence.
    
    Example usage:
    This function is for internal use only.
    
    Remarks:
    None.
    
    @f - The delayed sequence.
    @return - The sequence.
    */
    protected function delay (callable $f) : callable {
        return $f ();
    }

    public function __construct () {
        parent::__construct ('callable');
    }
}

/* counter
Returns a sequence that contains the numbers from the start number to the end number.

Example usage:
    $m = new SeqMonad ();
    $s = counter ($m, 0, 2);
    global $result;
    $result = '';
    seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
    // $result === '0 1 2 '

Remarks:
None.

@m - A SeqMonad instance.
@start - The number to start the sequence.
@end - The number to end the sequence.
@return - The sequence. If @start > @end, @return is an empty sequence.
*/
function counter (SeqMonad $m, int $start, int $end) : callable {
    $rec = function () use ($m, $start, $end) { return new SeqNodeSome ($start, counter ($m, $start + 1, $end)); };
    /* Note it is helpful to keep in mind that in the recursive case, Monad->unit2 () is applied to the result of a future call to Monad->combine (). */
    $code = '
        if ($start > $end) {
            unit2 (get_empty_seq ());
        }
        else if ($start === $end) {
            unit ($start);
        }
        else {
            unit2 ($rec);
        }
    ';
    return $m->monad_eval ($code, get_defined_vars ());
}

/* seq_iter
Applies the specified function to each item in the input sequence.

Example usage:
    $m = new SeqMonad ();
    $s = counter ($m, 0, 2);
    global $result;
    $result = '';
    seq_iter (function ($x) { global $result; $result .= "$x "; }, $s);
    // $result === '0 1 2 '

Remarks:
None.

@f - The function to apply to each item in the sequence.
@seq - The input sequence.
@return - None.
*/
function seq_iter (callable $f, callable $seq) /* : void */ {
    $node = $seq ();
    if (true === is_obj_type ($node, 'SeqNodeSome')) {
        $f ($node->item);
        seq_iter ($f, $node->next);
    }
    else {
        return;
    }
}

/* seq_take
Return a new sequence that contains the specified number of items from the input sequence. This is useful for dealing with infinite sequences.

Example usage:
    function fibonacci (SeqMonad $m, float $a, float $b) {
        $rec = function () use ($m, $a, $b) { return new SeqNodeSome ($a, fibonacci ($m, $b, $a + $b)); };
        $code = 'unit2 ($rec);';
        return $m->monad_eval ($code, get_defined_vars ());
    }

    $seq = fibonacci (new SeqMonad (), 1, 1);
    $seq = seq_take (10, $seq);
    global $result;
    $result = '';
    seq_iter (function ($x) { global $result; $result .= "$x "; }, $seq);
    // $result === '1 1 2 3 5 8 13 21 34 55 '

Remarks:
None.

@count - The number of items to take from the input sequence.
@seq - The input sequence.
@return - The new sequence.
*/
function seq_take (int $count, callable $seq) : callable {
    return function () use ($count, $seq) {
        if ($count === 0) {
            return new SeqNodeNone ();
        }
        else {
            $node = $seq ();
            if (true === is_obj_type ($node, 'SeqNodeSome')) {
                return new SeqNodeSome ($node->item, seq_take ($count - 1, $node->next));
            }
            else {
                return $node;
            }
        }
    };
}

?>