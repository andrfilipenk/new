<?php
// migrations/2025_10_01_100000_enhance_user_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class EnhanceUserTable extends Migration
{
    public function up(): void
    {
        $this->table('user', function(Blueprint $table) {
            // Additional user fields
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('department', 50)->nullable();
            $table->string('position', 50)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->integer('manager_id')->unsigned()->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('employee_id', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_login')->nullable();
            $table->string('profile_image', 255)->nullable();
            $table->text('notes')->nullable();
            
            // Add foreign key for manager
            $table->foreign('manager_id')->references('id')->on('user');
        });
        
        // Create employee benefits table
        $this->createTable('user_benefits', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->string('benefit_type', 50); // health, dental, vacation, etc.
            $table->string('benefit_name', 100);
            $table->text('description')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('user');
        });
        
        // Create user documents table
        $this->createTable('user_documents', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->string('document_type', 50); // contract, id_copy, certificate, etc.
            $table->string('document_name', 200);
            $table->string('file_path', 500);
            $table->string('file_type', 10)->nullable();
            $table->integer('file_size')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('user');
        });
    }
    
    public function down(): void
    {
        $this->dropTable('user_documents');
        $this->dropTable('user_benefits');
        
        $this->table('user', function(Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'department', 'position',
                'hire_date', 'birth_date', 'address', 'emergency_contact_name',
                'emergency_contact_phone', 'manager_id', 'salary', 'employee_id',
                'status', 'last_login', 'profile_image', 'notes'
            ]);
        });
    }
}