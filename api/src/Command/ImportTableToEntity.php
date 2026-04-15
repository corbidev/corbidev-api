<?php

namespace App\Command\ImportTableToEntity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

 #[AsCommand(
    name: 'app:importTableToEntity',
    description: 'Add a short description for your command',
)]
/**
 * Genere une entite Doctrine a partir de la structure d'une table PostgreSQL.
 */
class ImportTableToEntityCommand extends Command
{



    private string $projectDir;
    private EntityManager $entityManager;

    /**
     * @param ManagerRegistry $doctrine Registre Doctrine utilise pour acceder au manager map.
     */
    public function __construct( KernelInterface $kernel,ManagerRegistry $doctrine)
    {

        $this->projectDir                  = $kernel->getProjectDir();
        $this->entityManager               = $doctrine->getManager('map');

        parent::__construct();
    }

    /**
     * Declare les options requises par la commande d'import.
     */
    protected function configure(): void
    {
        $this

            ->addOption('schema', null, InputOption::VALUE_REQUIRED, 'Option schema')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Option table')
        ;
    }

    /**
     * Execute la generation d'entite a partir du schema et de la table cibles.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $schema = $input->getOption('schema');
        $table  = $input->getOption('table');

        if ($schema=='') {
            $io->error('Argument "schema" is required.');
            return Command::FAILURE;
        }

        if ($table=='') {
            $io->error('Argument "table" is required.');
            return Command::FAILURE;
        }

        try {
            $this->generateEntity($io,$schema, $table);
        } catch (Exception $e) {
            $io->error("ERROR CATCH ->".$e->getMessage());
            return Command::FAILURE;
        }




        return Command::SUCCESS;
    }

    /**
     * Construit le code PHP de l'entite puis l'ecrit dans src/EntityTest.
     */
    function generateEntity(SymfonyStyle $io,string $schema, string $tableName): void
    {

        $columns            = $this->getColumns($schema, $tableName);
        $foreignKeys        = $this->getForeignKeys($schema, $tableName);
        $sequences          = $this->getSequences($schema, $tableName);
        $indexes            = $this->getIndexes($schema, $tableName);
        $uniqueConstraints  = $this->getUniqueConstraints($schema, $tableName);


        if(count($columns)==0){
            $io->warning('table ->'.$schema.".".$tableName." Not Exist");
            return;
        }


        $className = ucfirst($schema).$this->snakeToPascalCase($tableName);
        $entityCode = "<?php\n\n";
        $entityCode .= "namespace App\\EntityTest;\n\n";
        $entityCode .= "use Doctrine\\ORM\\Mapping as ORM;\n\n";
        $entityCode .= "use Doctrine\\DBAL\\Types\\Types;\n\n";
        $entityCode .= "#[ORM\\Table(name: '$schema.$tableName')]\n";

        // Exporte les index non primaires dans les attributs Doctrine de l'entite.
        foreach ($indexes as $indexName => $index) {
            $columnsIndex = implode("', '", $index['columns']);
            $isUnique = $index['is_unique'] ? ', unique: true' : '';
            if (!$index['is_primary']) {
                $entityCode .= "#[ORM\\Index(name: '$indexName', columns: ['$columnsIndex'] $isUnique)]\n";
            }
        }

        // Exporte egalement les contraintes d'unicite detectees sur la table source.
        foreach ($uniqueConstraints as $constraintName => $columnsUniq) {
            $columnsList = implode("', '", $columnsUniq);
            $entityCode .= "#[ORM\\UniqueConstraint(name: '$constraintName', columns: ['$columnsList'])]\n";
        }


        $entityCode .= "#[ORM\\Entity(repositoryClass: '".ucfirst($schema).$this->snakeToPascalCase($tableName)."Repository')]\n";
        $entityCode .= "class $className\n{\n";





        foreach ($columns as $column) {
            $propertyName = lcfirst(str_replace('_', '', ucwords($column['column_name'], '_')));
            $type  = $this->mapColumnTypeToPhp($column['data_type']);
            $types = $this->mapColumnTypeToPhpTypes($column['data_type']);
            $nullable = $column['is_nullable'] === 'YES' ? 'true' : 'false';


            if(!array_key_exists($column['column_name'], $foreignKeys)){
                if (array_key_exists($column['column_name'], $sequences)) {
                    $sequenceName = $sequences[$column['column_name']]['schema'] . '.' . $sequences[$column['column_name']]['sequence_name'];
                    $entityCode .= "    #[ORM\\Id]\n";
                    $entityCode .= "    #[ORM\\GeneratedValue(strategy: 'IDENTITY')]\n";
                    $entityCode .= "    #[ORM\\SequenceGenerator(sequenceName: '$sequenceName', allocationSize: 1, initialValue: 1)]\n";
                    $entityCode .= "    #[ORM\\Column(name: '{$column['column_name']}',type: $types, nullable: $nullable)]\n";
                    $entityCode .= "    private ?$type \$$propertyName = null;\n\n";
                } else {
                        $entityCode .= "    #[ORM\\Column(name: '{$column['column_name']}', type: $types, nullable: $nullable)]\n";
                        if($column['column_name']=='id'){
                            $entityCode .= "    #[ORM\Id]\n";
                        }
                        $entityCode .= "    private ?$type \$$propertyName = null;\n\n";
                }
            }



        }

        foreach ($foreignKeys as $columnName => $foreignKey) {
            $propertyName = lcfirst(str_replace('_', '', ucwords($columnName, '_')));
            $referencedSchema = $foreignKey['referenced_schema'];
            $referencedTable = $foreignKey['referenced_table'];
            $referencedEntity = ucfirst($referencedSchema).$this->snakeToPascalCase($referencedTable);

            $entityCode .= "    #[ORM\\ManyToOne(targetEntity: $referencedEntity::class)]\n";
            $entityCode .= "    #[ORM\\JoinColumn(name: '$columnName', referencedColumnName: '{$foreignKey['referenced_column']}', nullable: true)]\n";
            $entityCode .= "    private ?$referencedEntity \$$propertyName =null;\n\n";

        }


        $entityCode .= "    \n\n";
        $entityCode .= "    public function __construct()\n";
        $entityCode .= "    {\n\n";
        $entityCode .= "    }\n\n";
        $entityCode .= "    public function __toString()\n";
        $entityCode .= "    {\n";
        $entityCode .= "        return json_encode(array(\n\n";
        foreach ($columns as $k=> $column) {
            $propertyName = lcfirst(str_replace('_', '', ucwords($column['column_name'], '_')));
            $entityCode .= "       '{$column['column_name']}'=>\$this->{$propertyName}";
            if(($k+1)<count($columns)) {
                $entityCode .= ",";
            }
            $entityCode .= "\n";
        }
        $entityCode .= "        ));\n";
        $entityCode .= "    }\n\n";

        $entityCode .= "}\n";


        file_put_contents($this->projectDir.'/src/EntityTest/'.$className.'.php', $entityCode);
        $io->success("Entity $className generated");
    }

    /**
     * Retourne la liste des colonnes d'une table.
     *
     * @return list<array{column_name: string, data_type: string, is_nullable: string}>
     */
    function getColumns(string $schema, string $tableName): array
    {

        $query = "
        SELECT
            column_name,
            data_type,
            is_nullable
        FROM
            information_schema.columns
        WHERE
            table_schema = :schema
            AND table_name = :table
    ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('column_name', 'column_name');
        $rsm->addScalarResult('data_type', 'data_type');
        $rsm->addScalarResult('is_nullable', 'is_nullable');


        $query = $this->entityManager->createNativeQuery($query, $rsm);
        $query->setParameter("schema", $schema);
        $query->setParameter("table", $tableName);
        $result= $query->getResult();

        return $result;
    }

    /**
     * Retourne les cles etrangeres d'une table indexees par nom de colonne.
     *
     * @return array<string, array{referenced_schema: string, referenced_table: string, referenced_column: string}>
     */
    function getForeignKeys(string $schema, string $tableName): array
    {


        $query = "
        SELECT
            kcu.column_name,
            ccu.table_schema AS referenced_schema,
            ccu.table_name AS referenced_table,
            ccu.column_name AS referenced_column
        FROM
            information_schema.key_column_usage kcu
        JOIN
            information_schema.table_constraints tc
            ON kcu.constraint_name = tc.constraint_name
            AND kcu.table_schema = tc.table_schema
        JOIN
            information_schema.constraint_column_usage ccu
            ON ccu.constraint_name = tc.constraint_name
        WHERE
            tc.constraint_type = 'FOREIGN KEY'
            AND kcu.table_schema = :schema
            AND kcu.table_name = :table
    ";


        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('column_name', 'column_name');
        $rsm->addScalarResult('referenced_schema', 'referenced_schema');
        $rsm->addScalarResult('referenced_table', 'referenced_table');
        $rsm->addScalarResult('referenced_column', 'referenced_column');


        $query = $this->entityManager->createNativeQuery($query, $rsm);
        $query->setParameter("schema", $schema);
        $query->setParameter("table", $tableName);
        $result= $query->getResult();


        $foreignKeys = [];
        foreach ($result as $fk) {
            $foreignKeys[$fk['column_name']] = [
                'referenced_schema' => $fk['referenced_schema'],
                'referenced_table' => $fk['referenced_table'],
                'referenced_column' => $fk['referenced_column'],
            ];
        }

        return $foreignKeys;
    }

    /**
     * Retourne les sequences PostgreSQL associees aux colonnes auto-generees.
     *
     * @return array<string, array{sequence_name: string, schema: string}>
     */
    function getSequences(string $schema,string $tableName): array
    {
        global $pdo;

        $query = "
     SELECT
            c.column_name,
            pgc.relname AS sequence_name,
            c.table_schema
        FROM
            information_schema.columns c
        JOIN
            pg_attrdef ad
            ON ad.adrelid = (c.table_schema || '.' || c.table_name)::regclass::oid
            AND ad.adnum = c.ordinal_position
        JOIN
            pg_class pgc
            ON pgc.oid = substring(pg_get_expr(ad.adbin, ad.adrelid) FROM 'nextval\\(''(.*?)''')::regclass
        WHERE
            c.table_schema = :schema
            AND c.table_name = :table
            AND c.column_default LIKE 'nextval(%'
    ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('column_name', 'column_name');
        $rsm->addScalarResult('sequence_name', 'sequence_name');
        $rsm->addScalarResult('table_schema', 'table_schema');


        $query = $this->entityManager->createNativeQuery($query, $rsm);
        $query->setParameter("schema", $schema);
        $query->setParameter("table", $tableName);
        $result= $query->getResult();

        $sequences = [];
        foreach ($result as $sequence) {
            $sequences[$sequence['column_name']] = [
                'sequence_name' => $sequence['sequence_name'],
                'schema' => $sequence['table_schema']
            ];
        }

        return $sequences;
    }

    /**
     * Retourne les index non uniques de la table.
     *
     * @return array<string, array{columns: list<string>, is_unique: mixed, is_primary: mixed}>
     */
    function getIndexes(string $schema, string $tableName): array
    {

        $query = "
        SELECT
            i.relname AS index_name,
            a.attname AS column_name,
            ix.indisunique AS is_unique,
            ix.indisprimary AS is_primary
        FROM
            pg_class t
        JOIN
            pg_index ix ON t.oid = ix.indrelid
        JOIN
            pg_class i ON i.oid = ix.indexrelid
        JOIN
            pg_attribute a ON a.attnum = ANY(ix.indkey) AND a.attrelid = t.oid
        WHERE
            t.relname = :table
            AND t.relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = :schema)
            AND ix.indisunique = 'f'  -- Esto asegura que solo traemos índices normales, no únicos
    ";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('index_name', 'index_name');
        $rsm->addScalarResult('column_name', 'column_name');
        $rsm->addScalarResult('is_unique', 'is_unique');
        $rsm->addScalarResult('is_primary', 'is_primary');


        $query = $this->entityManager->createNativeQuery($query, $rsm);
        $query->setParameter("schema", $schema);
        $query->setParameter("table", $tableName);
        $result= $query->getResult();

        $indexes = [];
        foreach ($result as $index) {
            $indexName = $index['index_name'];
            if (!isset($indexes[$indexName])) {
                $indexes[$indexName] = [
                    'columns' => [],
                    'is_unique' => $index['is_unique'] ,
                    'is_primary' => $index['is_primary'] ,
                ];
            }
            $indexes[$indexName]['columns'][] = $index['column_name'];
        }

        return $indexes;
    }

    /**
     * Retourne les contraintes d'unicite par nom de contrainte.
     *
     * @return array<string, list<string>>
     */
    function getUniqueConstraints(string $schema, string $tableName): array
    {

        $query = "


         SELECT
            i.relname AS constraint_name,
            a.attname AS column_name
        FROM
            pg_class t
        JOIN
            pg_index ix ON t.oid = ix.indrelid
        JOIN
            pg_class i ON i.oid = ix.indexrelid
        JOIN
            pg_attribute a ON a.attnum = ANY(ix.indkey) AND a.attrelid = t.oid
        WHERE
            t.relname = :table
            AND t.relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = :schema)
            AND i.relname = (
                SELECT DISTINCT
                    conname AS constraint_name
                FROM
                    pg_constraint c
                JOIN
                    pg_attribute a ON a.attnum = ANY(c.conkey)
                WHERE
                    c.contype = 'u'  -- Filtramos solo las restricciones de unicidad
                    AND c.connamespace = (SELECT oid FROM pg_namespace WHERE nspname = :schema)
                    AND c.conrelid = (
                        SELECT oid
                        FROM pg_class
                        WHERE relname = :table
                        AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = :schema)
                    )
            );
    ";
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('constraint_name', 'constraint_name');
        $rsm->addScalarResult('column_name', 'column_name');



        $query = $this->entityManager->createNativeQuery($query, $rsm);
        $query->setParameter("schema", $schema);
        $query->setParameter("table", $tableName);
        $result= $query->getResult();


        $constraints = [];
        foreach ($result as $row) {
            $constraints[$row['constraint_name']][] = $row['column_name'];
        }

        return $constraints;
    }



    /**
     * Mappe un type PostgreSQL vers un type PHP.
     */
    function mapColumnTypeToPhp(string $dbType): string
    {
        return match ($dbType) {
            'integer', 'smallint', 'bigint'                                     => 'int',
            'real', 'double precision', 'decimal'                               => 'float',
            'numeric'                                                           => 'string',
            'character varying', 'text', 'char'                                 => 'string',
            'boolean'                                                           => 'bool',
            'timestamp without time zone', 'timestamp with time zone'           => 'DateTimeInterface',
            'date'                                                              => 'DateTimeInterface',
            'time without time zone' , 'time with time zone'                    => 'DateTimeInterface',
            default => 'mixed',
        };
    }

    /**
     * Mappe un type PostgreSQL vers une constante Doctrine DBAL Types.
     */
    function mapColumnTypeToPhpTypes(string $dbType): string
    {
        return match ($dbType) {
            'integer', 'smallint', 'bigint'                                     => 'Types::INTEGER',
            'real', 'double precision', 'decimal'                               => 'Types::FLOAT',
            'numeric'                                                           => 'Types::DECIMAL',
            'character varying', 'text', 'char'                                 => 'Types::STRING',
            'boolean'                                                           => 'Types::BOOLEAN',
            'timestamp without time zone', 'timestamp with time zone'           => 'Types::DATETIME_MUTABLE',
            'date'                                                              => 'Types::DATE_MUTABLE',
            'time without time zone' , 'time with time zone'                    => 'Types::TIME_MUTABLE',
            default => 'mixed',
        };
    }

    /**
     * Convertit un identifiant snake_case en nom de classe PascalCase.
     */
    function snakeToPascalCase(string $snakeCase): string {
        $snakeCase = str_replace('_', ' ', $snakeCase);
        $pascalCase = ucwords($snakeCase);
        return str_replace(' ', '', $pascalCase);
    }




}
