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

use Symfony\Component\HttpFoundation\Request;

final class RequestToRefundUnitsConverter implements RequestToRefundUnitsConverterInterface
{
    public function __construct(
        /** @var RequestToRefundUnitsConverterInterface[] $refundUnitsConverters */
        private iterable $refundUnitsConverters
    ) {
    }

    public function convert(Request $request): array
    {
        $units = [];

        foreach ($this->refundUnitsConverters as $refundUnitsConverter) {
            $units = array_merge($units, $refundUnitsConverter->convert($request));
        }

        return $units;
    }
}
