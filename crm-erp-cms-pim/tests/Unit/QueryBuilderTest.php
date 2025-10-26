<?php

namespace Tests\Unit;

use Tests\TestCase;
use Core\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    public function testSelectQuery()
    {
        $qb = new QueryBuilder('users');
        $sql = $qb->select('id', 'name')->toSql();
        
        $this->assertTrue(str_contains($sql, 'SELECT'));
        $this->assertTrue(str_contains($sql, 'FROM users'));
    }
    
    public function testWhereClause()
    {
        $qb = new QueryBuilder('users');
        $sql = $qb->where('status', '=', 'active')->toSql();
        
        $this->assertTrue(str_contains($sql, 'WHERE'));
        $this->assertTrue(str_contains($sql, 'status'));
    }
    
    public function testJoinClause()
    {
        $qb = new QueryBuilder('users');
        $sql = $qb->join('roles', 'users.role_id', '=', 'roles.id')->toSql();
        
        $this->assertTrue(str_contains($sql, 'JOIN'));
    }
}

// Run tests
$test = new QueryBuilderTest();
try {
    $test->setUp();
    $test->testSelectQuery();
    echo "✓ Select query test passed\n";
    $test->tearDown();
    
    $test->setUp();
    $test->testWhereClause();
    echo "✓ Where clause test passed\n";
    $test->tearDown();
    
    $test->setUp();
    $test->testJoinClause();
    echo "✓ Join clause test passed\n";
    $test->tearDown();
} catch (\Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
}
