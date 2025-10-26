<?php

namespace Tests\Unit;

use Tests\TestCase;
use Core\Validation\Validator;

class ValidationTest extends TestCase
{
    public function testRequiredValidation()
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
    }
    
    public function testEmailValidation()
    {
        $validator = Validator::make(['email' => 'invalid'], ['email' => 'email']);
        $this->assertTrue($validator->fails());
        
        $validator = Validator::make(['email' => 'test@example.com'], ['email' => 'email']);
        $this->assertTrue($validator->passes());
    }
    
    public function testMinMaxValidation()
    {
        $validator = Validator::make(['age' => 5], ['age' => 'min:10']);
        $this->assertTrue($validator->fails());
        
        $validator = Validator::make(['age' => 15], ['age' => 'min:10|max:20']);
        $this->assertTrue($validator->passes());
    }
}

// Run tests
$test = new ValidationTest();
try {
    $test->setUp();
    $test->testRequiredValidation();
    echo "✓ Required validation test passed\n";
    $test->tearDown();
    
    $test->setUp();
    $test->testEmailValidation();
    echo "✓ Email validation test passed\n";
    $test->tearDown();
    
    $test->setUp();
    $test->testMinMaxValidation();
    echo "✓ Min/Max validation test passed\n";
    $test->tearDown();
} catch (\Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
}
