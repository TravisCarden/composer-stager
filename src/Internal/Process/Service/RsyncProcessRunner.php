<?php declare(strict_types=1);

namespace PhpTuf\ComposerStager\Internal\Process\Service;

/**
 * Before using this class outside the internal layer, consider a
 * higher-level abstraction, e.g.:
 *
 * @see \PhpTuf\ComposerStager\API\FileSyncer\Service\FileSyncerInterface
 * @see \PhpTuf\ComposerStager\Internal\FileSyncer\Factory\FileSyncerFactoryInterface
 *
 * @package Process
 *
 * @internal Don't depend on this class. It may be changed or removed at any time without notice.
 */
final class RsyncProcessRunner extends AbstractProcessRunner implements RsyncProcessRunnerInterface
{
    protected function executableName(): string
    {
        return 'rsync'; // @codeCoverageIgnore
    }
}
