<?php

namespace Shopware\PhpStan\Shopware5\Rules\InvalidUsage;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class PluginManagerOverwrite implements Rule
{
    public function getNodeType(): string
    {
        return Node\Scalar\String_::class;
    }

    /**
     * @param Node\Scalar\String_ $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->value;

        if (
            strpos($name, 'Enlight_Controller_Action_PostDispatch_Backend_PluginManager') !== false ||
            strpos($name, 'Enlight_Controller_Action_PreDispatch_Backend_PluginManager') !== false ||
            strpos($name, 'Enlight_Controller_Action_Backend_PluginManager') !== false
        ) {
            return [
                'It is not allowed to modifiy the plugin manager. For more informations please look at https://docs.shopware.com/en/plugin-standard-for-community-store'
            ];
        }

        return [];
    }
}
