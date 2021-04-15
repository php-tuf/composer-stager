<?php

namespace PhpTuf\ComposerStager\Filesystem;

use PhpTuf\ComposerStager\Exception\IOException;

class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    /**
     * @throws \PhpTuf\ComposerStager\Exception\IOException
     */
    public function getcwd(): string
    {
        $cwd = getcwd();
        if ($cwd === false) {
            throw new IOException('Cannot access the current working directory.'); // @codeCoverageIgnore
        }
        return $cwd;
    }

    /**
     * @codeCoverageIgnore
     */
    public function isWritable(string $filename): bool
    {
        return is_writable($filename);
    }
}
