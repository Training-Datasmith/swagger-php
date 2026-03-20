<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

trait Generator_Aware_Trait
{
    protected ?Generator $generator = null;
    public function set_generator(Generator $generator)
    {
        $this->generator = $generator;
        return $this;
    }
}