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

class Evento {

	private $codigo;

	private $dataHoraEvento;

	private $descricao;

	private $observacao;

	public function __construct($codigo, $dataHoraEvento, $descricao, $observacao) {
		$this->codigo = trim($codigo);
		$this->dataHoraEvento = trim($dataHoraEvento);
		$this->descricao = trim($descricao);
		$this->observacao = trim($observacao);
	}

	public function getCodigo() {
		return $this->codigo;
	}

	public function getDataHoraEvento() {
		return $this->dataHoraEvento;
	}

	public function getDescricao() {
		if (array_key_exists($this->descricao, self::$statusList)) {
			return self::$statusList[$this->descricao];
		}
		return $this->descricao;
	}

	public function getObservacao() {
		return $this->observacao;
	}

}
