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

namespace Sylius\RefundPlugin\Refunder;

use Sylius\RefundPlugin\Creator\RefundCreatorInterface;
use Sylius\RefundPlugin\Event\ShipmentRefunded;
use Sylius\RefundPlugin\Model\RefundType;
use Sylius\RefundPlugin\Model\ShipmentRefund;
use Sylius\RefundPlugin\Model\UnitRefundInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderShipmentsRefunder implements RefunderInterface
{
    private RefundCreatorInterface $refundCreator;

    private MessageBusInterface $eventBus;

    public function __construct(RefundCreatorInterface $refundCreator, MessageBusInterface $eventBus)
    {
        $this->refundCreator = $refundCreator;
        $this->eventBus = $eventBus;
    }

    public function refundFromOrder(array $units, string $orderNumber): int
    {
        $units = $this->filterShipmentRefunds($units);

        $refundedTotal = 0;

        /** @var ShipmentRefund $shipmentUnit */
        foreach ($units as $shipmentUnit) {
            $this->refundCreator->__invoke(
                $orderNumber,
                $shipmentUnit->id(),
                $shipmentUnit->total(),
                RefundType::shipment()
            );

            $refundedTotal += $shipmentUnit->total();

            $this->eventBus->dispatch(new ShipmentRefunded($orderNumber, $shipmentUnit->id(), $shipmentUnit->total()));
        }

        return $refundedTotal;
    }

    private function filterShipmentRefunds(array $units): array
    {
        return array_filter($units, fn (UnitRefundInterface $unitRefund) => $unitRefund instanceof ShipmentRefund);
    }
}
