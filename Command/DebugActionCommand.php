<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

use Oro\Component\Action\Action\ActionFactory;

class DebugActionCommand extends AbstractDebugCommand
{
    const COMMAND_NAME = 'oro:debug:action';
    const ARGUMENT_NAME = 'action-name';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Displays current "actions" for an application')
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'An "action" name')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> displays the configured 'actions':

  <info>php %command.full_name%</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFactoryServiceId()
    {
        return 'oro_action.action_factory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getArgumentName()
    {
        return self::ARGUMENT_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypes()
    {
        /** @var ActionFactory $factory */
        $factory = $this->getFactory();

        return $factory->getTypes();
    }
}
