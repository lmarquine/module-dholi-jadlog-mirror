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

class Calculo {

	private $codigo;

	private $nome;

	private $alerta;

	private $localidade;

	private $tarifa;

	private $prazo;

	private $valor;

	public function __construct($codigo, $nome, $alerta, $localidade, $tarifa, $prazo, $valor) {
		$this->codigo = $codigo;
		$this->nome = $nome;
		$this->alerta = $alerta;
		$this->localidade = $localidade;
		$this->tarifa = $tarifa;
		$this->prazo = $prazo;
		$this->valor = floatval(str_replace(',', '.', (string)$valor));
	}

	public function getCodigo() {
		return $this->codigo;
	}

	public function getNome() {
		return $this->nome;
	}

	public function getAlerta() {
		return $this->alerta;
	}

	public function getLocalidade() {
		return $this->localidade;
	}

	public function getTarifa() {
		return $this->tarifa;
	}

	public function canShow() {
		if ($this->getPrazo() != null && $this->getValor() > 0) {
			return true;
		}

		return false;
	}

	public function getPrazo() {
		return $this->prazo;
	}

	public function getValor() {
		return $this->valor;
	}
}