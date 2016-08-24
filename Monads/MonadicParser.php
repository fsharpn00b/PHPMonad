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
require_once '../Utils/ArrayUtils.php';

/* A tree node. */
abstract class Node {
    public $value;
    public function __construct (/* mixed */ $value) {
        $this->value = $value;
    }
}
/* A tree branch. */
class Branch extends Node {
    public $children;
    public function __construct (/* mixed */ $value, array $children = array ()) {
        $this->children = $children;
        parent::__construct ($value);
    }
}
/* A tree branch that represents an if statement. */
class IfBranch extends Branch {}
/* A tree branch that represents an else if statement. */
class ElseIfBranch extends Branch {}
/* A tree branch that represents an else statement. */
class ElseBranch extends Branch {}
/* A tree leaf. */
class Leaf extends Node {}

/* We use this to convert statements to tree branch types. */
define ('branch_types', array (
    'if' => 'IfBranch',
    'else if' => 'ElseIfBranch',
    'elseif' => 'ElseIfBranch',
    'else' => 'ElseBranch'
));

/* parse_branch_type
Returns the tree branch type that corresponds to the input statement.

Example usage
This function is for internal use only.

Remarks
None.

@input - The input statement.
@return - A Maybe.
    If a tree branch type is found that corresponds to @input:
        @is_some - true.
        @value - The tree branch type.
    If no such tree branch type is found:
        @is_some - false.
        @value - null.
*/
function parse_branch_type (string $input) : Maybe {
    $result = ArrayUtils::try_find (function (string $key, $ignore) use ($input) {
        return starts_with ($input, $key);
    }, branch_types);
    if (false === $result->is_some) {
        return $result;
    }
    else {
        $branch = (new ReflectionClass ($result->value->value))->newInstanceArgs (array ($input));
        return new Maybe (true, $branch);
    }
}

/* make_tree_helper
Parses the input text starting from the specified position. Adds the results to the input tree branch.

Example usage
This function is for internal use only.

Remarks
Raises an exception if a control construct other than the following is used: if, else if, else.

@branch - The tree branch to add the parsing results to.
@text - The input text.
@pos - The current position in @text.
@return - An object.
    @branch - The input tree branch with the parsing results added.
    @pos - The updated current position in @text.
*/
/* This approach is not very functional. However, the functional approach would mean converting the text to an array and calling an anonymous function to process every character, which would be costly.
I prefer to return a wrapper such as Maybe or Either, rather than raise an exception, whenever possible. However, in this case, if we return such a wrapper, it propagates to Monad->monad_eval. As a result, running monadic code for a given monadic type returns a wrapper rather than an instance of the monadic type, which is not how monads work in other languages. */
function make_tree_helper (Branch $branch, string $text, int $pos) : StdClass {
    $current = '';
    while ($pos < strlen ($text)) {
        $char = $text[$pos];
        switch ($char) {
            case ';':
                /* Add the current statement to the current tree branch as a leaf. */
                $branch->children = ArrayUtils::append ($branch->children, null, new Leaf (trim ($current)));
                /* Clear the current statement and go to the next position. */
                $current = '';
                $pos++;
                break;
            case '{':
                $current = trim ($current);
                /* Convert the current statement to a tree branch type. */
                $result = parse_branch_type ($current);
                if (false === $result->is_some) {
                    throw new Exception (sprintf ('Currently you cannot use \'%s\' in monadic code. Please use only the following control constructs: if, else if, else.', $current));
				}
				else {
                    /* Clear the current statement. */
	                $current = '';
                    /* Continue parsing the text from the next position. Add the results to the new tree branch. */
                	$result = make_tree_helper ($result->value, $text, $pos + 1);
                	$new_branch = $result->branch;
                    /* Set the position to the one reached in the recursive call. */
                	$pos = $result->pos;
                    /* Add the new tree branch to the current tree branch. */
                	$branch->children = ArrayUtils::append ($branch->children, null, $new_branch);
                	break;
				}
            case '}':
                /* We are finished adding to the current tree branch. Return it and the current position. */
                return (object) ['branch' => $branch, 'pos' => $pos + 1];
            default:
                /* Add the character to the current statement and go to the next position. */
                $current .= $char;
                $pos++;
                break;
        }
    }
    /* Note this ignores any characters after the last ';' or '}'. */
    return (object) ['branch' => $branch, 'pos' => $pos];
}

/* make_tree
Creates a tree by parsing the input text.

Example usage
This function is for internal use only.

Remarks:
None.

@text - The input text.
@return - The tree.
*/
function make_tree (string $text) : Branch {
    $result = make_tree_helper (new Branch ('root'), $text, 0);
    return $result->branch;
}

/* flatten_tree_helper
Returns a string created from the input tree branches and leaves.

Example usage
This function is for internal use only.

Remarks
Raises an exception if @nodes contains an unknown tree node type.

@outer_wrapper - Text to enclose the statements represented as branches and leaves directly under the tree root.
@inner_wrapper - Text to enclose the statements represented as branches and leaves under a tree branch that is not the tree root.
@nodes - The input tree branches and leaves.
@return - The string representation of the input tree nodes.
*/
function flatten_tree_helper (string $outer_wrapper, string $inner_wrapper, array $nodes) : string {
    if (true === empty ($nodes)) {
        return '';
    }
    else {
        /* We need to do the following.
        1. Evaluate each tree node in relation to the one that follows it. We do this by converting the nodes array into windows of
        size 2.
        2. Evaluate every tree node. We do this by adding a null value to the end of the nodes array, so we can detect when we are
        evaluating the last window.
        */
        $nodes = ArrayUtils::append ($nodes, null, null);
        $nodes = ArrayUtils::windowed ($nodes, 2);
        /* We add each statement to an array defined in a string that is passed to eval (), so each statement must be quoted. For
        example:
        eval ("array (\"statement 1;\", \"statement 2;\");");
        */
        $acc = '"';
        return array_reduce ($nodes, function (string $acc, array $window) use ($outer_wrapper, $inner_wrapper) {
            $fst = $window[0];
            $snd = $window[1];

            /* Each tree node pair is evaluated as follows.
            
            Case    First       Second      Result              Notes
            ---------------------------------------------------------
            1       Leaf        null        <statement>;"       This is the last statement.
            2       Leaf        (other)     <statement>;", "    This is not the last statement.
            3       Branch      null        <statement>"        This is the last statement.
            4       if          else if     <statement>(space)  if and else if blocks are not separated.
            5       if          else        <statement>(space)  if and else blocks are not separated.
            6       if          (other)     <statement>", "     This if block is not followed by an else if or else block.
            7       else if     else if     <statement>(space)  Two else if blocks are not separated.
            8       else if     else        <statement>(space)  else if and else blocks are not separated.
            9       else if     (other)     <statement>", "     This else if block is not followed by an else if or else block.
            10      else        (any)       <statement>", "     This else block is not the last statement.
            */
            
            if (true === is_obj_type ($fst, 'Leaf')) {
                /* Because we quote the statement, we must escape its contents. We must escape quote marks, variable names, and
                backslashes previously used to escape other characters (remember that if/else blocks can be nested, so we might
                escape a statement more than once). */
                $to_add = addcslashes ($fst->value, '"\$');
                /* Cases 1 and 2. */
                if (true === is_null ($snd)) {
                    $to_add = "$to_add;\"";
                }
                else {
                    $to_add = "$to_add;\", \"";
                }
            }
            else if (true === is_subclass_of ($fst, 'Branch')) {
                /* Recursively evaluate the tree branch. */
                $to_add = flatten_tree ($outer_wrapper, $inner_wrapper, $fst, false);
                /* Include the statement that defines the tree branch. */
                $to_add = $fst->value . $to_add;
                /* Escape the statements under the tree branch so we can quote them. */
                $to_add = addcslashes ($to_add, '"\$');
                
                /* Case 3. */
                if (true === is_null ($snd)) {
                    $to_add = "$to_add\"";
                }
                else if (true === is_obj_type ($fst, 'IfBranch')) {
                    /* Cases 4 and 5. */
                    if (
                        true === is_obj_type ($snd, 'ElseIfBranch') ||
                        true === is_obj_type ($snd, 'ElseBranch')) {
                        $to_add = "$to_add ";
                    }
                    /* Case 6. */
                    else {
                        $to_add = "$to_add\", \"";
                    }
                }
                else if (true === is_obj_type ($fst, 'ElseIfBranch')) {
                    /* Cases 7 and 8. */
                    if (
                        true === is_obj_type ($snd, 'ElseIfBranch') ||
                        true === is_obj_type ($snd, 'ElseBranch')) {
                        $to_add = "$to_add ";
                    }
                    /* Case 9. */
                    else {
                        $to_add = "$to_add\", \"";
                    }
                }
                /* Case 10. */
                else if (true === is_obj_type ($fst, 'ElseBranch')) {
                    $to_add = "$to_add\", \"";
                }
                else {
                    /* This should never happen. */
                    throw new Exception (sprintf ('Unknown tree branch type: %s.', get_type ($fst)));
                }

            }
            else {
                /* This should never happen. */
                throw new Exception (sprintf ('Unknown tree node type: %s.', get_type ($fst)));
            }
            return $acc .= $to_add;
        }, $acc);
    }
}

/* flatten_tree
Returns a string created from the input tree.

Example usage
This function is for internal use only.

Remarks:
None.

@outer_wrapper - Text to enclose the statements represented as branches and leaves directly under the tree root.
@inner_wrapper - Text to enclose the statements represented as branches and leaves under a tree branch that is not the tree root.
@branch - The input tree branch.
@is_top_level - True if @branch is the root of the tree; otherwise, false.
@return - The string representation of the input tree.
*/
function flatten_tree (string $outer_wrapper, string $inner_wrapper, Branch $branch, bool $is_top_level) : string {
    $result = flatten_tree_helper ($outer_wrapper, $inner_wrapper, $branch->children);
    $wrapper = $is_top_level ? $outer_wrapper : $inner_wrapper;
    $result = sprintf ($wrapper, $result);
    return $result;
}

/* parse_monadic_code
Returns the input monadic code, formatted so it can be run.

Example usage
This function is for internal use only.

Remarks:
None.

@outer_wrapper - Text to enclose the statements represented as branches and leaves directly under the tree root.
@inner_wrapper - Text to enclose the statements represented as branches and leaves under a tree branch that is not the tree root.
@code - The input monadic code.
@return - The formatted monadic code.
*/
function parse_monadic_code (string $outer_wrapper, string $inner_wrapper, string $code) : string {
    $result = make_tree ($code);
    $result = flatten_tree ($outer_wrapper, $inner_wrapper, $result, true);
    return $result;
}

/* get_parser
Returns a function that formats monadic code so it can be run.

Example usage
This function is for internal use only.

Remarks:
None.

@outer_wrapper - Text to enclose the statements represented as branches and leaves directly under the tree root.
@inner_wrapper - Text to enclose the statements represented as branches and leaves under a tree branch that is not the tree root.
@return - A function. Signature:
    @code - The input monadic code.
    @return - The formatted monadic code.
*/
function get_parser (string $outer_wrapper, string $inner_wrapper) : callable {
    return function (string $code) use ($outer_wrapper, $inner_wrapper) : string {
        return parse_monadic_code ($outer_wrapper, $inner_wrapper, $code);
    };
}

?>