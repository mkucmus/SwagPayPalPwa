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
 * @Route("/sales-channel-api/v{version}/pwa/plugin")
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
     * @Route("/paypal/client-id", name="sales-channel-api.paypal.pwa", methods={"POST"})
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
     * @Route("/paypal/create-order", name="sales-channel-api.pwa.plugin.paypal.spb.create-order", methods={"POST"})
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
}
