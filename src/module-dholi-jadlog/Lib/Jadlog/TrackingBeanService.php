<?php
/**
* 
* Frente com Jadlog para Magento 2
* 
* @category     Dholi
* @package      Modulo Frente com Jadlog
* @copyright    Copyright (c) 2019 dholi (https://www.dholi.dev)
* @version      1.0.0
* @license      https://www.dholi.dev/license/
*
*/
declare(strict_types=1);

namespace Dholi\Jadlog\Lib\Jadlog;

class TrackingBeanService extends \SoapClient {

	private static $classmap = array(
		'Consultar' => '\Dholi\Jadlog\Lib\Jadlog\Consultar',
		'ConsultarResponse' => '\Dholi\Jadlog\Lib\Jadlog\ConsultarResponse',
		'ConsultarPedido' => '\Dholi\Jadlog\Lib\Jadlog\ConsultarPedido',
		'ConsultarPedidoResponse' => '\Dholi\Jadlog\Lib\Jadlog\ConsultarPedidoResponse');

	public function __construct(array $options = array(), $wsdl = 'http://www.jadlog.com.br:8080/JadlogEdiWs/services/TrackingBean?wsdl') {
		foreach (self::$classmap as $key => $value) {
			if (!isset($options['classmap'][$key])) {
				$options['classmap'][$key] = $value;
			}
		}

		parent::__construct($wsdl, $options);
	}

	public function consultar(\Dholi\Jadlog\Lib\Jadlog\Consultar $parameters) {
		return $this->__soapCall('consultar', array($parameters));
	}

	public function consultarPedido(\Dholi\Jadlog\Lib\Jadlog\ConsultarPedido $parameters) {
		return $this->__soapCall('consultarPedido', array($parameters));
	}

}