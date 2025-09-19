<?php
// app/Core/Validation/Rules/Unique.php
namespace Core\Validation\Rules;

use Core\Database\Model;
use Core\Di\Container;

class Unique implements RuleInterface
{
    public function passes(string $attribute, $value, array $parameters = [], array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $table  = $parameters[0] ?? null;
        $column = $parameters[1] ?? $attribute;
        $except = $parameters[2] ?? null;
        $exceptColumn = $parameters[3] ?? 'id';
        if (!$table) {
            return false;
        }
        $query = Container::getDefault()->get('db')
            ->table($table)
            ->where($column, $value);
        if ($except) {
            $query->where($exceptColumn, '!=', $except);
        }
        return $query->first() === null;
    }

    public function message(string $attribute, $value, array $parameters = []): string
    {
        return "The {$attribute} has already been taken.";
    }
}