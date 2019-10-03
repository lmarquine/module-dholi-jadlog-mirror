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

namespace Dholi\Jadlog\Model;

use Dholi\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazo;
use Dholi\CorreiosFrete\Lib\CalcPrecoPrazo\CalcPrecoPrazoWS;
use Dholi\CorreiosFrete\Lib\CalcPrecoPrazo\Errors;
use Dholi\CorreiosFrete\Lib\Sro\BuscaEventos;
use Dholi\CorreiosFrete\Lib\Sro\Rastro;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrier implements CarrierInterface {

	const CODE = 'dholi_jadlog';
	const COUNTRY = 'BR';

	protected $_code = self::CODE;
	protected $_freeMethod = null;
	protected $_result = null;

	private $fromZip = null;
	private $toZip = null;
	private $hasFreeMethod = false;
	private $nVlComprimento = 0;
	private $nVlAltura = 0;
	private $nVlLargura = 0;

	private $rateErrorFactory;

	private $rateResultFactory;

	private $rateMethodFactory;

	private $trackFactory;

	private $trackErrorFactory;

	private $trackStatusFactory;

	private $packageValue;

	private $packageWeight;

	private $volumeWeight;

	private $freeMethodWeight;

	private $logger;

	public function __construct(ScopeConfigInterface $scopeConfig,
	                            ErrorFactory $rateErrorFactory,
	                            LoggerInterface $logger,
	                            ResultFactory $rateResultFactory,
	                            MethodFactory $rateMethodFactory,
	                            array $data = [],
	                            \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
	                            \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
	                            \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory) {
		parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

		$this->rateErrorFactory = $rateErrorFactory;
		$this->rateResultFactory = $rateResultFactory;
		$this->rateMethodFactory = $rateMethodFactory;
		$this->trackFactory = $trackFactory;
		$this->trackErrorFactory = $trackErrorFactory;
		$this->trackStatusFactory = $trackStatusFactory;
		$this->logger = $logger;
	}

	public function collectRates(RateRequest $request) {
		$this->toZip = $request->getDestPostcode();
		if (null == $this->toZip) {
			return $this->_result;
		}
		$this->toZip = str_replace(array('-', '.'), '', trim($this->toZip));
		$this->toZip = str_replace('-', '', $this->toZip);
		if (!preg_match('/^([0-9]{8})$/', $this->toZip)) {
			return $this->_result;
		}

		return $this->_result;
	}

	public function getAllowedMethods() {

	}

	public function isTrackingAvailable() {
		return true;
	}

	public function getTrackingInfo($tracking) {
		return $this->_getTracking($tracking);
	}

	private function _getTracking($trackingNumber) {

	}
}