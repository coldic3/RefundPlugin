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

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\OrderItemUnitInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Entity\LineItem;
use Sylius\RefundPlugin\Entity\LineItemInterface;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Sylius\RefundPlugin\Model\UnitRefundInterface;
use Sylius\RefundPlugin\Provider\TaxRateProviderInterface;
use Webmozart\Assert\Assert;

final class OrderItemUnitLineItemsConverter implements LineItemsConverterInterface
{
    private RepositoryInterface $orderItemUnitRepository;

    private TaxRateProviderInterface $taxRateProvider;

    public function __construct(RepositoryInterface $orderItemUnitRepository, TaxRateProviderInterface $taxRateProvider)
    {
        $this->orderItemUnitRepository = $orderItemUnitRepository;
        $this->taxRateProvider = $taxRateProvider;
    }

    public function convert(array $units): array
    {
        $orderItemUnitRefunds = $this->filterUnits($units);

        $lineItems = [];

        /** @var OrderItemUnitRefund $unitRefund */
        foreach ($orderItemUnitRefunds as $unitRefund) {
            $lineItems = $this->addLineItem($this->convertUnitRefundToLineItem($unitRefund), $lineItems);
        }

        return $lineItems;
    }

    private function convertUnitRefundToLineItem(OrderItemUnitRefund $unitRefund): LineItemInterface
    {
        /** @var OrderItemUnitInterface|null $orderItemUnit */
        $orderItemUnit = $this->orderItemUnitRepository->find($unitRefund->id());
        Assert::notNull($orderItemUnit);
        Assert::lessThanEq($unitRefund->total(), $orderItemUnit->getTotal());

        /** @var OrderItemInterface $orderItem */
        $orderItem = $orderItemUnit->getOrderItem();

        $grossValue = $unitRefund->total();
        $taxAmount = (int) ($grossValue * $orderItemUnit->getTaxTotal() / $orderItemUnit->getTotal());
        $netValue = $grossValue - $taxAmount;

        /** @var string|null $productName */
        $productName = $orderItem->getProductName();
        Assert::notNull($productName);

        return new LineItem(
            $productName,
            1,
            $netValue,
            $grossValue,
            $netValue,
            $grossValue,
            $taxAmount,
            $this->taxRateProvider->provide($orderItemUnit)
        );
    }

    /**
     * @param LineItemInterface[] $lineItems
     *
     * @return LineItemInterface[]
     */
    private function addLineItem(LineItemInterface $newLineItem, array $lineItems): array
    {
        /** @var LineItemInterface $lineItem */
        foreach ($lineItems as $lineItem) {
            if ($lineItem->compare($newLineItem)) {
                $lineItem->merge($newLineItem);

                return $lineItems;
            }
        }

        $lineItems[] = $newLineItem;

        return $lineItems;
    }

    /** @return OrderItemUnitRefund[] */
    private function filterUnits(array $units): array
    {
        return array_values(array_filter($units, function (UnitRefundInterface $unitRefund): bool {
            return $unitRefund instanceof OrderItemUnitRefund;
        }));
    }
}
