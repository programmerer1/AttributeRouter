<?php

declare(strict_types=1);

namespace AttributeRouter;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteGroup
{
    public function __construct(public string $path = '', public int $priority = 0) {}
}
