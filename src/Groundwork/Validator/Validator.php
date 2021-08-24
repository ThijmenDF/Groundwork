<?php

namespace Groundwork\Validator;

use Groundwork\Exceptions\ValidationFailedException;
use Groundwork\Injector\Injection;
use Groundwork\Utils\Table;
use Symfony\Component\HttpFoundation\Request;

class Validator implements Injection
{

    public array $rules = [];

    public bool $returnOnFailure = false;

    protected array $failures = [];

    protected array $validated = [];

    protected Request $request;

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
        $old = [];
        $ok = true;

        $rules->every(function($rules, $key) use(&$old, &$ok) {

            $item = $this->request->get($key);

            $old[$key] = $item;

            if (is_null($item)) {
                $item = $this->request->files->get($key);
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
            table($this->failed())->transform(fn(AssertFailedException $item) => $item->getMessage())->all(),
            $old
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
     * @param $param
     *
     * @return Injection
     * @throws ValidationFailedException
     */
    public static function __inject($param) : Injection
    {
        $instance = new static;

        $ok = $instance->validate();

        if ($instance->returnOnFailure && !$ok) {
            // return to the previous screen
            throw new ValidationFailedException;
        }

        return $instance;
    }

    private function updateSession(array $failures, array $old)
    {
        // create / load
        $session = session();

        $path = $this->request->getRequestUri();

        // set failures
        $oldFailures = $session->get('validation-failed', []);
        $oldFailures[$path] = $failures;
        $session->set('validation-failed', $oldFailures);

        // set previous values
        $oldOld = $session->get('validation-old', []);
        $oldOld[$path] = $old;
        $session->set('validation-old', $oldOld);

        // save
        $session->save();
    }
}