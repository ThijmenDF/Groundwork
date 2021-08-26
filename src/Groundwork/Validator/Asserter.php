<?php

namespace Groundwork\Validator;

use Groundwork\Exceptions\AssertFailedException;
use Groundwork\Utils\Str;

class Asserter 
{

    protected array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Asserts the list of rules on the given value.
     * 
     * @param mixed $value
     * @param string $key
     * 
     * @throws AssertFailedException
     */
    public function process(&$value, string $key)
    {
        foreach ($this->rules as $ruleName) {

            $ruleName = table(explode(':', $ruleName));

            $rule = 'Groundwork\\Validator\\Rules\\' . Str::studly($ruleName->first()) . 'Rule';

            $rule = new $rule;

            $params = $ruleName->skip()->values()->all();

            if (!$rule->passes($value, $params)) {
                $message = $rule->getErrorMessage($value, $params);

                throw new AssertFailedException($message);
            }

            if ($rule instanceof TransformsValue) {
                $value = $rule->transform($value, $params);
            }

            if ($rule instanceof BreaksCycle) {
                if ($rule->breakIf($value, $params)) {
                    break;
                }
            }

        }
    }
}