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

class ConsultarResponse {

	public $consultarReturn = null;
	public $tracking = null;
	private $error = null;

	public function __construct($consultarReturn) {
		$this->consultarReturn = $consultarReturn;
	}

	public function xmlToObject() {
		$xml = simplexml_load_string($this->consultarReturn);
		$this->tracking = $xml->Jadlog_Tracking_Consultar;

		if (isset($this->tracking->Retorno) && $this->tracking->Retorno == '-1') {
			$this->error = $this->tracking->Mensagem->__toString();
		}
		$this->consultarReturn = null;

		return $this;
	}

	public function hasError() {
		if (!is_null($this->error)) {
			return true;
		}

		return false;
	}

	public function getError() {
		return $this->error;
	}
}
