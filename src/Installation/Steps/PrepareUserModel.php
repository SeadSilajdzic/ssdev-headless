<?php

namespace Ssdev\Headless\Installation\Steps;

use Illuminate\Contracts\Config\Repository;
use Ssdev\Headless\Installation\InstallContext;
use Ssdev\Headless\Installation\InstallStep;
use Ssdev\Headless\Installation\StepResult;

/**
 * Edits only the shapes the Laravel 12 and 13 skeletons ship; anything else gets manual instructions.
 */
final class PrepareUserModel implements InstallStep
{
    private const DESCRIPTION = 'Prepare app/Models/User.php';

    private const PATH = 'app/Models/User.php';

    private const HAS_ROLES = 'HasRoles';

    private const HAS_ROLES_IMPORT = 'use Spatie\Permission\Traits\HasRoles;';

    private const MUST_VERIFY_EMAIL = 'MustVerifyEmail';

    private const MUST_VERIFY_EMAIL_IMPORT = 'use Illuminate\Contracts\Auth\MustVerifyEmail;';

    private const CLASS_DECLARATION = '/^class User extends Authenticatable(?: implements ([^{\r\n]+?))?[ \t]*$/m';

    private const TRAIT_LIST = '/^([ \t]+)use ([\w\\\\]+(?:, *[\w\\\\]+)*);/m';

    private const IMPORT = '/^use [^;]+;[ \t]*$/m';

    public function __construct(private Repository $config) {}

    public function handle(InstallContext $context): StepResult
    {
        $files = $context->files;

        if (! $files->exists(self::PATH)) {
            return $this->manual();
        }

        $original = $files->get(self::PATH);
        $code = $this->addHasRoles($original);

        if ($code !== null && $this->config->get('headless.email_verification')) {
            $code = $this->addMustVerifyEmail($code);
        }

        if ($code === null) {
            return $this->manual();
        }

        if ($code === $original) {
            return StepResult::skipped(self::DESCRIPTION);
        }

        $files->put(self::PATH, $code);

        return StepResult::changed(self::DESCRIPTION);
    }

    private function addHasRoles(string $code): ?string
    {
        if (preg_match(self::CLASS_DECLARATION, $code, $class, PREG_OFFSET_CAPTURE) !== 1
            || preg_match(self::TRAIT_LIST, $code, $traits, PREG_OFFSET_CAPTURE, $class[0][1]) !== 1) {
            return null;
        }

        $names = array_map('trim', explode(',', $traits[2][0]));

        if (! in_array(self::HAS_ROLES, $names, true)) {
            $names[] = self::HAS_ROLES;
            sort($names, SORT_STRING);
            $line = $traits[1][0].'use '.implode(', ', $names).';';
            $code = substr_replace($code, $line, $traits[0][1], strlen($traits[0][0]));
        }

        return $this->addImport($code, self::HAS_ROLES_IMPORT);
    }

    private function addMustVerifyEmail(string $code): ?string
    {
        $code = str_replace('// '.self::MUST_VERIFY_EMAIL_IMPORT, self::MUST_VERIFY_EMAIL_IMPORT, $code);
        $code = $this->addImport($code, self::MUST_VERIFY_EMAIL_IMPORT);

        if ($code === null || preg_match(self::CLASS_DECLARATION, $code, $class) !== 1) {
            return null;
        }

        $interfaces = isset($class[1]) ? array_map('trim', explode(',', $class[1])) : [];

        if (in_array(self::MUST_VERIFY_EMAIL, $interfaces, true)) {
            return $code;
        }

        $interfaces[] = self::MUST_VERIFY_EMAIL;
        $declaration = 'class User extends Authenticatable implements '.implode(', ', $interfaces);

        return (string) preg_replace(self::CLASS_DECLARATION, $declaration, $code, 1);
    }

    private function addImport(string $code, string $import): ?string
    {
        if (str_contains($code, "\n".$import)) {
            return $code;
        }

        if (preg_match_all(self::IMPORT, $code, $imports, PREG_OFFSET_CAPTURE) === 0) {
            return null;
        }

        $last = end($imports[0]);

        return substr_replace($code, $last[0]."\n".$import, $last[1], strlen($last[0]));
    }

    private function manual(): StepResult
    {
        return StepResult::manual(
            self::DESCRIPTION,
            'Add the Spatie\Permission\Traits\HasRoles trait to app/Models/User.php'
            .($this->config->get('headless.email_verification') ? ' and implement Illuminate\Contracts\Auth\MustVerifyEmail.' : '.'),
        );
    }
}
