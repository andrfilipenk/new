<?php

namespace App\Eav\Schema\Backup;

/**
 * Backup Type Enum
 */
class BackupType
{
    public const SCHEMA_ONLY = 'schema';
    public const DATA_ONLY = 'data';
    public const FULL = 'full';
}
