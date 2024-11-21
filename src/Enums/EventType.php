<?php

namespace Webkul\DAM\Enums;

enum EventType: string
{
    case DELETE_DIRECTORY = 'delete_directory';
    case RENAME_DIRECTORY = 'rename_directory';
    case COPY_DIRECTORY_STRUCTURE = 'copy_directory_structure';
    case MOVE_DIRECTORY_STRUCTURE = 'move_directory_structure';
}
