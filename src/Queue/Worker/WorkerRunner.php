<?php

declare (strict_types=1);
namespace OmniMail\Queue\Worker;

use OmniMail\Discovery\Attribute\Hook;
use OmniMail\Discovery\Attribute\Service;
use OmniMail\Queue\Query\QueueMessageQuery;
use OmniMail\Queue\Trigger\ActionSchedulerTrigger;
/**
 * Entry points for scheduled and direct worker execution.
 *
 * @since 0.1.0
 */
#[Service]
final readonly class WorkerRunner
{
    public const string RUNNER_LEASE_OPTION = 'omni_mail_queue_runner_lease';
    public function __construct(private \OmniMail\Queue\Worker\QueueWorker $queueWorker, private QueueMessageQuery $queueMessageQuery, private ActionSchedulerTrigger $actionSchedulerTrigger)
    {
    }
    /**
     * @since 0.1.0
     */
    public function runNow(int $limit = 25, int $timeLimit = 20): int
    {
        return $this->queueWorker->run($limit, $timeLimit);
    }
    /**
     * @since 0.1.0
     */
    #[Hook(name: ActionSchedulerTrigger::RUN_HOOK, kind: 'action', acceptedArgs: 0)]
    #[Hook(name: ActionSchedulerTrigger::RECURRING_HOOK, kind: 'action', acceptedArgs: 0)]
    public function runScheduled(int $limit = 25, int $timeLimit = 20): int
    {
        if (!$this->acquireRunnerLease($timeLimit)) {
            return 0;
        }
        try {
            $processed = $this->queueWorker->run($limit, $timeLimit);
            $snapshot = $this->queueMessageQuery->getStatusSnapshot();
            if ($snapshot['pending_ready'] > 0) {
                $this->actionSchedulerTrigger->trigger();
            }
            return $processed;
        } finally {
            $this->releaseRunnerLease();
        }
    }
    /**
     * @since 0.1.0
     */
    private function acquireRunnerLease(int $timeLimit): bool
    {
        $ttl = max(30, $timeLimit + 15);
        $expiresAt = time() + $ttl;
        if (add_option(self::RUNNER_LEASE_OPTION, (string) $expiresAt, '', \false)) {
            return \true;
        }
        $existingExpiresAt = (int) get_option(self::RUNNER_LEASE_OPTION, '0');
        if ($existingExpiresAt >= time()) {
            return \false;
        }
        delete_option(self::RUNNER_LEASE_OPTION);
        return add_option(self::RUNNER_LEASE_OPTION, (string) $expiresAt, '', \false);
    }
    /**
     * @since 0.1.0
     */
    private function releaseRunnerLease(): void
    {
        delete_option(self::RUNNER_LEASE_OPTION);
    }
}
