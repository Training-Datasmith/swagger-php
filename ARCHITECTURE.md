# Architecture: swagger-php

## Purpose

A PHP library that reads OpenAPI annotations and PHP 8 attributes from source code and generates OpenAPI 3.x specification documents (JSON/YAML). Used to document REST APIs directly from PHP docblocks or attributes without maintaining a separate spec file.

## Directory Structure

```
src/
  Generator.php                  - Entry point: scans source files and produces an OpenApi object
  Analysis.php                   - Holds all discovered annotations during a scan pass
  Pipeline.php                   - Ordered chain of Processor passes applied after analysis
  Serializer.php                 - Serializes the OpenApi object to JSON or YAML
  Source_Finder.php              - Locates PHP source files from directories/globs
  Context.php                    - Carries file/class/method context during annotation parsing
  Open_Api_Exception.php         - Domain exception
  Generator_Aware_Interface.php  - Interface for classes that need Generator access
  Generator_Aware_Trait.php      - Default implementation of Generator_Aware_Interface
  Type_Resolver_Interface.php    - Contract for resolving PHP types to OpenAPI schema types
  Annotations/                   - PHP classes for every OpenAPI object (Info, Path, Schema, etc.)
  Attributes/                    - PHP 8 attribute equivalents of the annotation classes
  Analysers/                     - Strategies for extracting annotations (docblock vs attribute)
  Processors/                    - Post-processing passes (augment refs, merge components, etc.)
  Loggers/                       - PSR-3 compatible logger implementations
  Type/                          - Type resolution helpers
```

## Key Design Decisions

- **Dual annotation/attribute support**: The same OpenAPI concepts are represented as both PHP docblock annotations (Doctrine-style) and native PHP 8 attributes, sharing the same underlying class hierarchy.
- **Analysis pipeline**: After source scanning, a `Pipeline` of `Processor` passes augments and validates the annotation graph (e.g., resolving `$ref`, merging definitions into components, expanding enums/interfaces/traits).
- **Separation of analysis and processing**: `Analysis` collects raw annotations; `Processor` passes transform them. This makes it easy to add new transformation steps without touching the analyser.
- **Reflection-based analyser**: `Reflection_Analyser` uses PHP's `ReflectionClass` to walk class members, supporting both docblock and attribute extraction in one pass.

## Extension Points

- Implement `Analyser_Interface` to add a custom source analysis strategy.
- Add a custom `Processor` to the pipeline for project-specific spec augmentation.
- Implement `Type_Resolver_Interface` for custom PHP-to-OpenAPI type mapping.

## Dependency Flow

```
Generator::generate(Source_Finder $finder): OpenApi
  └─> Source_Finder — collect PHP files
  └─> Analyser (Reflection_Analyser) — parse annotations/attributes into Analysis
  └─> Pipeline — apply Processor passes to Analysis
        └─> Augment_Refs, Merge_Into_Components, Build_Paths, ...
  └─> Serializer::toJson() / toYaml() — output spec
```
