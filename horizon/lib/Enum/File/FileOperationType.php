<?php

namespace Horizon\Enum\File;

abstract class FileOperationType
{

    const CREATE = 0x1;
    const MODIFY = 0x2;
    const DELETE = 0x3;

}