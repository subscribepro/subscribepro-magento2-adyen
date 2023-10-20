<?php

declare(strict_types=1);

namespace Swarming\SubscribeProAdyen\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Adyen\Payment\Observer\AdyenCcDataAssignObserver as AdyenAssignObserver;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class AdyenCcDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    private $generalConfig;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    private $quoteHelper;

    /**
     * @var \Adyen\Payment\Helper\StateData
     */
    private $stateData;
    private \Psr\Log\LoggerInterface $logger;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Swarming\SubscribePro\Helper\Quote         $quoteHelper
     * @param \Adyen\Payment\Helper\StateData             $stateData
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper,
        \Adyen\Payment\Helper\StateData $stateData,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->generalConfig = $generalConfig;
        $this->quoteHelper = $quoteHelper;
        $this->stateData = $stateData;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $paymentInfo = $this->readPaymentModelArgument($observer);
        $quote = $paymentInfo->getQuote();

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode) || !$this->quoteHelper->hasSubscription($quote)) {
            $this->logger->debug('SS PRO: AdyenCcDataAssignObserver: Not enabled or no subscription');
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            $this->logger->debug('SS PRO: AdyenCcDataAssignObserver: No additional data or public hash found');
            return;
        }

        if (!empty($additionalData[PaymentTokenInterface::PUBLIC_HASH])) {
            $this->logger->debug('SS PRO: AdyenCcDataAssignObserver: additionalData: ' . print_r($additionalData, true));
            $this->logger->debug('SS PRO: AdyenCcDataAssignObserver: Public hash !empty');
            return;
        }

        // This works on Adyen 7.x
        $stateData = $this->stateData->getStateData((int)$paymentInfo->getData('quote_id'));
        $stateData['storePaymentMethod'] = true;

        $this->stateData->setStateData($stateData, (int)$paymentInfo->getData('quote_id'));

        $paymentInfo->setAdditionalInformation(AdyenAssignObserver::STORE_CC, true);
        $paymentInfo->setAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE, true);

        $additionalData[VaultConfigProvider::IS_ACTIVE_CODE] = true;
        $data->setData(PaymentInterface::KEY_ADDITIONAL_DATA, $additionalData);
    }
}
