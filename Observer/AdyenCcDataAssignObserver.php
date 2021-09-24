<?php

declare(strict_types=1);

namespace Swarming\SubscribeProAdyen\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Adyen\Payment\Observer\AdyenCcDataAssignObserver as AdyenAssignObserver;

class AdyenCcDataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var \Swarming\SubscribePro\Model\Config\General
     */
    private $generalConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Swarming\SubscribePro\Helper\Quote
     */
    private $quoteHelper;

    /**
     * @param \Swarming\SubscribePro\Model\Config\General $generalConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Swarming\SubscribePro\Helper\Quote $quoteHelper
     */
    public function __construct(
        \Swarming\SubscribePro\Model\Config\General $generalConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Swarming\SubscribePro\Helper\Quote $quoteHelper
    ) {
        $this->generalConfig = $generalConfig;
        $this->checkoutSession = $checkoutSession;
        $this->quoteHelper = $quoteHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $quote = $this->checkoutSession->getQuote();

        $websiteCode = $quote->getStore()->getWebsite()->getCode();
        if (!$this->generalConfig->isEnabled($websiteCode) || !$this->quoteHelper->hasSubscription($quote)) {
            return;
        }

        $data = $this->readDataArgument($observer);
        $paymentInfo = $this->readPaymentModelArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)
            || empty($additionalData[AdyenAssignObserver::STATE_DATA])
        ) {
            return;
        }

        $stateData = $paymentInfo->getAdditionalInformation(AdyenAssignObserver::STATE_DATA);
        $stateData[AdyenAssignObserver::STORE_PAYMENT_METHOD] = true;

        $paymentInfo->setAdditionalInformation(AdyenAssignObserver::STATE_DATA, $stateData);
        $paymentInfo->setAdditionalInformation(AdyenAssignObserver::STORE_PAYMENT_METHOD, true);
        $paymentInfo->setAdditionalInformation(AdyenAssignObserver::STORE_CC, true);
    }
}
