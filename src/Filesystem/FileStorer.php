<?php

namespace Webkul\DAM\Filesystem;

use Webkul\Core\Filesystem\FileStorer as BaseFileStorer;

class FileStorer extends BaseFileStorer
{
    public function store(string $path, mixed $file, $fileName = null, array $options = [])
    {
        $name = $fileName ?? $this->getFileName($file);

        return $this->storeAs($path, $name, $file, $options);
    }

    public function storeAs(string $path, string $name, mixed $file, array $options = [])
    {
        $result = parent::storeAs($path, $name, $file, $options);

        if ($result === false) {
            throw new \RuntimeException(trans('dam::app.admin.dam.asset.datagrid.storage-upload-failed', ['name' => $name]));
        }

        return $result;
    }
}
