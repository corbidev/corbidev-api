<?php

namespace App\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

final class SqlTablePrefixSubscriber
{
    public function __construct(
        private readonly string $prefix,
    ) {
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs): void
    {
        if ($this->prefix === '') {
            return;
        }

        $classMetadata = $eventArgs->getClassMetadata();
        if ($classMetadata->rootEntityName !== $classMetadata->name) {
            return;
        }

        $table = $classMetadata->getTableName();
        if (!str_starts_with($table, $this->prefix)) {
            $classMetadata->setPrimaryTable([
                'name' => $this->prefix . $table,
            ]);
        }

        foreach ($classMetadata->associationMappings as $fieldName => $mapping) {
            if (($mapping['type'] ?? null) !== ClassMetadata::MANY_TO_MANY || !($mapping['isOwningSide'] ?? false)) {
                continue;
            }

            $joinTableName = $mapping['joinTable']['name'] ?? null;
            if (!is_string($joinTableName) || str_starts_with($joinTableName, $this->prefix)) {
                continue;
            }

            $classMetadata->associationMappings[$fieldName]['joinTable']['name'] = $this->prefix . $joinTableName;
        }
    }
}