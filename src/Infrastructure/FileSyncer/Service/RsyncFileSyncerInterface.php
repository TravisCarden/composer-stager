<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Infrastructure\FileSyncer\Service;

use PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface;

/**
 * Provides an rsync-based file syncer.
 *
 * This interface exists solely to facilitate autowiring dependencies through type hinting.
 *
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 *
 * @package FileSyncer
 *
 * @api
 */
interface RsyncFileSyncerInterface extends FileSyncerInterface
{
}
