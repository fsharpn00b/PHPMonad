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
require_once '../Monads/MonadicParser.php';

global $s1, $t1, $s2, $t2, $s3, $t3;

$s1 = 'stmt;';
$t1 = new Branch ('root', array (new Leaf ('stmt')));
$s1_ = '("stmt;")';
$s2 = 'stmt;
            if (true) {
                stmt;
            }
            else if (true) {
                stmt;
            }
            elseif (true) {
                stmt;
            }
            else {
                stmt;
            }
            stmt;';
$s2_ = '("stmt;", "if (true)(\"stmt;\") else if (true)(\"stmt;\") elseif (true)(\"stmt;\") else(\"stmt;\")", "stmt;")';
$t2 = new Branch ('root',
                array (new Leaf ('stmt'),
                    new IfBranch ('if (true)', array (new Leaf ('stmt'))),
                    new ElseIfBranch ('else if (true)', array (new Leaf ('stmt'))),
                    new ElseIfBranch ('elseif (true)', array (new Leaf ('stmt'))),
                    new ElseBranch ('else', array (new Leaf ('stmt'))),
                    new Leaf ('stmt')));
$s3 = 'if (true) {
                    if (true) {
                        if (true) {
                            stmt;
                        }
                    }
                }';
$t3 = new Branch ('root',
                array (new IfBranch ('if (true)',
                    array (new IfBranch ('if (true)',
                        array (new IfBranch ('if (true)',
                            array (new Leaf ('stmt')))))))));                                
$s3_ = '("if (true)(' .
    addcslashes ('"if (true)(' .
        addcslashes ('"if (true)(' .
            addcslashes ('"stmt;"', '\"')
        . ')"', '\"')
    . ')"', '\"')
. ')")';
    
/**
@backupGlobals disabled
*/
class MonadicParserTest extends PHPUnit_Framework_TestCase
{

    /* parse_branch_type */

/**
@dataProvider parse_branch_type_provider
*/
    public function test_parse_branch_type (string $input, Maybe $expected_result) /* : void */ {
        $result = parse_branch_type ($input);
        $this->assertEquals ($result, $expected_result);
    }

    public static function parse_branch_type_provider () : array {
        return array (
            'if' =>         array ('if', new Maybe (true, new IfBranch ('if'))),
            'else if' =>    array ('else if', new Maybe (true, new ElseIfBranch ('else if'))),
            'elseif' =>     array ('elseif', new Maybe (true, new ElseIfBranch ('elseif'))),
            'else' =>       array ('else', new Maybe (true, new ElseBranch ('else'))),
            'Failure' =>    array ('nothing', new Maybe (false, null))
        );
    }
    
    /* make_tree, make_tree_helper */
    
/**
@dataProvider make_tree_provider
*/
    public function test_make_tree (string $input, Branch $expected_result) /* : void */ {
        $result = make_tree ($input);
        $this->assertEquals ($result, $expected_result);
    }
    
    public static function make_tree_provider () : array {
        global $s1, $t1, $s2, $t2, $s3, $t3;
        return array (
            array ($s1, $t1),
            array ($s2, $t2),
            array ($s3, $t3)
        );
    }
    
    /**
     * @expectedException Exception
     */
    public function test_make_tree_exception () /* : void */ {
        $input = 'while (true) { stmt; }';
        $result = make_tree ($input);
    }
    
    /* flatten_tree, flatten_tree_helper */
    
/**
@dataProvider flatten_tree_provider
*/
    public function test_flatten_tree (Branch $input, string $expected_result) /* : void */ {
        /* These are just test values to verify flatten_tree formats strings as expected. */
        $outer_wrapper = '(%s)';
        $inner_wrapper = '(%s)';
        $result = flatten_tree ($outer_wrapper, $inner_wrapper, $input, true);
        $this->assertEquals ($result, $expected_result);
    }

    public static function flatten_tree_provider () : array {
        global $s1_, $t1, $s2_, $t2, $s3_, $t3;
        return array (
            array ($t1, $s1_),
            array ($t2, $s2_),
            array ($t3, $s3_),
            /* See MonadicParser.php for the list of tree node combinations and rules for evaluating them. */
            /* 1. Leaf, null */
            array (new Branch ('root', array (new Leaf ('x'))), '("x;")'),
            /* 2. Leaf, (other) */
            array (new Branch ('root', array (new Leaf ('x'), new Leaf ('x'))), '("x;", "x;")'),
            /* 3. Branch, null */
            array (new Branch ('root', array (new IfBranch ('x'))), '("x()")'),
            /* 4. if, else if */
            array (new Branch ('root', array (new IfBranch ('x'), new ElseIfBranch ('x'))), '("x() x()")'),
            /* 5. if, else */
            array (new Branch ('root', array (new IfBranch ('x'), new ElseBranch ('x'))), '("x() x()")'),
            /* 6. if, (other) */
            array (new Branch ('root', array (new IfBranch ('x'), new IfBranch ('x'))), '("x()", "x()")'),
            /* 7. else if, else if */
            array (new Branch ('root', array (new ElseIfBranch ('x'), new ElseIfBranch ('x'))), '("x() x()")'),
            /* 8. else if, else */
            array (new Branch ('root', array (new ElseIfBranch ('x'), new ElseBranch ('x'))), '("x() x()")'),
            /* 9. else if, (other) */
            array (new Branch ('root', array (new ElseIfBranch ('x'), new IfBranch ('x'))), '("x()", "x()")'),
            /* 10. else, (any) */
            array (new Branch ('root', array (new ElseBranch ('x'), new IfBranch ('x'))), '("x()", "x()")')
        );
    }
    
    /* parse_monadic_code and get_parser just call the functions we have already tested. */
}

?>