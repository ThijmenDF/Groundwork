<?php

namespace Groundwork\Validator;

use Groundwork\Exceptions\AssertFailedException;
use Groundwork\Exceptions\ValidationFailedException;
use Groundwork\Injector\Injection;
use Groundwork\Request\Request;
use Groundwork\Utils\Table;

class Validator implements Injection
{

    /**
     * An associative array of testing rules.
     *
     * The following format can be used:
     * ```
     * public array $rules = [
     *     'username' => [
     *         'required',
     *         'length:5'
     *     ],
     *     'file' => [
     *         'required',
     *         'image'
     *     ],
     *     'new-password' => [
     *         'nullable',
     *         'min:6'
     *     ]
     *     'email' => [
     *         'required',
     *         'email'
     *     ]
     * ];
     * ```
     * Note that some rules have (optional) parameters. These parameters can be separated by using a column `:`.
     *
     * Some rules will stop the check of consequent rules if the value passes a certain check. An example is 'nullable',
     * which will stop any remaining rules from being applied to the value if the value is null.
     *
     * @var array
     */
    public array $rules = [];

    /**
     * Whether this validator needs to return to the previous route if any validation rules failed.
     *
     * @var bool
     */
    public bool $returnOnFailure = false;

    /**
     * An associative array of AssertFailedExceptions.
     *
     * @var array
     */
    protected array $failures = [];

    /**
     * An associative array of all input fields that passed their validation rules.
     *
     * @var array
     */
    protected array $validated = [];

    /**
     * The request object.
     *
     * @var Request
     */
    protected Request $request;

    /**
     * If the rules have been processed and the result of it.
     *
     * @var bool|null
     */
    protected ?bool $processed = null;

    /**
     * Initialize the Validator. It takes the Request object from the request() method.
     */
    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Attempts to validate the request data based on this class' rules.
     * 
     * @return bool
     */
    public function validate() : bool
    {
        if (!is_null($this->processed)) {
            return $this->processed;
        }

        $rules = table($this->rules);
        $ok = true;

        $rules->every(function($rules, $key) use(&$ok) {

            $item = $this->request->input($key);

            if (is_null($item) && $this->request->hasFile($key)) {
                $item = $this->request->file($key);
            }

            $asserter = new Asserter($rules);

            try {
                $asserter->process($item, $key);
            } catch (AssertFailedException $exception) {
                $this->failures[$key] = $exception;
                $ok = false;
            }

            $this->validated[$key] = $item;
        });

        $this->updateSession(
            table($this->failed())->transform(fn(AssertFailedException $item) => $item->getMessage())->all()
        );

        $this->processed = $ok;
        return $ok;
    }

    /**
     * Returns a table with the validated data, or null if validation failed at any point.
     * 
     * @return Table|null
     */
    public function validated() : ?Table
    {
        if ($this->validate()) {
            return table($this->validated);
        }

        return null;
    }

    /**
     * Returns the list of items that failed to pass the validator.
     *
     * @return array
     */
    public function failed() : array
    {
        return $this->failures;
    }

    /**
     * Allows a Validator to be initialized through dependency injection.
     *
     * @param mixed $param
     *
     * @return Injection
     * @throws ValidationFailedException
     */
    public static function __inject($param) : Injection
    {
        $instance = new static;

        $ok = $instance->validate();

        if (! $ok && $instance->returnOnFailure) {
            // return to the previous screen
            throw new ValidationFailedException;
        }

        return $instance;
    }

    /**
     * Adds the failures and old values to the flash session.
     *
     * @param array $failures
     */
    private function updateSession(array $failures)
    {
        // set failures
        $this->request->session()->getFlashBag()->set('errors', $failures);

        // set previous values
        $this->request->flash();
    }
}