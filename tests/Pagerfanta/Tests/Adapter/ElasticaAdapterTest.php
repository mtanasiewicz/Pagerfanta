<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\ElasticaAdapter;
use PHPUnit\Framework\TestCase;

class ElasticaAdapterTest extends TestCase
{
    /**
     * @var ElasticaAdapter
     */
    private $adapter;
    private $resultSet;
    private $searchable;
    private $query;
    private $options;

    protected function setUp()
    {
        $this->query = $this->getMockBuilder('Elastica\\Query')->disableOriginalConstructor()->getMock();
        $this->resultSet = $this->getMockBuilder('Elastica\\ResultSet')->disableOriginalConstructor()->getMock();
        $this->searchable = $this->getMockBuilder('Elastica\\SearchableInterface')->disableOriginalConstructor()->getMock();

        $this->options = array("option1" => "value1", "option2" => "value2");

        $this->adapter = new ElasticaAdapter($this->searchable, $this->query, $this->options);
    }

    public function testGetResultSet()
    {
        $this->assertNull($this->adapter->getResultSet());

        $this->searchable->expects($this->any())
            ->method('search')
            ->with($this->query, array('from' => 0, 'size' => 1, 'option1' => 'value1', 'option2' => 'value2'))
            ->will($this->returnValue($this->resultSet));

        $this->adapter->getSlice(0, 1);

        $this->assertSame($this->resultSet, $this->adapter->getResultSet());
    }

    public function testGetSlice()
    {
        $this->searchable->expects($this->any())
            ->method('search')
            ->with($this->query, array('from' => 10, 'size' => 30, 'option1' => 'value1', 'option2' => 'value2'))
            ->will($this->returnValue($this->resultSet));

        $resultSet = $this->adapter->getSlice(10, 30);

        $this->assertSame($this->resultSet, $resultSet);
        $this->assertSame($this->resultSet, $this->adapter->getResultSet());
    }

    /**
     * Returns the number of results before search, use count() method if resultSet is empty
     */
    public function testGetNbResultsBeforeSearch()
    {
        $this->searchable->expects($this->once())
            ->method('count')
            ->with($this->query)
            ->willReturn(100);

        $this->assertSame(100, $this->adapter->getNbResults());
    }

    /**
     * Returns the number of results after search, use getTotalHits() method if resultSet is not empty
     */
    public function testGetNbResultsAfterSearch()
    {
        $adapter = new ElasticaAdapter($this->searchable, $this->query, [], 30);

        $this->searchable->expects($this->once())
            ->method('search')
            ->with($this->query, array('from' => 10, 'size' => 30))
            ->will($this->returnValue($this->resultSet));

        $this->resultSet->expects($this->once())
            ->method('getTotalHits')
            ->will($this->returnValue(100));

        $adapter->getSlice(10, 30);

        $this->assertSame(30, $adapter->getNbResults());
    }

    public function testGetNbResultsWithMaxResultsSet()
    {
        $adapter = new ElasticaAdapter($this->searchable, $this->query, [], 10);

        $this->searchable->expects($this->once())
            ->method('count')
            ->with($this->query)
            ->willReturn(100);

        $this->assertSame(10, $adapter->getNbResults());
    }
}
