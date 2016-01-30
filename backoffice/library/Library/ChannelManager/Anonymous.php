<?php

namespace Library\ChannelManager;

class Anonymous extends \stdClass {
	protected $methods = [];
	protected $properties = [];

	public function __construct(array $options = []) {
		if (count($options)) {
			foreach ($options as $key => $opt) {
				if (is_array($opt) || is_scalar($opt) || is_null($opt)) {
					$this->properties[$key] = $opt;

					unset($options[$key]);
				}
			}

			$this->methods = $options;

			foreach ($this->properties as $k => $value) {
				$this->{$k} = $value;
			}
		}
	}

	public function __call($name, $arguments) {
		$callable = null;

		if (array_key_exists($name, $this->methods)) {
			$callable = $this->methods[$name];
		} elseif (isset($this->$name)) {
			$callable = $this->$name;
		}

		if (!is_callable($callable)) {
			throw new \BadMethodCallException("Method {$name} does not exists");
		}

		return call_user_func_array($callable, $arguments);
	}
}
