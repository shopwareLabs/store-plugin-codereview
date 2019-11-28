<?php

namespace Shopware\PhpStan\Shopware5\Rules\InvalidUsage;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class InvalidCacheClearing implements Rule
{
    private const NOT_ALLOWED_CACHE_ITEMS_WHILE_INSTALL = [
        'CACHE_LIST_ALL',
        'CACHE_LIST_FRONTEND',
        'CACHE_TAG_TEMPLATE',
        'CACHE_TAG_THEME',
        'CACHE_TAG_HTTP',
        'template',
        'theme',
        'http',
    ];

    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = (string) $node->name;

        if (!in_array($name, ['install', 'uninstall'], true)) {
            return [];
        }

        $errors = [];

        /** @var Node\Stmt\Expression $stmt */
        foreach ($node->getStmts() as $stmt) {
            $call = $stmt->expr;
            if (! $call instanceof Node\Expr\MethodCall) {
                continue;
            }

            $callName = (string) $call->name;
            $varName = (string) $call->var->name;

            if ($callName !== 'scheduleClearCache' && $varName !== 'context') {
                continue;
            }

            $list = $this->convertArgToList($call->args[0]);

            foreach (self::NOT_ALLOWED_CACHE_ITEMS_WHILE_INSTALL as $item) {
                if (in_array($item, $list, true)) {
                    $errors[] = 'Clear only the necessary caches when installing or uninstalling a plugin. For more information please look at https://docs.shopware.com/en/plugin-standard-for-community-store#clear-only-the-necessary-caches-when-installing-or-uninstalling-a-plugin';
                }
            }
        }

        return $errors;
    }

    private function convertArgToList(Node\Arg $arg): array
    {
        if ($arg->value instanceof Node\Expr\ClassConstFetch) {
            return [(string) $arg->value->name];
        }

        if($arg->value instanceof Node\Expr\Array_) {
            return array_map(static function (Node\Expr\ArrayItem $item) {
                return InvalidCacheClearing::convertValue($item->value);
            }, $arg->value->items);
        }

        return [];
    }

    public static function convertValue($value): ?string
    {
        if ($value instanceof Node\Expr\ClassConstFetch) {
            return (string) $value->name;
        }

        if ($value instanceof Node\Scalar\String_) {
            return (string) $value->value;
        }
    }
}
