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
use Sylius\RefundPlugin\Model\UnitRefundInterface;

interface RefundUnitsConverterInterface
{
    /**
     * @template T of UnitRefundInterface
     *
     * @param class-string<T> $unitRefundClass
     *
     * @return Collection<T>
     */
    public function convert(array $units, string $unitRefundClass): Collection;
}
