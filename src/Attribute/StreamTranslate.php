<?php

namespace Johnkhansrc\ApiPlatformStreamTranslateBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class StreamTranslate
{
    public function __construct(
        public string $domain,
        public ?string $key = null,
        public bool $childs = false
    ){}
}
