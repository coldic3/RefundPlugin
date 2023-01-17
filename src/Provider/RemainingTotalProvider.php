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

namespace Sylius\RefundPlugin\Provider;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RefundPlugin\Entity\RefundInterface;
use Sylius\RefundPlugin\Model\RefundTypeInterface;

final class RemainingTotalProvider implements RemainingTotalProviderInterface
{
    public function __construct(
        private RefundUnitTotalProviderInterface $refundUnitTotalProvider,
        private RepositoryInterface $refundRepository
    ) {
    }

    public function getTotalLeftToRefund(int $id, RefundTypeInterface $type): int
    {
        $unitTotal = $this->refundUnitTotalProvider->getRefundUnitTotal($id, $type);
        $refunds = $this->refundRepository->findBy(['refundedUnitId' => $id, 'type' => $type]);

        if (count($refunds) === 0) {
            return $unitTotal;
        }

        $refundedTotal = 0;
        /** @var RefundInterface $refund */
        foreach ($refunds as $refund) {
            $refundedTotal += $refund->getAmount();
        }

        return $unitTotal - $refundedTotal;
    }
}
