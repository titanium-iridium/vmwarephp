<?php
namespace Vmwarephp\Factory;

use \Vmwarephp\Exception as Ex;
use Vmwarephp\WsdlClassMapper;
use Vmwarephp\Vhost;

class SoapClient {

	private $wsdlClassMapper;
	private $wsdlFilePath;

	function __construct(WsdlClassMapper $mapper = null, $wsdlFilePath = null) {
		$this->wsdlClassMapper = $mapper ?: new WsdlClassMapper;
		$this->wsdlFilePath = $wsdlFilePath ?: $this->getWsdlFilePath();
	}

	function make(Vhost $vhost) {
		$vhostOptions = $vhost->getOptions();

		$options = array_merge([
			'location' => $this->makeRequestsLocation($vhostOptions['host']),
			'trace' => true,
			'exceptions' => true,
			'connection_timeout' => 10,
			'classmap' => $this->wsdlClassMapper->getClassMap(),
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE,
		], $vhostOptions['soap']);

		if (!empty($options['stream_context'])) {
			$options['stream_context'] = stream_context_create($options['stream_context']);
		}

		$soapClient = $this->makeDefaultSoapClient($this->wsdlFilePath, $options);
		if (!$soapClient) {
			throw new Ex\CannotCreateSoapClient();
		}
		return $soapClient;
	}

	function getClientClassMap() {
		return $this->wsdlClassMapper->getClassMap();
	}

	protected function makeRequestsLocation($host) {
		return 'https://' . $host . '/sdk';
	}

	protected function makeDefaultSoapClient($wsdl, array $options) {
		return @new \Vmwarephp\SoapClient($wsdl, $options);
	}

	private function getWsdlFilePath() {
		return __DIR__ . '/../Wsdl/vimService.wsdl';
	}
}
