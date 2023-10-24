<?php

declare(strict_types=1);

namespace Swarming\SubscribeProAdyen\Observer\Payment;

use Adyen\Payment\Model\Ui\AdyenCcConfigProvider;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use SubscribePro\Service\Transaction\TransactionInterface;

class TokenAssigner extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;
    private \Psr\Log\LoggerInterface $logger;

    /**
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $paymentMethodToken = $additionalData['payment_method_token'] ?? null;

        if (empty($paymentMethodToken)) {
            if (!isset($additionalData['public_hash']) || !(isset($additionalData['customer_id']))) {
                return;
            }
            $paymentTokenObj = $this->paymentTokenManagement->getByPublicHash($additionalData['public_hash'], $additionalData['customer_id']);
            $paymentMethodToken = $paymentTokenObj->getGatewayToken();
            $this->logger->debug('SS PRO: TokenAssigner: paymentMethodToken: ' . $paymentMethodToken);
            if (empty($paymentMethodToken)) {
                $this->logger->debug('SS PRO: TokenAssigner: paymentMethodToken is empty');
                return;
            }
        }

        /** @var \Magento\Quote\Model\Quote\Payment $paymentModel */
        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof QuotePayment) {
            $this->logger->debug('SS PRO: TokenAssigner: paymentModel is not QuotePayment');
            return;
        }

        $quote = $paymentModel->getQuote();
        $customerId = $quote->getCustomer()->getId();
        if ($customerId === null) {
            $this->logger->debug('SS PRO: TokenAssigner: customerId is null');
            return;
        }

        $paymentToken = $this->getPaymentToken($paymentMethodToken, (int)$customerId);
        if ($paymentToken === null) {
            $this->logger->debug('SS PRO: TokenAssigner: paymentToken is null');
            return;
        }

        $paymentModel->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $customerId);
        $paymentModel->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $paymentToken->getPublicHash());

        if (!empty($additionalData[TransactionInterface::UNIQUE_ID])) {
            $this->logger->debug('SS PRO: TokenAssigner: uniqueId: ' . $additionalData[TransactionInterface::UNIQUE_ID]);
            $paymentModel->setAdditionalInformation(
                TransactionInterface::UNIQUE_ID,
                $additionalData[TransactionInterface::UNIQUE_ID]
            );
        }

        if (!empty($additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN])) {
            $this->logger->debug('SS PRO: TokenAssigner: orderToken: ' . $additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN]);
            $paymentModel->setAdditionalInformation(
                TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN,
                $additionalData[TransactionInterface::SUBSCRIBE_PRO_ORDER_TOKEN]
            );
        }
    }

    /**
     * @param string $paymentMethodToken
     * @param int $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface|null
     */
    protected function getPaymentToken(string $paymentMethodToken, int $customerId): ?PaymentTokenInterface
    {
        return $this->paymentTokenManagement->getByGatewayToken(
            $paymentMethodToken,
            AdyenCcConfigProvider::CODE,
            $customerId
        );
    }
}
