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

namespace Dholi\Jadlog\Lib\GetModal;

class Volume {

	public $sku;

	public $quantidade;

	public $valor;

	public $altura;

	public $comprimento;

	public $largura;

	public $peso;

	public $agrupar;

	public function __construct($sku, $quantidade, $valor, $altura, $comprimento, $largura, $peso, $agrupar) {
		$this->sku = $sku;
		$this->quantidade = $quantidade;
		$this->valor = $valor;
		$this->altura = $altura;
		$this->comprimento = $comprimento;
		$this->largura = $largura;
		$this->peso = $peso;
		$this->agrupar = $agrupar;
	}
}