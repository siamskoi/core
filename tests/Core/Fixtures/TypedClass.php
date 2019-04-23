<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);


namespace Spiral\Core\Tests\Fixtures;

class TypedClass
{
    public function __construct(
        string $string,
        int $int,
        float $float,
        bool $bool,
        array $array = [],
        string $pong = null
    ) {
    }
}