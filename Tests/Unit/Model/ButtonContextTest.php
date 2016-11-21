<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ButtonContext */
    protected $buttonContext;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buttonContext = new ButtonContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->buttonContext);
    }

    public function testGetSetButtonContext()
    {
        $context = [
            ['routeName', 'test_route'],
            ['datagridName', 'datagrid'],
            ['group', 'test_group'],
            ['executionRoute', 'test_url1'],
            ['formDialogRoute', 'test_url2'],
            ['formPageRoute', 'test_url3'],
            ['enabled', true],
            ['unavailableHidden', true],
            ['errors', ['test_error'], []],
        ];

        $this->assertPropertyAccessors($this->buttonContext, $context);
    }

    /**
     * @dataProvider getSetEntityDataProvider
     *
     * @param int|string|array|null $entityId
     */
    public function testSetGetEntity($entityId)
    {
        $this->buttonContext->setEntity('Class', $entityId);
        $this->assertSame('Class', $this->buttonContext->getEntityClass());
        $this->assertSame($entityId, $this->buttonContext->getEntityId());
    }

    /**
     * @return array
     */
    public function getSetEntityDataProvider()
    {
        return [
            [10],
            [uniqid()],
            [[10, uniqid()]],
            [null]
        ];
    }
}
