<?php

namespace Oro\Bundle\ActionBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Model\Assembler\ParameterAssembler;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionGroup
{
    /** @var ActionFactory */
    private $actionFactory;

    /** @var ConditionFactory */
    private $conditionFactory;

    /** @var ParameterAssembler */
    private $parameterAssembler;

    /** @var ActionGroupDefinition */
    private $definition;

    /** @var Parameter[] */
    private $parameters;

    /**
     * @param ActionFactory $actionFactory
     * @param ConditionFactory $conditionFactory
     * @param ParameterAssembler $parameterAssembler
     * @param ActionGroupDefinition $definition
     */
    public function __construct(
        ActionFactory $actionFactory,
        ConditionFactory $conditionFactory,
        ParameterAssembler $parameterAssembler,
        ActionGroupDefinition $definition
    ) {
        $this->actionFactory = $actionFactory;
        $this->conditionFactory = $conditionFactory;
        $this->parameterAssembler = $parameterAssembler;
        $this->definition = $definition;
    }

    /**
     * @param ActionData $data
     * @param Collection $errors
     * @return ActionData
     * @throws ForbiddenOperationException
     */
    public function execute(ActionData $data, Collection $errors = null)
    {
        if (!$this->isAllowed($data, $errors)) {
            throw new ForbiddenOperationException(
                sprintf('ActionGroup "%s" is not allowed', $this->definition->getName())
            );
        }
        $this->executeActions($data);

        return $data;
    }

    /**
     * @return ActionGroupDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * Check is actionGroup is allowed to execute
     *
     * @param ActionData $data
     * @param Collection|null $errors
     * @return bool
     */
    public function isAllowed(ActionData $data, Collection $errors = null)
    {
        if ($config = $this->definition->getConditions()) {
            $conditions = $this->conditionFactory->create(ConfigurableCondition::ALIAS, $config);
            if ($conditions instanceof ConfigurableCondition) {
                return $conditions->evaluate($data, $errors);
            }
        }

        return true;
    }

    /**
     * @param ActionData $data
     */
    protected function executeActions(ActionData $data)
    {
        if ($config = $this->definition->getActions()) {
            $actions = $this->actionFactory->create(ConfigurableAction::ALIAS, $config);
            if ($actions instanceof ActionInterface) {
                $actions->execute($data);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        if ($this->parameters === null) {
            $this->parameters = [];
            $parametersConfig = $this->definition->getParameters();
            if ($parametersConfig) {
                $this->parameters = $this->parameterAssembler->assemble($parametersConfig);
            }
        }

        return $this->parameters;
    }
}
