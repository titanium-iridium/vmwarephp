<?php

namespace Vmwarephp;

use Vmwarephp\Exception as Ex;

class Vhost {
	protected $service;
	protected $options;
	protected $defaultOptions = [
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'vmware',
		'soap' => [],
		'cache_path' => null,
	];

	function __construct($options) {
		$this->options = array_merge($this->defaultOptions, $options);
	}

	function getOptions() {
		return $this->options;
	}

	function getPort() {
		$port = parse_url($this->options['host'], PHP_URL_PORT);
		return $port ? $port : '443';
	}

	function __get($propertyName) {
		if (isset($this->options[$propertyName])) {
			return $this->options[$propertyName];
		}
		throw new \InvalidArgumentException('Property ' . $propertyName . ' not set on this object!');
	}

	function __set($propertyName, $value) {
		$this->validateProperty($propertyName, $value);
		$this->options[$propertyName] = $value;
	}

	function __call($method, $arguments) {
		if (!$this->service) {
			$this->initializeService();
		}
		return call_user_func_array(array($this->service, $method), $arguments);
	}

	function getApiType() {
		return $this->getServiceContent()->about->apiType;
	}

	function changeService(\Vmwarephp\Service $service) {
		$this->service = $service;
	}

	private function initializeService() {
		if (!$this->service) {
			$this->service = \Vmwarephp\Factory\Service::makeConnected($this);
		}
	}

	private function validateProperty($propertyName, $value) {
		if (in_array($propertyName, array_keys($this->defaultOptions)) && empty($value)) {
			throw new Ex\InvalidVhost('Vhost ' . ucfirst($propertyName) . ' cannot be empty!');
		}
	}
}
