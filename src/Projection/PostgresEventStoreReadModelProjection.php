<?php
/**
 * This file is part of the prooph/pdo-event-store.
 * (c) 2016-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\PDO\Projection;

use PDO;
use Prooph\EventStore\PDO\PostgresEventStore;
use Prooph\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\StreamName;

final class PostgresEventStoreReadModelProjection extends AbstractPDOReadModelProjection
{
    use PDOQueryTrait;
    use PDOEventStoreProjectionTrait;

    /**
     * @var PostgresEventStore
     */
    protected $eventStore;

    /**
     * @var string
     */
    protected $projectionsTable;

    public function __construct(
        PostgresEventStore $eventStore,
        PDO $connection,
        string $name,
        ReadModelProjection $readModelProjection,
        string $eventStreamsTable,
        string $projectionsTable,
        int $lockTimeoutMs,
        int $cacheSize
    ) {
        parent::__construct(
            $eventStore,
            $connection,
            $name,
            $readModelProjection,
            $eventStreamsTable,
            $projectionsTable,
            $lockTimeoutMs,
            $cacheSize
        );
    }

    public function delete(bool $deleteEmittedEvents): void
    {
        $this->eventStore->beginTransaction();

        $deleteProjectionSql = <<<EOT
DELETE FROM $this->projectionsTable WHERE name = ?;
EOT;
        $statement = $this->connection->prepare($deleteProjectionSql);
        $statement->execute([$this->name]);

        if ($deleteEmittedEvents) {
            $this->eventStore->delete(new StreamName($this->name));
        }

        $this->eventStore->commit();

        parent::delete($deleteEmittedEvents);
    }
}