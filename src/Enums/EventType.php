<?php

namespace Webkul\DAM\Enums;

enum EventType: string
{
    const DELETE_DIRECTORY = 'delete_directory';

    const RENAME_DIRECTORY = 'rename_directory';

    const COPY_DIRECTORY_STRUCTURE = 'copy_directory_directory';

    const MOVE_DIRECTORY_STRUCTURE = 'move_directory_directory';
}
