<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

interface Generator_Aware_Interface
{
    public function set_generator(Generator $generator);
}