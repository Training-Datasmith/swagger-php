<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Analysers;

use Composer\Autoload\Class_Loader;
/**
 * Scans for classes/interfaces/traits.
 *
 * Relies on a <code>composer --optimized</code> run in order to utilize
 * the generated class map.
 */
class Composer_Autoloader_Scanner
{
    /**
     * Collect all classes/interfaces/traits known by composer.
     *
     * @param list<string> $namespaces
     *
     * @return list<string>
     */
    public function scan(array $namespaces): array
    {
        $units = [];
        if ($autoloader = static::get_composer_autoloader()) {
            foreach (array_keys($autoloader->get_class_map()) as $unit) {
                foreach ($namespaces as $namespace) {
                    if (str_starts_with($unit, $namespace)) {
                        $units[] = $unit;
                        break;
                    }
                }
            }
        }
        return $units;
    }
    public static function get_composer_autoloader(): ?Class_Loader
    {
        foreach (spl_autoload_functions() as $fkt) {
            if (is_array($fkt) && $fkt[0] instanceof Class_Loader) {
                return $fkt[0];
            }
        }
        return null;
    }
}