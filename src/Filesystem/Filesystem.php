<?php

namespace PhpTuf\ComposerStager\Filesystem;

class Filesystem extends \Symfony\Component\Filesystem\Filesystem
{
    public function getcwd(): string
    {
        return getcwd();
    }

    public function isWritable(string $filename): bool
    {
        return is_writable($filename);
    }
}
