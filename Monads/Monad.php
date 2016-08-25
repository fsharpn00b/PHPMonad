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
require_once 'MonadicParser.php';

/* This file implements a monad.
Generally, a monad abstracts out computations that are not easily expressed in functional languages. For our purposes, a monad
helps compose functions that return values of complex types (for example, Maybe).
For general reference, see:
https://bartoszmilewski.com/2011/01/09/monads-for-the-curious-programmer-part-1/
https://fsharpforfunandprofit.com/series/computation-expressions.html
*/

/* Consts. */

const outer_wrapper = 'return array (%s);';
const inner_wrapper = '{ unit2 ($this->eval_helper (array (%s), $_context)); }';

/* Helper types. */

/* The result of evaluating a statement in the monadic code. */
/* Monadic code does not call zero (), combine (), delay (), or run () explicitly, so we do not have result types for those
functions. */
abstract class EvalResult {
    public /* bool */ $unit_called = false;
    public /* bool */ $bind_called = false;
    public /* bool */ $do_called = false;
}

/* In this case, the statement in the monadic code had no result; however, it might have added or reassigned variables. */
class NoResult extends EvalResult {
    public /* array */ $context;
    public function __construct (array $context) {
        $this->context = $context;
    }
}

/* In this case, the statement in the monadic code called unit (). */
class UnitResult extends EvalResult {
    public /* type */ $value;
    public function __construct (/* type */ $value) {
        $this->unit_called = true;
        $this->value = $value;
    }
}

/* In this case, the statement in the monadic code called unit2 (). */
class Unit2Result extends UnitResult {};

/* In this case, the statement in the monadic code called bind (). */
class BindResult extends EvalResult {
    public /* string */ $name;
    public /* type */ $value;
    public function __construct (string $name, /* type */ $value) {
        $this->name = $name;
        $this->value = $value;
        $this->bind_called = true;
    }
}

/* In this case, the statement in the monadic code called monad_do (). */
class DoResult extends EvalResult {
    public /* type */ $value;
    public function __construct (/* type */ $value) {
        $this->do_called = true;
        $this->value = $value;
    }
}

/* Helper functions. */

/* We declare the unit (), unit2 (), bind (), and do () functions globally so they are visible to the monadic code. Monadic code
does not call zero (), combine (), delay (), or run () explicitly. */

/* unit
Notifies the Monad class that the monadic code called unit ().

Example usage:
    $m = new MaybeMonad ();
    $code = 'unit (1)';
    $result = $m->monad_eval ($code);
    // $result->is_some === true
    // $result->value === 1

Remarks:
None.

@value - The value to promote to the monadic type. 
@return - None.
*/
function unit (/* mixed */ $value) /* : void */ {
    /* We modify this global variable rather than return a result. This is so the monad code does not look like the following.
        return bind ('x', func1 ());
        return bind ('y', func2 ($x));
        return bind ('z', func3 ($y));
        return unit ($z);
    */
    global $_eval_result;
    $_eval_result = new Maybe (true, new UnitResult ($value));
}

/* unit
Notifies the Monad class that the monadic code called unit2 ().

Example usage:
    $m = new MaybeMonad ();
    $code = 'unit2 (new Maybe (true, 1))';
    $result = $m->monad_eval ($code);
    // $result->is_some === true
    // $result->value === 1

Remarks:
None.

@value - The monadic type value. 
@return - None.
*/
function unit2 (/* type */ $value) /* : void */ {
    /* See the comments for unit (). */
    global $_eval_result;
    $_eval_result = new Maybe (true, new Unit2Result ($value));
}

/* bind
Notifies the Monad class that the monadic code called bind ().

Example usage:
    $m = new MaybeMonad ();
    $code = '
        bind (\'x\', new Maybe (true, 1));
        $x += 1;
        unit ($x);
    ';
    $result = $m->monad_eval ($code);
    // $result->is_some === true
    // $result->value === 2

Remarks:
None.

@name - The variable name to bind.
@value - The input monadic type value whose contents should be bound to @name. 
@return - None.
*/
function bind (string $name, /* mixed */ $value) /* : void */ {
    /* See the comments for unit (). */
    global $_eval_result;
    $_eval_result = new Maybe (true, new BindResult ($name, $value));
}

/* monad_do
Notifies the Monad class that the monadic code called monad_do ().

Example usage:
    $m = new MaybeMonad ();
    $code = '
        monad_do (new Maybe (true, 1));
        unit (2);
    ';
    $result = $m->monad_eval ($code);
    // $result->is_some === true
    // $result->value === 2

Remarks:
None.

@value - The input monadic type value. 
@return - None.
*/
function monad_do (/* mixed */ $value) /* : void */ {
    /* See the comments for unit (). */
    global $_eval_result;
    $_eval_result = new Maybe (true, new DoResult ($value));
}

/* Monad parent class. */
abstract class Monad /* <type param> */ {
    /* True if the child class implements combine (). */
    private $_is_combine_implemented = false;
    /* True if the child class implements delay (). */
    private $_is_delay_implemented = false;
    /* True if the child class implements run (). */
    private $_is_run_implemented = false;
    
    /* Monad->unit
    Promotes a value to the monadic type.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the monadic code calls the globally defined unit () function. In that case, the child class must
    override this method or it raises an exception.

    @value - The value to promote to the monadic type.
    @return - The monadic type value.
    */
    protected function unit (/* mixed */ $value) /* : type */ {
        throw new Exception ('Your monad must implement monad->unit () in order for your monadic code to call unit ().');
    }

    /* Monad->unit2
    Returns the input monadic type value.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the monadic code calls the globally defined unit2 () function.
    This is a default implementation that can be overridden in a child class.

    @value - The monadic type value.
    @return - The monadic type value.
    */
    protected function unit2 (/* type */ $value) /* : type */ {
        return $value;
    }
    
    /* Monad->bind
    Binds the contents of the input monadic type value for the rest of the monadic code.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the monadic code calls the globally defined bind () function. In that case, the child class must
    override this method or it raises an exception.

    @result - The input monadic type value whose contents should be bound.
    @rest - A function that represents the remaining monadic code.
        @value - The contents of @result.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    protected function bind (/* type */ $result, callable $rest) /* : type */ {
        throw new Exception ('Your monad must implement Monad->bind () in order for your monadic code to call bind ().');
    }

    /* Monad->monad_do
    Processes the input monadic type value. Typically, this means the value is used in a side effect, or is used to determine
    whether to continue running the monadic code.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the monadic code calls the globally defined monad_do () function. In that case, the child class
    must override this method or it raises an exception.

    @result - The input monadic type value.
    @rest - A function that represents the remaining monadic code.
        @return - The result of running the rest of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    protected function monad_do (/* type */ $result, callable $rest) /* : type */ {
        throw new Exception ('Your monad must implement Monad->monad_do () in order for your monadic code to call monad_do ().');
    }

    /* Monad->zero
    Returns a default monadic type value.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the last statement in the the monadic code does not call unit () or unit2 (). In that case, the
    child class must override this method or it raises an exception.

    @return - A monadic type value.
    */
    protected function zero () /* : type */ {
        throw new Exception ('Either (1) the last statement in the monadic code must be either unit () or unit2 (), or (2) your monad must implement Monad->zero ().');
    }

    /* Monad->combine
    Returns the result of combining two monadic type values.

    Example usage:
    This function is for internal use only.

    Remarks:
    This method is called when the monadic code calls unit () more than once, calls unit2 () more than once, or calls unit () and
    unit2 () at least once each. In these cases, the child class must override this method or it raises an exception.
    
    @value1 - The first monadic type value to combine.
    @value2 - If the child class implements Monad->delay (), @value2 is the return value of Monad->delay (), which is either a
    function that represents the rest of the monadic code, or the result of running the rest of the monadic code. If the child
    class does not implement Monad->delay (), @value2 is the result of running the rest of the monadic code.
        If @value2 is a function, its signature is as follows.
        @return - The result of running the rest of the monadic code.
    @return - The combined monadic type value.
    */
    /* We cannot have a default implementation of this method because we use method_exists to see whether it is implemented in
    the child class. We also do not define it as abstract because we do not want to require the child class to implement it. */
//    protected function combine (/* type */ $value1, /* type or callable */ $value2) /* : type */ {}
    
    /* Monad->delay
    Delays the running of monadic code.
    
    Example usage:
    This function is for internal use only.

    Remarks:
    This method is only called if the child class implements it.
    
    @f - A function that represents the remaining monadic code.
        @return - The result of running the rest of the monadic code.
    @return - Either @f or its return value, depending on whether this function runs @f.
    */
    /* We cannot have a default implementation of this method because we use method_exists to see whether it is implemented in
    the child class. We also do not define it as abstract because we do not want to require the child class to implement it. */
//    protected function delay (callable $f) /* : type or callable */ {}
    
    /* Monad->run
    Runs delayed monadic code.
    
    Example usage:
    This function is for internal use only.

    Remarks:
    This method is only called if the child class implements it.
    
    @f - If the child class implements Monad->delay (), @f is the return value of Monad->delay (), which is either a function
    that represents the monadic code, or the result of running the monadic code. If the child class does not implement
    Monad->delay (), @f is the result of running the monadic code.
        If @f is a function, its signature is as follows.
        @return - The result of running the monadic code.
    @return - Either @f or its return value, depending on whether @f is a function and whether this function runs @f.
    */
    /* We cannot have a default implementation of this method because we use method_exists to see whether it is implemented in
    the child class. We also do not define it as abstract because we do not want to require the child class to implement it. */
//    protected function run (/* type or callable */ $f) /* : type or callable */ {}

    /*
    If the child class does not implement Monad->delay () or Monad->run (), the result of Monad->monad_eval () is simply:
    eval (<monadic code>)

    If the child class implements Monad->delay () but not Monad->run (), the result of Monad->monad_eval () is:
    Monad->delay (function () { eval (<monadic code>); })

    If the child class implements Monad->delay () and Monad->run (), the result of Monad->monad_eval () is:
    Monad->run (Monad->delay (function () { eval (<monadic code>); }))

    If the child class implements Monad->run () but not Monad->delay (), the result of Monad->monad_eval () is:
    Monad->run (eval (<monadic code>))
    
    If the child class implements Monad->delay (), the second parameter to Monad->combine () is the return value of
    Monad->delay ().

    For example, suppose the child class implements Monad->delay () as follows.
    protected function delay (callable $f) : callable {
        return $f;
    }
    Suppose the child class runs the following monadic code.
        unit (1);
        unit (2);
        unit (3);
    The result of the monadic code is as follows.
        Monad->delay (function () {
            return Monad->combine (1, Monad->delay (function () {
                return Monad->combine (2, Monad->delay (function () {
                    return 3;
                }));
            }));
        })
    This allows the implementation of Monad->combine () to skip running the rest of the monadic code if needed, by simply not
    calling the function that is passed to it as the second parameter.
    */

    /* Monad->get_result
    Returns the result of evaluating the specified monadic code.

    Example usage:
    This function is for internal use only.

    Remarks:
    We prefix our parameter and local variable names with '_' to help avoid collisions with those in @_context. $_eval_result
    also appears in the local scope even though it is declared as global.

    @_code - The monadic code to evaluate.
    @_rest - The remaining monadic code.
    @_context - The variables defined in the scope of the monadic code.
    @_return - The result of evaluating the monadic code.
    */
    private function get_result (string $_code, array $_rest, array $_context) : EvalResult {
        /* We declare this as global so it is visible to the unit (), unit2 (), bind (), and monad_do () functions. */
        global $_eval_result;
        /* Clear the global variable so its value does not persist between calls to this function. */
        $_eval_result = new Maybe (false, null);
        /* Add the existing variables to the local scope, in which the monadic code is evaluated. */
        extract ($_context);
        /* Evaluate the monadic code. */
        eval ($_code);
        /* If the monadic code called unit (), unit2 (), bind (), or monad_do (), return the result. */
        if (true === $_eval_result->is_some) {
            return $_eval_result->value;
        }
        else {
            /* Get the defined variables. The monadic code might have added or reassigned variables. */
            $_context = get_defined_vars ();
            /* Remove the parameter names from the defined variables. Otherwise, when we extract the defined variables in the
            next call to this function, we will overwrite the new parameter values with the old values. */
            unset ($_context['_code']);
            unset ($_context['_rest']);
            unset ($_context['_context']);
            return new NoResult ($_context);
        }
    }

    /* Monad->unit_helper_2
    Processes the result of a call to Monad->unit () or Monad->unit2 ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if the child class does not implement Monad->combine ().

    @value - The result returned from Monad->unit () or Monad->unit2 ().
    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function unit_helper_2 (/* type */ $value, array $rest, array $context) /* : type */ {
        if (false === $this->_is_combine_implemented) {
            throw new Exception ('Either (1) your monadic code must call unit () or unit2 () only once, as the last statement, or (2) your monad must implement Monad->combine ().');
        }
        else {
            if (true === $this->_is_delay_implemented) {
                $result = $this->delay (function () use ($rest, $context) {
                    return $this->eval_helper ($rest, $context);
                });
            }
            else {
                $result = $this->eval_helper ($rest, $context);
            }
            return $this->combine_helper ($this->combine ($value, $result));
        }
    }
    
    /* Monad->unit_helper
    Processes the result of a call to Monad->unit () or Monad->unit2 ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if @value does not match the monadic type for the monad that implements this method.
    
    @value - The result returned from Monad->unit () or Monad->unit2 ().
    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function unit_helper (/* type */ $value, array $rest, array $context) /* : type */ {
/* TODO2 This function is called by eval_last_statement only for this type check. This type check should be moved to a separate
function. */
        if (false === is_obj_type ($value, $this->type)) {
            throw new Exception (sprintf ('Monad->unit () or Monad->unit2 () returned a result of an unrecognized type. Expected type: %s. Actual type: %s.', $this->type, get_type ($value)));
        }
        else {
            if (count ($rest) === 0) {
                return $value;
            }
            else {
                return $this->unit_helper_2 ($value, $rest, $context);
            }
        }
    }
    
    /* Monad->bind_helper
    When the monadic code calls bind (), this function maps the function call to the monad's implementation of Monad->bind ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if @value does not match the monadic type for the monad that implements this method.

    @name - The variable name to bind.
    @value - The monadic type value whose contents should be bound to @name.
    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function bind_helper (string $name, /* mixed */ $value, array $rest, array $context) /* : type */ {
        if (false === is_obj_type ($value, $this->type)) {
            throw new Exception (sprintf ('Monad->bind () was applied to a value of an unrecognized type. Expected type: %s. Actual type: %s.', $this->type, get_type ($value)));
        }
        else {
            /* Monad->bind extracts the contents of @value and passes the contents as @bound_value to the anonymous function. */
            return $this->bind ($value, function ($bound_value) use ($name, $rest, $context) {
                /* Add the variable to the context. */
                $context = ArrayUtils::append ($context, $name, $bound_value);
                return $this->eval_helper ($rest, $context);
            });
        }
    }

    /* Monad->do_helper
    When the monadic code calls monad_do (), this function maps the function call to the monad's implementation of
    Monad->monad_do ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if @value does not match the monadic type for the monad that implements this method.

    @value - The input monadic type value.
    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function do_helper (/* mixed */ $value, array $rest, array $context) /* : type */ {
        if (false === is_obj_type ($value, $this->type)) {
            throw new Exception (sprintf ('Monad->monad_do () was applied to a value of an unrecognized type. Expected type: %s. Actual type: %s.', $this->type, get_type ($value)));
        }
        else {
            return $this->monad_do ($value, function () use ($rest, $context) {
                return $this->eval_helper ($rest, $context);
            });
        }
    }

    /* Monad->zero_helper
    Processes the result of a call to Monad->zero ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if @value does not match the monadic type for the monad that implements this method.

    @value - The result returned from Monad->zero ().
    @return - @value.
    */
    private function zero_helper (/* type */ $value) /* : type */ {
        if (false === is_obj_type ($value, $this->type)) {
            throw new Exception (sprintf ('Monad->zero () returned a result of an unrecognized type. Expected type: %s. Actual type: %s.', $this->type, get_type ($value)));
        }
        else {
            return $value;
        }
    }

    /* Monad->combine_helper
    Processes the result of a call to Monad->combine ().

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception if @value does not match the monadic type for the monad that implements this method.

    @value - The result returned from Monad->combine ().
    @return - @value.
    */
    private function combine_helper (/* type */ $value) /* : type */ {
        if (false === is_obj_type ($value, $this->type)) {
            throw new Exception (sprintf ('Monad->combine () returned a result of an unrecognized type. Expected type: %s. Actual type: %s.', $this->type, get_type ($value)));
        }
        else {
            return $value;
        }
    }

    /* Monad->eval_dispatch
    Dispatches the result of evaluating a statement in the monadic code to the appropriate handler.
    
    Example usage:
    This function is for internal use only.
    
    Remarks:
    Raises an exception if @result type is unknown.
    
    @result - The result of evaluating a statement in the monadic code.
    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function eval_dispatch (EvalResult $result, array $rest, array $context) /* : type */ {
        switch (get_class ($result)) {
            case 'NoResult':
                return $this->eval_helper ($rest, $result->context);
            case 'UnitResult':
                return $this->unit_helper ($this->unit ($result->value), $rest, $context);
            case 'Unit2Result':
                return $this->unit_helper ($this->unit2 ($result->value), $rest, $context);
            case 'BindResult':
                return $this->bind_helper ($result->name, $result->value, $rest, $context);
            case 'DoResult':
                return $this->do_helper ($result->value, $rest, $context);
            default:
                /* get_class returns false if applied to a value that is not an object. So this exception is raised if either
                (1) $result is not an object or (2) $result is an object of an unrecognized type. However, this should never
                happen. */
                throw new Exception (sprintf ('Monad->eval_dispatch () was applied to a statement result of an unrecognized type: %s.', get_type ($result)));
        }
    }
    
    /* Monad->eval_last_statement
    Dispatches the result of evaluating the last statement in the monadic code to the appropriate handler.

    Example usage:
    This function is for internal use only.

    Remarks:
    None.

    @result - The result of evaluating a statement in the monadic code.
    @return - The return value from the handler for @result.
    */
    private function eval_last_statement (EvalResult $result) /* : type */ {
        if (true === is_obj_type ($result, 'UnitResult')) {
            return $this->unit_helper ($this->unit ($result->value), array (), array ());
        }
        else if (true === is_obj_type ($result, 'Unit2Result')) {
            return $this->unit_helper ($this->unit2 ($result->value), array (), array ());
        }
        else {
            return $this->zero_helper ($this->zero ());
        }
    }
    
    /* Monad->eval_helper
    Gets the next statement in the monadic code, evaluates it, and recurses with the remaining monadic code.

    Example usage:
    This function is for internal use only.

    Remarks:
    Raises an exception is @rest is empty.

    @rest - The remaining monadic code.
    @context - The variables defined in the scope of the monadic code.
    @return - The result of running the rest of the monadic code.
    */
    private function eval_helper (array $rest, array $context) /* : type */ {
        $code = ArrayUtils::head_value ($rest);
        $rest = array_slice ($rest, 1);
        if (false === $code->is_some) {
            throw new Exception ('Monad->eval_helper () was applied to an empty monadic code list.');
        }
        else {
            $result = $this->get_result ($code->value, $rest, $context);
            if (count ($rest) > 0) {
                return $this->eval_dispatch ($result, $rest, $context);
            }
            else {                
                return $this->eval_last_statement ($result);
            }
        }
    }

    /* Monad->monad_eval
    Runs the specified monadic code and returns the result.

    Example usage:
        $m = new MaybeMonad ();
        $code = 'unit (1)';
        $result = $m->monad_eval ($code);
        // $result->is_some === true
        // $result->value === 1

    Remarks:
    None.

    @code - The monadic code.
    @context - The variables defined in the scope of the monadic code.
    @result - The result of running the monadic code.
    */
    public function monad_eval (string $code, array $context = array ()) /* : type */ {
        $parser = get_parser (outer_wrapper, inner_wrapper);
        $code = $parser ($code);
        $code = eval ($code);

        if (false === $this->_is_delay_implemented) {
            $result = $this->eval_helper ($code, $context);
        }
        else {
            $result = $this->delay (function () use ($code, $context) {
                return $this->eval_helper ($code, $context);
            });
        }        
        if (false === $this->_is_run_implemented) {
            return $result;
        }
        else {
            return $this->run ($result);
        }
    }
    
    public function __construct (string $type) {
        $this->_is_combine_implemented = method_exists ($this, 'combine');
        $this->_is_delay_implemented = method_exists ($this, 'delay');
        $this->_is_run_implemented = method_exists ($this, 'run');
        if (true === $this->_is_combine_implemented &&
            false === $this->_is_delay_implemented) {
            throw new Exception ("If your monad implements Monad->combine (), it must also implement Monad->delay ().");
        }
        $this->type = $type;
    }
}

?>