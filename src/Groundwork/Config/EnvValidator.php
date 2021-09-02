<?php

namespace Groundwork\Config;

use Groundwork\Exceptions\EnvConfigurationException;

class EnvValidator
{
    protected string $name;
    protected bool $exists;
    protected $value;

    /** @var bool If no exception will be thrown if a validation fails. */
    protected bool $ignore = false;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->exists = Config::has($name);
        $this->value = Config::get($name);
    }

    /**
     * @return $this
     * @throws EnvConfigurationException
     */
    public function required() : self
    {
        if (!$this->exists) {
            $this->throw('is required');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function optional() : self
    {
        if (!$this->exists) {
            $this->ignore = true;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws EnvConfigurationException
     */
    public function boolean() : self
    {
        if (!in_array($this->value, [true, false, 1, 0, 'true', 'false', '1', '0'])) {
            $this->throw('must be a boolean');
        }

        return $this;
    }

    /**
     * @return $this
     * @throws EnvConfigurationException
     */
    public function integer() : self
    {
        if (!filter_var($this->value, FILTER_VALIDATE_INT)) {
            $this->throw('must be an integer');
        }

        return $this;
    }

    /**
     * @return $this
     * @throws EnvConfigurationException
     */
    public function number() : self
    {
        if (!is_numeric($this->value)) {
            $this->throw('must be a numeric value');
        }

        return $this;
    }

    /**
     * @return $this
     * @throws EnvConfigurationException
     */
    public function notEmpty() : self
    {
        if (is_null($this->value)) {
            $this->throw('cannot be empty');
        }

        return $this;
    }

    /**
     * @param array $values
     *
     * @return $this
     * @throws EnvConfigurationException
     */
    public function in(array $values) : self
    {
        if (!in_array($this->value, $values)) {
            $this->throw('must be one of: ' . implode(', ', $values));
        }

        return $this;
    }

    /**
     * @param string $reason
     *
     * @throws EnvConfigurationException
     */
    private function throw(string $reason = '')
    {
        if (!$this->ignore) {
            throw new EnvConfigurationException("'$this->name' $reason");
        }
    }
}