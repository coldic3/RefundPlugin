<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\RefundPlugin\Converter;

use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Order\Model\AdjustableInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Entity\LineItem;
use Sylius\RefundPlugin\Entity\LineItemInterface;
use Sylius\RefundPlugin\Exception\MoreThanOneTaxAdjustment;
use Sylius\RefundPlugin\Model\ShipmentRefund;
use Sylius\RefundPlugin\Model\UnitRefundInterface;
use Sylius\RefundPlugin\Provider\TaxRateProviderInterface;
use Webmozart\Assert\Assert;

final class ShipmentLineItemsConverter implements LineItemsConverterInterface
{
    private RepositoryInterface $adjustmentRepository;

    private TaxRateProviderInterface $taxRateProvider;

    public function __construct(RepositoryInterface $adjustmentRepository, TaxRateProviderInterface $taxRateProvider)
    {
        $this->adjustmentRepository = $adjustmentRepository;
        $this->taxRateProvider = $taxRateProvider;
    }

    public function convert(array $units): array
    {
        $shipmentRefunds = $this->filterUnits($units);
        $lineItems = [];

        /** @var ShipmentRefund $shipmentRefund */
        foreach ($shipmentRefunds as $shipmentRefund) {
            $lineItems[] = $this->convertShipmentRefundToLineItem($shipmentRefund);
        }

        return $lineItems;
    }

    private function convertShipmentRefundToLineItem(ShipmentRefund $shipmentRefund): LineItemInterface
    {
        /** @var AdjustmentInterface|null $shippingAdjustment */
        $shippingAdjustment = $this
            ->adjustmentRepository
            ->findOneBy(['id' => $shipmentRefund->id(), 'type' => AdjustmentInterface::SHIPPING_ADJUSTMENT])
        ;
        Assert::notNull($shippingAdjustment);

        /** @var ShipmentInterface $shipment */
        $shipment = $shippingAdjustment->getShipment();
        Assert::notNull($shipment);
        Assert::isInstanceOf($shipment, AdjustableInterface::class);
        Assert::lessThanEq($shipmentRefund->total(), $shipment->getAdjustmentsTotal());

        $taxAdjustment = $this->getTaxAdjustment($shipment);
        $taxAdjustmentAmount = $taxAdjustment !== null ? $taxAdjustment->getAmount() : 0;

        $grossValue = $shipmentRefund->total();
        $taxAmount = (int) ($grossValue * $taxAdjustmentAmount / $shipment->getAdjustmentsTotal());
        $netValue = $grossValue - $taxAmount;

        /** @var string|null $label */
        $label = $shippingAdjustment->getLabel();
        Assert::notNull($label);

        return new LineItem(
            $label,
            1,
            $netValue,
            $grossValue,
            $netValue,
            $grossValue,
            $taxAmount,
            $this->taxRateProvider->provide($shipment)
        );
    }

    private function getTaxAdjustment(ShipmentInterface $shipment): ?AdjustmentInterface
    {
        $taxAdjustments = $shipment->getAdjustments(AdjustmentInterface::TAX_ADJUSTMENT);
        if ($taxAdjustments->isEmpty()) {
            return null;
        }

        if ($taxAdjustments->count() > 1) {
            throw MoreThanOneTaxAdjustment::occur();
        }

        /** @var AdjustmentInterface $taxAdjustment */
        $taxAdjustment = $taxAdjustments->first();

        return $taxAdjustment;
    }

    /** @return ShipmentRefund[] */
    private function filterUnits(array $units): array
    {
        return array_values(array_filter($units, function (UnitRefundInterface $unitRefund): bool {
            return $unitRefund instanceof ShipmentRefund;
        }));
    }
}
