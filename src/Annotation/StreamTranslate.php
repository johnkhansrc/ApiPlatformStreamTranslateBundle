<?php

namespace Johnkhansrc\ApiPlatformStreamTranslateBundle\Annotation;

/**
 * @Annotation
 */
class StreamTranslate
{
    public string $domain;

    public ?string $key;

    public bool $childs = false;
}
