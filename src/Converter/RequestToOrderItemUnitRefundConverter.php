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

use Doctrine\Common\Collections\Collection;
use Sylius\RefundPlugin\Model\OrderItemUnitRefund;
use Symfony\Component\HttpFoundation\Request;

final class RequestToOrderItemUnitRefundConverter implements RequestToRefundUnitsConverterInterface
{
    private RefundUnitsConverterInterface $refundUnitsConverter;

    public function __construct(RefundUnitsConverterInterface $refundUnitsConverter)
    {
        $this->refundUnitsConverter = $refundUnitsConverter;
    }

    public function convert(Request $request): Collection
    {
        return $this->refundUnitsConverter->convert(
            $request->request->has('sylius_refund_units') ? $request->request->all()['sylius_refund_units'] : [],
            OrderItemUnitRefund::class
        );
    }
}
