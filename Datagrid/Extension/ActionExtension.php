<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;

class ActionExtension extends AbstractExtension
{
    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var ApplicationsHelper */
    protected $applicationHelper;

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var array */
    protected $actionConfiguration = [];

    /** @var array */
    protected $datagridContext = [];

    /** @var array|Action[] */
    protected $actions = [];

    /** @var array  */
    protected $actionGroups;

    /**
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     * @param ApplicationsHelper $applicationHelper
     * @param MassActionProviderRegistry $providerRegistry
     */
    public function __construct(
        ActionManager $actionManager,
        ContextHelper $contextHelper,
        ApplicationsHelper $applicationHelper,
        MassActionProviderRegistry $providerRegistry
    ) {
        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
        $this->applicationsHelper = $applicationHelper;
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * @param array $actionGroups
     */
    public function setActionGroups(array $actionGroups)
    {
        $this->actionGroups = $actionGroups;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->actions = $this->actionManager->getActions($this->datagridContext, false);

        if (0 === count($this->actions)) {
            return false;
        }

        $this->processActionsConfig($config);
        $this->processMassActionsConfig($config);

        $this->actionConfiguration = $config->offsetGetOr(DatagridActionExtension::ACTION_CONFIGURATION_KEY, []);
        $config->offsetSet(DatagridActionExtension::ACTION_CONFIGURATION_KEY, [$this, 'getRowConfiguration']);

        return true;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getRowConfiguration(ResultRecordInterface $record)
    {
        $actionData = $this->contextHelper->getActionData([
            'entityId' => $record->getValue('id'),
            'entityClass' => $this->datagridContext['entityClass'],
            'datagrid' => $this->datagridContext['datagrid'],
        ]);

        $actionsNew = [];
        foreach ($this->actions as $action) {
            $actionsNew[$action->getName()] = $this->getRowActionsConfig($action, $actionData);
        }

        return array_merge($this->getParentRowConfiguration($record), $actionsNew);
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    protected function getParentRowConfiguration(ResultRecordInterface $record)
    {
        if (empty($this->actionConfiguration)) {
            return [];
        }

        $rowActions = [];

        if (is_callable($this->actionConfiguration)) {
            $rowActions = call_user_func($this->actionConfiguration, $record);
        } elseif (is_array($this->actionConfiguration)) {
            $rowActions = $this->actionConfiguration;
        }

        return is_array($rowActions) ? $rowActions : [];
    }

    /**
     * @param Action $action
     * @param ActionData $actionData
     * @return bool|array
     */
    protected function getRowActionsConfig(Action $action, ActionData $actionData)
    {
        if (!$action->isAvailable($actionData)) {
            return false;
        }

        return [
            'translates' => $actionData->getScalarValues(),
        ];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr('actions', []);

        foreach ($this->actions as $action) {
            $buttonOptions = $action->getDefinition()->getButtonOptions();
            $frontendOptions = $action->getDefinition()->getFrontendOptions();

            $actionsConfig[$action->getName()] = [
                'type' => 'action-widget',
                'label' => $action->getDefinition()->getLabel(),
                'rowAction' => false,
                'link' => '#',
                'icon' => !empty($buttonOptions['icon']) ? str_ireplace('icon-', '', $buttonOptions['icon']) : 'edit',
                'options' => [
                    'actionName' => $action->getName(),
                    'entityClass' => $this->datagridContext['entityClass'],
                    'datagrid' => $this->datagridContext['datagrid'],
                    'confirmation' =>  !empty($frontendOptions['confirmation']) ? $frontendOptions['confirmation'] : '',
                    'showDialog' => $action->hasForm(),
                    'executionRoute' => $this->applicationsHelper->getExecutionRoute(),
                    'dialogRoute' => $this->applicationsHelper->getDialogRoute(),
                    'dialogOptions' => [
                        'title' => $action->getDefinition()->getLabel(),
                        'dialogOptions' => !empty($frontendOptions['options']) ? $frontendOptions['options'] : []
                    ],
                    'translates' => [],
                ]
            ];
        }

        $config->offsetSet('actions', $actionsConfig);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processMassActionsConfig(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->actions as $action) {
            $datagridOptions = $action->getDefinition()->getDatagridOptions();

            if (!empty($datagridOptions['mass_action_provider'])) {
                $provider = $this->providerRegistry->getProvider($datagridOptions['mass_action_provider']);

                if ($provider) {
                    foreach ($provider->getActions() as $name => $massAction) {
                        $actions[$action->getName() . $name] = $massAction;
                    }
                }
            } elseif (!empty($datagridOptions['mass_action'])) {
                $actions[$action->getName()] = array_merge(
                    [
                        'label' => $action->getDefinition()->getLabel()
                    ],
                    $datagridOptions['mass_action']
                );
            }
        }

        $config->offsetSet('mass_actions', $actions);
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        $entityClass = $config->offsetGetByPath('[extended_entity_name]');

        return [
            'entityClass' => $entityClass ? : $config->offsetGetByPath('[entity_name]'),
            'datagrid' => $config->offsetGetByPath('[name]'),
            'group' => $this->actionGroups,
        ];
    }
}
