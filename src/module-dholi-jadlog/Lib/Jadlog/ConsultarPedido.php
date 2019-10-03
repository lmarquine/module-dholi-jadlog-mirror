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

class ConsultarPedido {

	public $CodCliente = null;

	public $Password = null;

	public $NDs = null;

	public function __construct($CodCliente, $Password, $NDs) {
		$this->CodCliente = $CodCliente;
		$this->Password = $Password;
		$this->NDs = $NDs;
	}

}
