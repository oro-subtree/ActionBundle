<?php

namespace Oro\Bundle\ActionBundle\Model;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;

class ActionFormManager
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var OperationManager */
    protected $operationManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param OperationManager $operationManager
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        OperationManager $operationManager,
        ContextHelper $contextHelper
    ) {
        $this->formFactory = $formFactory;
        $this->operationManager = $operationManager;
        $this->contextHelper = $contextHelper;
    }

    /**
     * @param string $actionName
     * @param ActionData $data
     * @return Form
     */
    public function getOperationForm($actionName, ActionData $data)
    {
        $operation = $this->operationManager->getOperation($actionName, $data);

        return $this->formFactory->create(
            $operation->getDefinition()->getFormType(),
            $data,
            array_merge($operation->getFormOptions($data), ['action' => $operation])
        );
    }
}
