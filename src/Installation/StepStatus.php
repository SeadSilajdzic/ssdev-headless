<?php

namespace Ssdev\Headless\Installation;

enum StepStatus: string
{
    case Changed = 'changed';
    case Skipped = 'skipped';
    case Manual = 'manual';
}
