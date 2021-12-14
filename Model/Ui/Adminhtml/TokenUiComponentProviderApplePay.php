<?php

declare(strict_types=1);

namespace Swarming\SubscribeProAdyen\Model\Ui\Adminhtml;

use Adyen\Payment\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class TokenUiComponentProviderApplePay implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var Data
     */
    private $adyenHelper;

    /**
     * TokenUiComponentProvider constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Data $adyenHelper
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Data $adyenHelper
    ) {
        $this->componentFactory = $componentFactory;
        $this->adyenHelper = $adyenHelper;
    }

    /**
     * @inheritdoc
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $details = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $details['icon'] = $this->adyenHelper->getVariantIcon($details['type']);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => 'adyen_apple_pay_vault',
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $details,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    'template' => 'Adyen_Payment::form/vault.phtml'
                ],
                'name' => Template::class
            ]
        );
        return $component;
    }
}
