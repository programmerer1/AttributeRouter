<?php
declare(strict_types=1);

namespace AttributeRouter;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public array  $methods = ['GET'],
        public string $name = '',
        public array  $patterns = [],
        public array  $locales = [],
        public string $groupPath = '',
    )
    {
    }
}