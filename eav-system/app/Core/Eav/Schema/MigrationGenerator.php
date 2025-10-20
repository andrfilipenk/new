<?php
// app/Core/Eav/Schema/MigrationGenerator.php
namespace Core\Eav\Schema;

use Core\Eav\Model\EntityType;

/**
 * Generates migration files for schema changes
 */
class MigrationGenerator
{
    private string $migrationsPath;
    private StructureBuilder $structureBuilder;

    public function __construct(StructureBuilder $structureBuilder, string $migrationsPath = null)
    {
        $this->structureBuilder = $structureBuilder;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../../../../migrations';
    }

    /**
     * Generate base EAV structure migration
     */
    public function generateBaseMigration(): string
    {
        $timestamp = date('Y_m_d_His');
        $className = 'CreateEavBaseStructure';
        $fileName = "{$timestamp}_create_eav_base_structure.php";
        $filePath = $this->migrationsPath . '/' . $fileName;

        $content = $this->generateBaseMigrationContent($className);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Generate entity type migration
     */
    public function generateEntityTypeMigration(EntityType $entityType): string
    {
        $timestamp = date('Y_m_d_His');
        $code = $entityType->getCode();
        $className = 'CreateEavEntity' . $this->toPascalCase($code);
        $fileName = "{$timestamp}_create_eav_entity_{$code}.php";
        $filePath = $this->migrationsPath . '/' . $fileName;

        $content = $this->generateEntityTypeMigrationContent($className, $entityType);

        file_put_contents($filePath, $content);

        return $filePath;
    }

    private function generateBaseMigrationContent(string $className): string
    {
        return <<<PHP
<?php
// migrations/{$className}.php
use Core\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        // Create entity type table
        \$this->createTable('eav_entity_type', function(\$table) {
            \$table->id('entity_type_id');
            \$table->string('entity_code')->unique();
            \$table->string('entity_label');
            \$table->string('entity_table');
            \$table->string('storage_strategy', 32)->default('eav');
            \$table->timestamps();
        });

        // Create attribute metadata table
        \$this->createTable('eav_attribute', function(\$table) {
            \$table->id('attribute_id');
            \$table->integer('entity_type_id')->unsigned();
            \$table->string('attribute_code');
            \$table->string('attribute_label');
            \$table->string('backend_type', 32);
            \$table->string('frontend_type', 32);
            \$table->integer('is_required')->default(0);
            \$table->integer('is_unique')->default(0);
            \$table->integer('is_searchable')->default(0);
            \$table->integer('is_filterable')->default(0);
            \$table->text('default_value')->nullable();
            \$table->text('validation_rules')->nullable();
            \$table->integer('sort_order')->default(0);
            \$table->timestamps();
            \$table->index(['entity_type_id', 'attribute_code'], 'idx_entity_attr', 'UNIQUE');
            \$table->index(['entity_type_id']);
        });

        // Create value tables for each backend type
        \$this->createValueTable('varchar', 'VARCHAR(255)');
        \$this->createValueTable('int', 'INT');
        \$this->createValueTable('decimal', 'DECIMAL(12,4)');
        \$this->createValueTable('datetime', 'DATETIME');
        \$this->createValueTable('text', 'TEXT');
    }

    private function createValueTable(\$type, \$valueType)
    {
        \$this->createTable("eav_value_{\$type}", function(\$table) use (\$valueType) {
            \$table->id('value_id');
            \$table->integer('entity_type_id')->unsigned();
            \$table->integer('attribute_id')->unsigned();
            \$table->integer('entity_id')->unsigned();
            
            if (\$valueType === 'VARCHAR(255)') {
                \$table->string('value')->nullable();
            } elseif (\$valueType === 'INT') {
                \$table->integer('value')->nullable();
            } elseif (\$valueType === 'DECIMAL(12,4)') {
                \$table->decimal('value', 12, 4)->nullable();
            } elseif (\$valueType === 'DATETIME') {
                \$table->timestamp('value')->nullable();
            } elseif (\$valueType === 'TEXT') {
                \$table->text('value')->nullable();
            }
            
            \$table->index(['entity_type_id', 'attribute_id', 'entity_id'], 'idx_unique_entity_attr', 'UNIQUE');
            \$table->index(['entity_id']);
            
            if (\$valueType !== 'TEXT') {
                \$table->index(['attribute_id', 'value']);
            }
        });
    }

    public function down()
    {
        \$this->dropTable('eav_value_text');
        \$this->dropTable('eav_value_datetime');
        \$this->dropTable('eav_value_decimal');
        \$this->dropTable('eav_value_int');
        \$this->dropTable('eav_value_varchar');
        \$this->dropTable('eav_attribute');
        \$this->dropTable('eav_entity_type');
    }
}
PHP;
    }

    private function generateEntityTypeMigrationContent(string $className, EntityType $entityType): string
    {
        $code = $entityType->getCode();
        $label = $entityType->getLabel();
        $tableName = $entityType->getEntityTable();
        
        $attributesInsert = '';
        foreach ($entityType->getAttributes() as $attribute) {
            $attrArray = $this->arrayToPhpCode($attribute->toArray());
            $attributesInsert .= "        \$this->insertData('eav_attribute', array_merge(\$baseAttr, {$attrArray}));\n";
        }

        return <<<PHP
<?php
// migrations/{$className}.php
use Core\Database\Migration;

class {$className} extends Migration
{
    public function up()
    {
        // Insert entity type record
        \$entityTypeId = self::db()->table('eav_entity_type')->insert([
            'entity_code' => '{$code}',
            'entity_label' => '{$label}',
            'entity_table' => '{$tableName}',
            'storage_strategy' => 'eav'
        ]);

        // Create entity table
        \$this->createTable('{$tableName}', function(\$table) {
            \$table->id('entity_id');
            \$table->integer('entity_type_id')->unsigned();
            \$table->timestamps();
            \$table->index(['entity_type_id']);
        });

        // Insert attributes
        \$baseAttr = ['entity_type_id' => \$entityTypeId];
{$attributesInsert}
    }

    public function down()
    {
        \$entityType = self::db()->table('eav_entity_type')
            ->where('entity_code', '{$code}')
            ->first();
        
        if (\$entityType) {
            self::db()->table('eav_attribute')
                ->where('entity_type_id', \$entityType['entity_type_id'])
                ->delete();
        }
        
        \$this->dropTable('{$tableName}');
        
        self::db()->table('eav_entity_type')
            ->where('entity_code', '{$code}')
            ->delete();
    }
}
PHP;
    }

    private function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }

    private function arrayToPhpCode(array $array): string
    {
        $elements = [];
        foreach ($array as $key => $value) {
            if ($value === null) {
                $elements[] = "'{$key}' => null";
            } elseif (is_bool($value)) {
                $elements[] = "'{$key}' => " . ($value ? 'true' : 'false');
            } elseif (is_numeric($value)) {
                $elements[] = "'{$key}' => {$value}";
            } else {
                $escaped = addslashes($value);
                $elements[] = "'{$key}' => '{$escaped}'";
            }
        }
        return '[' . implode(', ', $elements) . ']';
    }
}
