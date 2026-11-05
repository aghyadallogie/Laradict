<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when DeepL can't give us anything usable (e.g. the source language couldn't be detected).
 */
class DeepLException extends RuntimeException
{
}
