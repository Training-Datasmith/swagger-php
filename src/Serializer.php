<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api;

use Open_Api\Annotations as OA;
use Symfony\Component\Yaml\Yaml;
/**
 * Allows to serialize/de-serialize annotations from/to JSON.
 */
class Serializer
{
    private static array $VALID_ANNOTATIONS = [OA\Additional_Properties::class, OA\Attachable::class, OA\Components::class, OA\Contact::class, OA\Delete::class, OA\Discriminator::class, OA\Encoding::class, OA\Examples::class, OA\External_Documentation::class, OA\Flow::class, OA\Get::class, OA\Head::class, OA\Header::class, OA\Info::class, OA\Items::class, OA\Json_Content::class, OA\License::class, OA\Link::class, OA\Media_Type::class, OA\Open_Api::class, OA\Operation::class, OA\Options::class, OA\Parameter::class, OA\Path_Parameter::class, OA\Query_Parameter::class, OA\Cookie_Parameter::class, OA\Header_Parameter::class, OA\Patch::class, OA\Path_Item::class, OA\Post::class, OA\Property::class, OA\Put::class, OA\Query::class, OA\Request_Body::class, OA\Response::class, OA\Schema::class, OA\Security_Scheme::class, OA\Server::class, OA\Server_Variable::class, OA\Tag::class, OA\Trace::class, OA\Webhook::class, OA\Xml::class, OA\Xml_Content::class];
    /**
     * @param class-string<OA\AbstractAnnotation> $className
     */
    protected static function is_valid_annotation_class(string $class_name): bool
    {
        return in_array($class_name, self::$VALID_ANNOTATIONS);
    }
    /**
     * Deserialize a string.
     *
     * @param class-string<OA\AbstractAnnotation> $className
     */
    public function deserialize(string $json_string, string $class_name, ?Context $context = null): OA\Abstract_Annotation
    {
        if (!static::is_valid_annotation_class($class_name)) {
            throw new Open_Api_Exception($class_name . ' is not defined in OpenApi PHP Annotations');
        }
        return $this->do_deserialize(json_decode($json_string), $class_name, $context ?? new Context(['generated' => true]));
    }
    /**
     * Deserialize a file.
     *
     * @param class-string<OA\AbstractAnnotation> $className
     */
    public function deserialize_file(string $filename, string $format = 'json', string $class_name = OA\Open_Api::class, ?Context $context = null): OA\Abstract_Annotation
    {
        if (!static::is_valid_annotation_class($class_name)) {
            throw new Open_Api_Exception($class_name . ' is not a valid OpenApi PHP Annotations');
        }
        $contents = file_get_contents($filename);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if ('yaml' === $format || in_array($ext, ['yml', 'yaml'], strict: true)) {
            $contents = json_encode(Yaml::parse($contents));
        }
        return $this->do_deserialize(json_decode($contents), $class_name, $context ?? new Context(['generated' => true]));
    }
    /**
     * Do deserialization.
     *
     * @param class-string<OA\AbstractAnnotation> $className
     */
    protected function do_deserialize(\stdClass $c, string $class_name, Context $context): OA\Abstract_Annotation
    {
        $annotation = new $class_name(['_context' => $context]);
        foreach ((array) $c as $property => $value) {
            if ($property === '$ref') {
                $property = 'ref';
            }
            if (str_starts_with((string) $property, 'x-')) {
                if (Generator::is_default($annotation->x)) {
                    $annotation->x = [];
                }
                $custom = substr((string) $property, 2);
                $annotation->x[$custom] = $value;
            } else {
                $annotation->{$property} = $this->do_deserialize_property($annotation, $property, $value, $context);
            }
        }
        if ($annotation instanceof OA\Open_Api) {
            $context->root()->version = $annotation->openapi;
        }
        return $annotation;
    }
    /**
     * Deserialize the annotation's property.
     */
    protected function do_deserialize_property(OA\Abstract_Annotation $annotation, string $property, $value, Context $context)
    {
        // property is primitive type
        if (array_key_exists($property, $annotation::$_types)) {
            return $this->do_deserialize_base_property($annotation::$_types[$property], $value, $context);
        }
        // property is embedded annotation
        // note: this does not support custom nested annotation classes
        foreach ($annotation::$_nested as $nested_class => $declaration) {
            // property is an annotation
            if (is_string($declaration) && $declaration === $property) {
                if (is_object($value)) {
                    return $this->do_deserialize($value, $nested_class, $context);
                }
                return $value;
            }
            // property is an annotation array
            if (is_array($declaration) && count($declaration) === 1 && $declaration[0] === $property) {
                $annotation_arr = [];
                foreach ($value as $v) {
                    $annotation_arr[] = $this->do_deserialize($v, $nested_class, $context);
                }
                return $annotation_arr;
            }
            // property is an annotation hash map
            if (is_array($declaration) && count($declaration) === 2 && $declaration[0] === $property) {
                $key = $declaration[1];
                $annotation_hash = [];
                foreach ($value as $k => $v) {
                    $annotation = $this->do_deserialize($v, $nested_class, $context);
                    $annotation->{$key} = $k;
                    $annotation_hash[$k] = $annotation;
                }
                return $annotation_hash;
            }
        }
        return $value;
    }
    /**
     * Deserialize base annotation property.
     *
     * @param array|string $type  The property type
     * @param mixed        $value The value to deserialization
     *
     * @return array|OA\AbstractAnnotation
     */
    protected function do_deserialize_base_property(string $type, mixed $value, Context $context)
    {
        $is_annotation_class = is_string($type) && is_subclass_of(trim($type, '[]'), OA\Abstract_Annotation::class);
        if ($is_annotation_class) {
            $is_array = str_starts_with($type, '[') && str_ends_with($type, ']');
            if ($is_array) {
                $annotation_arr = [];
                $class = trim($type, '[]');
                foreach ($value as $v) {
                    $annotation_arr[] = $this->do_deserialize($v, $class, $context);
                }
                return $annotation_arr;
            }
            return $this->do_deserialize($value, $type, $context);
        }
        return $value;
    }
}