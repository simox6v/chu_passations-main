<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Passation;

class PassationBootTest extends TestCase
{
    public function test_passation_model_has_boot_method()
    {
        // Test that the Passation model has the boot method implemented
        $reflectionClass = new \ReflectionClass(Passation::class);
        $this->assertTrue($reflectionClass->hasMethod('boot'));
        
        $bootMethod = $reflectionClass->getMethod('boot');
        $this->assertTrue($bootMethod->isProtected() || $bootMethod->isPublic());
    }

    public function test_passation_model_has_proper_fillable_attributes()
    {
        $passation = new Passation();
        $fillable = $passation->getFillable();
        
        // Check that required attributes are fillable
        $this->assertContains('nom_patient', $fillable);
        $this->assertContains('date_passation', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    public function test_passation_model_has_proper_date_casts()
    {
        $passation = new Passation();
        $dates = $passation->getDates();
        
        // Check that date_passation is properly cast
        $this->assertContains('date_passation', $dates);
        $this->assertContains('created_at', $dates);
        $this->assertContains('updated_at', $dates);
    }
}