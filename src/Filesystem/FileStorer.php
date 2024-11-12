<?php

namespace Webkul\DAM\FileSystem;

use Webkul\Core\Filesystem\FileStorer as BaseFileStorer;

class FileStorer extends BaseFileStorer
{
    /**
     * {@inheritdoc}
     */
    public function store(string $path, mixed $file, $fileName = null, array $options = [])
    {
        $name = $fileName ?? $this->getFileName($file);

        return $this->storeAs($path, $name, $file, $options);
    }
}
