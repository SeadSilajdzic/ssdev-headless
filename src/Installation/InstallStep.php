<?php

namespace Ssdev\Headless\Installation;

interface InstallStep
{
    public function handle(InstallContext $context): StepResult;
}
