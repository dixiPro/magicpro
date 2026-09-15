<?php

namespace MagicProSrc\Console\Aws;

/**
 * Removes what an earlier setup left in AWS: an access key, or a whole IAM user
 * with its key and its policy.
 *
 * A change of keys leaves something behind, whichever way it is done. Change
 * the key under one user — the old key stays until somebody deletes it, and AWS
 * allows only two. Change the user — the old user stays with its key and its
 * rights, and after a year there are a dozen of them, each able to send mail
 * as the domain of the site.
 *
 * Doing it by hand takes four steps in a certain order — keys, policy, user —
 * and AWS refuses the last one until the first two are done. That is what this
 * command is for.
 *
 * It decides nothing on its own: the name is typed by hand, everything found is
 * printed, and nothing is removed without an answer. The one thing it refuses
 * outright is deleting the key the site is working with right now.
 */
class RemoveCommand extends AwsCommand
{
    protected $signature = 'magicpro:aws-remove
        {--user= : IAM user to remove with its key and policy}
        {--key= : one access key to remove, the user is left alone}';

    protected $description = 'Removes an IAM user of an old setup, or one access key';

    public function handle(): int
    {
        $user = trim((string) $this->option('user'));
        $key  = trim((string) $this->option('key'));

        if (($user === '') === ($key === '')) {
            $this->err('name one thing to remove: --user=name or --key=AKIA…');

            return self::FAILURE;
        }

        if (! $this->askSetupKey()) {
            return self::FAILURE;
        }

        try {
            return $key !== '' ? $this->removeKey($key) : $this->removeUser($user);
        } catch (\Throwable $e) {
            $this->err($this->awsMessage($e));

            return self::FAILURE;
        }
    }

    /**
     * One key, by its id.
     *
     * The owner is asked of AWS: a key does not say whose it is, and guessing
     * by the name in the settings file is exactly how the wrong one gets
     * deleted.
     */
    private function removeKey(string $key): int
    {
        $used = $this->iam()->getAccessKeyLastUsed(['AccessKeyId' => $key]);

        $owner = (string) $used->get('UserName');
        $last  = $used->get('AccessKeyLastUsed')['LastUsedDate'] ?? null;

        $this->line('');
        $this->line('Key ' . $key);
        $this->line('  user       ' . $owner);
        $this->line('  last used  ' . ($last ? $this->when($last) : 'never'));

        if ($this->inUse($key)) {
            return self::FAILURE;
        }

        if (! $this->confirm('Delete this key?', false)) {
            $this->wait('nothing removed');

            return self::SUCCESS;
        }

        $this->iam()->deleteAccessKey(['UserName' => $owner, 'AccessKeyId' => $key]);

        $this->ok('key ' . $key . ' deleted');

        return self::SUCCESS;
    }

    /**
     * The user with everything of his own: keys first, then the inline
     * policies, then himself. AWS refuses to delete a user who still holds
     * something, and that order is the reason this command exists.
     */
    private function removeUser(string $user): int
    {
        $iam = $this->iam();

        $iam->getUser(['UserName' => $user]);

        $keys     = $iam->listAccessKeys(['UserName' => $user])->get('AccessKeyMetadata') ?? [];
        $policies = $iam->listUserPolicies(['UserName' => $user])->get('PolicyNames') ?? [];
        $attached = $iam->listAttachedUserPolicies(['UserName' => $user])->get('AttachedPolicies') ?? [];

        $this->line('');
        $this->line('IAM user ' . $user);

        foreach ($keys as $found) {
            $this->line('  key       ' . $found['AccessKeyId']
                . '  ' . str_pad((string) ($found['Status'] ?? ''), 8)
                . '  created ' . $this->when($found['CreateDate'] ?? null));
        }

        foreach ($policies as $name) {
            $this->line('  policy    ' . $name . '  (inline, goes with the user)');
        }

        foreach ($attached as $policy) {
            $this->line('  attached  ' . $policy['PolicyName'] . '  (stays, only detached)');
        }

        foreach ($keys as $found) {
            if ($this->inUse((string) $found['AccessKeyId'])) {
                return self::FAILURE;
            }
        }

        if (! $this->confirm('Delete the user with all of the above?', false)) {
            $this->wait('nothing removed');

            return self::SUCCESS;
        }

        foreach ($keys as $found) {
            $iam->deleteAccessKey(['UserName' => $user, 'AccessKeyId' => $found['AccessKeyId']]);
            $this->ok('key ' . $found['AccessKeyId'] . ' deleted');
        }

        foreach ($policies as $name) {
            $iam->deleteUserPolicy(['UserName' => $user, 'PolicyName' => $name]);
            $this->ok('policy ' . $name . ' deleted');
        }

        // an attached policy is somebody else's object: it may serve other
        // users, so it is unhooked and left where it is
        foreach ($attached as $policy) {
            $iam->detachUserPolicy(['UserName' => $user, 'PolicyArn' => $policy['PolicyArn']]);
            $this->ok('policy ' . $policy['PolicyName'] . ' detached');
        }

        $iam->deleteUser(['UserName' => $user]);

        $this->ok('IAM user ' . $user . ' deleted');

        return self::SUCCESS;
    }

    /**
     * The key the project is working with right now.
     *
     * Read from the environment of the project and not from the comment in the
     * result file: a comment is written once and never corrected, while
     * `AWS_ACCESS_KEY_ID` is what actually goes to SES.
     */
    private function inUse(string $key): bool
    {
        $current = trim((string) config('services.ses.key', ''));

        if ($current === '' || $current !== $key) {
            return false;
        }

        $this->err('this is the key the site sends mail with, AWS_ACCESS_KEY_ID of the project.');
        $this->line('Issue a new one, put it into the project, check that mail goes — and then delete this.');

        return true;
    }

    /** A date of AWS as a day, whatever type the sdk handed over. */
    private function when(mixed $date): string
    {
        try {
            return (new \DateTimeImmutable((string) $date))->format('Y-m-d');
        } catch (\Throwable) {
            return 'unknown';
        }
    }
}
