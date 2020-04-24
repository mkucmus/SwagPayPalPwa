<?php declare(strict_types=1);

namespace Swag\PayPalPwa\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SPBCheckoutController;
use Swag\PayPal\Setting\Service\SettingsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"sales-channel-api"})
 * @Route("/sales-channel-api/v{version}/pwa/plugin/paypal")
 */
class PwaController extends AbstractController
{
    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * PwaController constructor.
     * @param SettingsService $settingsService
     */
    public function __construct(
        SettingsService $settingsService
    ) {
        $this->settingsService = $settingsService;
    }

    /**
     * Adds endpoint to get PayPal client ID which can vary depending on sales channel.
     * It's used by the PayPal SDK script loader in Vue component.
     *
     * @Route("/client-id", name="sales-channel-api.paypal.pwa", methods={"POST"})
     * @param SalesChannelContext $context
     * @return JsonResponse
     * @throws \Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException
     */
    public function getClientId(SalesChannelContext $context): JsonResponse
    {
        $settings = $this->settingsService->getSettings($context->getSalesChannel()->getId());
        $isSandboxMode = $settings->getSandbox();
        $clientId = $isSandboxMode ? $settings->getClientIdSandbox() : $settings->getClientId();

        return new JsonResponse([
            'clientId' => $clientId,
        ]);
    }

    /**
     * Forward request to the SPBCheckoutController::createPayment action.
     * This action is created only for creating the additional route to be reachable from shopware-6-client
     * (pluginGet, pluginPost) which are prefixed with "/sales-channel-api/v{version}/pwa/plugin"
     *
     * It's used by Smart Buttons to initiate the transaction. Returns token.
     *
     * @Route("/create-order", name="sales-channel-api.pwa.plugin.paypal.spb.create-order", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function createPayment(Request $request): JsonResponse
    {
        return $this->forward(
            sprintf('%s::createPayment', SPBCheckoutController::class),
            [
                $request,
            ]
        );
    }

    /**
     * Since the shopware-pwa won't be keeping the secrets after successful PayPal transaction
     * It should be kept on SW6 side, for instance in some context-related place.
     * The payerID and paymentID will be used during the placing an order just like storefront does at:
     * 1) /confirm page as query - /checkout/confirm?paypalPayerId=FECPL9K3F8NXS&paypalPaymentId=PAYID-L2RPOEQ80B17849187953048)
     * 2) then it's used on /checkout/order as a payload {
     *   isPayPalSpbCheckout: 1
     *   paypalPaymentId: PAYID-L2RPOEQ80B17849187953048
     *   paypalPayerId: FECPL9K3F8NXS
     * }
     * 3) and the last time at /checkout/finalize-transaction as query
     *  paymentId: PAYID-L2RPOEQ80B17849187953048
     *  PayerID: FECPL9K3F8NXS
     * isPayPalSpbCheckout: 1
     *
     * We need to keep it stick to the current context with possibility to change those values (in case of cart changes)
     * To avoid passing extra parameters in shopware-6-client / checkout / order services.
     *
     * @param Request $request
     * @param SalesChannelContext $context
     * @return JsonResponse
     */
    public function onApprove(Request $request, SalesChannelContext $context): JsonResponse
    {
        // @TODO: store the payerID and paymentID
        // if so, be aware that's the paypal payment-related checkout
        // adjust listeners/subscribers to be aware of it for sales-channel entrypoint.
    }
}
