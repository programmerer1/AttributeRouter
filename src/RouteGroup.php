<?php
declare(strict_types=1);

namespace AttributeRouter;

use Attribute;

#[Attribute]
class RouteGroup
{
    public function __construct(public string $path = '')
    {
    }
}