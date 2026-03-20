<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Open_Api\Analysis;
use Open_Api\Context;
use Open_Api\Generator_Aware_Interface;
interface Analyser_Interface extends Generator_Aware_Interface
{
    public function from_file(string $filename, Context $context): Analysis;
}