<?php
// app/Core/Validation/Rules/Exists.php
namespace Core\Validation\Rules;

use Core\Di\Container;
use Core\Database\Database;

class Exists implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if (is_null($value) || $value === '') {
            return true; // Use 'required' rule for required validation
        }
        
        if (count($parameters) < 2) {
            throw new \InvalidArgumentException('Exists rule requires table and column parameters');
        }
        
        $table = $parameters[0];
        $column = $parameters[1];
        
        /**
         * @var Database $database
         */
        $database = Container::getDefault()->get('db');
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $result = $database->execute($query, [$value]);
        
        return $result[0]['count'] > 0;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The selected {$attribute} does not exist.";
    }
}