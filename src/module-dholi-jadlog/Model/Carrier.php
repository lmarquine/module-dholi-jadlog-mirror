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
use Dholi\Jadlog\Lib\GetModal\Api;
use Dholi\Jadlog\Lib\GetModal\Calculo;
use Dholi\Jadlog\Lib\GetModal\Response;
use Dholi\Jadlog\Lib\GetModal\Volume;
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
	private $volumes = [];

	private $rateErrorFactory;

	private $rateResultFactory;

	private $rateMethodFactory;

	private $trackFactory;

	private $trackErrorFactory;

	private $trackStatusFactory;

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

	private function check(RateRequest $request) {
		if (!$this->getConfigFlag('active')) {
			return false;
		}
		$this->_result = $this->rateResultFactory->create();
		$origCountry = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_COUNTRY_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$destCountry = $request->getDestCountryId();
		if ($origCountry != self::COUNTRY || $destCountry != self::COUNTRY) {
			$rate = $this->rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('002'));
			$this->_result->append($rate);

			return false;
		}
		$this->fromZip = $this->_scopeConfig->getValue(Shipment::XML_PATH_STORE_ZIP, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $request->getStoreId());
		$this->fromZip = str_replace(array('-', '.'), '', trim($this->fromZip));
		if (!preg_match('/^([0-9]{8})$/', $this->fromZip)) {
			$rate = $this->rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));
			$rate->setErrorMessage(Errors::getMessage('003'));
			$this->_result->append($rate);

			return false;
		}

		$comprimento = null;
		$altura = null;
		$largura = null;
		$peso = null;
		$preco = null;
		$qty = null;

		$widthAttr = $this->getConfigData('width');
		$heightAttr = $this->getConfigData('height');
		$lengthAttr = $this->getConfigData('length');
		$weightAttr = $this->getConfigData('weight');

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		foreach ($request->getAllItems() as $item) {
			$qty = null;
			if ($item->getProduct()->isVirtual()) {
				continue;
			}

			if ($item->getHasChildren()) {
				foreach ($item->getChildren() as $child) {
					if (!$child->getProduct()->isVirtual()) {
						$product = $objectManager->create('Magento\Catalog\Model\Product')->load($child->getProductId());

						$preco = ($item->getPrice() - $item->getDiscountAmount());

						// FIXME: rever
						$parentIds = null;//Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($productId);
						if (!$parentIds) {
							// FIXME: rever
							$parentIds = null;//Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($productId);

							if ($parentIds) {
								$parentProd = $objectManager->create('Magento\Catalog\Model\Product')->load($parentIds[0]);
								$comprimento = $parentProd->getData($widthAttr);
								$altura = $parentProd->getData($heightAttr);
								$largura = $parentProd->getData($widthAttr);
							}
						}

						$this->volumes[$item->getSku()] = new Volume($item->getSku(),
							$item->getQty(),
							$preco,
							$altura,
							$comprimento,
							$largura,
							$product->getData($weightAttr),
							'false');
					}
				}
			} else {
				$product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProductId());

				if (isset($this->volumes[$item->getSku()])) {
					$this->volumes[$item->getSku()]->peso = $product->getData($weightAttr);
				} else {
					$this->volumes[$item->getSku()] = new Volume($item->getSku(),
						$item->getQty(),
						($item->getPrice() - $item->getDiscountAmount()),
						$product->getData($heightAttr),
						$product->getData($widthAttr),
						$product->getData($widthAttr),
						$product->getData($weightAttr),
						'false');
				}
			}
		}

		$this->nVlAltura = $this->getConfigData('default_height');
		$this->nVlLargura = $this->getConfigData('default_width');
		$this->nVlComprimento = $this->getConfigData('default_length');
		$this->hasFreeMethod = $request->getFreeShipping();
		$this->_freeMethod = $this->getConfigData('servico_gratuito');
	}

	public function getCode($type, $code = null) {
		$codes = [
			'service' => [
				'jadlog_package' => __('Tabela Rodoviária'),
				'jadlog_com' => __('Tabela Expressa')
			],
			'front' => [
				'jadlog_package' => __('Rodoviário'),
				'jadlog_com' => __('Expresso')
			]
		];

		if (!isset($codes[$type])) {
			return false;
		} elseif (null === $code) {
			return $codes[$type];
		}

		if (!isset($codes[$type][$code])) {
			return false;
		} else {
			return $codes[$type][$code];
		}
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

		if ($this->check($request) === false) {
			return $this->_result;
		}
		$this->getQuotes();

		return $this->_result;
	}

	public function getAllowedMethods() {
		$allowedMethods = explode(',', $this->getConfigData('servico_codigo'));
		$methods = [];
		foreach ($allowedMethods as $k) {
			$methods[$k] = $this->getCode('service', $k);
		}
		return $methods;
	}

	private function getQuotes() {
		$data = ['transportadora_codigos_servicos' => $this->getConfigData('servico_codigo'),
			'cep_origem' => $this->fromZip,
			'cep_destino' => $this->toZip
		];

		$volumes = [];
		foreach ($this->volumes as $volume) {
			$volumes[] = array('sku' => $volume->sku,
				'quantidade' => $volume->quantidade,
				'valor' => $volume->valor,
				'altura' => ($volume->altura ? $volume->altura : $this->nVlAltura),
				'comprimento' => ($volume->comprimento ? $volume->comprimento : $this->nVlComprimento),
				'largura' => ($volume->largura ? $volume->largura : $this->nVlLargura),
				'peso' => round($volume->peso, 2),
				'agrupar' => $volume->agrupar);
		}
		$data['volumes'] = $volumes;

		$response = $this->consultar($data);
		if ($response->hasErrors() || $response->hasWarnings()) {
			$rate = $this->rateErrorFactory->create();
			$rate->setCarrier($this->_code);
			$rate->setCarrierTitle($this->getConfigData('title'));

			if ($response->hasErrors()) {
				$rate->setErrorMessage($response->getError());
			} else {
				$rate->setErrorMessage($response->getWarning());
			}
			$this->_result->append($rate);

			return $this->_result;
		}

		foreach ($response->listServices() as $s) {
			$this->appendService($s);
		}
	}

	private function appendService(Calculo $calculo) {
		$method = $calculo->getCodigo();
		$rate = $this->rateMethodFactory->create();
		$rate->setCarrier($this->_code);
		$rate->setCarrierTitle($this->getConfigData('title'));
		$rate->setMethod($method);

		$title = $this->getCode('front', $calculo->getCodigo());
		if ($this->getConfigData('prazo_entrega')) {
			$s = $this->getConfigData('mensagem_prazo_entrega');
			$title = sprintf($s, $title, intval($calculo->getPrazo() + $this->getConfigData('prazo_extra')));
		}
		$title = substr($title, 0, 255);
		$rate->setMethodTitle($title);

		$taxaExtra = $this->getConfigData('taxa_extra');
		if ($taxaExtra) {
			$v1 = floatval(str_replace(',', '.', (string)$this->getConfigData('taxa_extra_valor')));
			$v2 = $calculo->getValor();

			if ($taxaExtra == '2') {
				$rate->setPrice($v1 + $v2);
			} else if ($taxaExtra == '1') {
				$rate->setPrice($v2 + (($v1 * $v2) / 100));
			}
		} else {
			$rate->setPrice($calculo->getValor());
		}
		if ($this->hasFreeMethod) {
			if ($method == $this->_freeMethod) {
				$v1 = floatval(str_replace(',', '.', (string)$this->getConfigData('servico_gratuito_desconto')));
				$p = $rate->getPrice();
				if ($v1 > 0 && $v1 > $p) {
					$rate->setPrice(0);
				}
			}
		}
		$rate->setCost(0);

		$this->_result->append($rate);
	}

	private function consultar($data) {
		$request = array(
			'uri' => '/cotacao/consultar',
			'data' => $data
		);

		$response = null;
		$gmAccessKey = $this->getConfigData('gm_key');
		$gmAccessToken = $this->getConfigData('gm_token');

		try {
			$api = new Api($gmAccessKey, $gmAccessToken);
			$text = $api->post($request);

			$response = Response::getInstance()->prepare($text);
		} catch (Exception $e) {
			$response = Response::getInstance()->prepare(array('errors' => array($e->getMessage())));
			$this->logger->critical($e->getMessage());
		}

		return $response;
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