<?php

declare(strict_types=1);

namespace YourStoryz\Import;

use Throwable;
use YourStoryz\Api\ApiException;
use YourStoryz\Settings;
use YourStoryz\Source;
use YourStoryz\SourceSettings;
use YourStoryz\StoryCatalog;

/**
 * Single entry point for imports: the daily cron event and the "Nu importeren" button both run through here,
 * so the lock and the token/source checks apply to both.
 */
final class ImportRunner
{
    public const HOOK = 'yourstoryz_import';

    public const LOCK = 'yourstoryz_import_lock';

    public const LOCK_TTL = 900;

    public const STATUS_DONE = 'done';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_NO_TOKEN = 'no_token';

    public const STATUS_NO_SOURCE = 'no_source';

    public const STATUS_ERROR = 'error';

    public function __construct(
        private readonly Settings $settings,
        private readonly SourceSettings $source,
        private readonly StoryCatalog $catalog,
        private readonly Importer $importer = new Importer,
    ) {}

    public static function schedule(): void
    {
        if (! wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'daily', self::HOOK);
        }
    }

    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * @return array{status: string, result: ImportResult|null, message: string}
     */
    public function run(): array
    {
        if (! $this->settings->hasToken()) {
            return $this->outcome(self::STATUS_NO_TOKEN);
        }

        $source = $this->source->source();

        if (! $source instanceof Source) {
            return $this->outcome(self::STATUS_NO_SOURCE);
        }

        if (! $this->acquireLock()) {
            return $this->outcome(self::STATUS_LOCKED);
        }

        try {
            if (function_exists('wp_raise_memory_limit')) {
                wp_raise_memory_limit('admin');
            }

            if (function_exists('set_time_limit')) {
                @set_time_limit(0);
            }

            // The catalog is cached for 10 minutes; an import must see the current API state, so drop the cache first.
            $this->catalog->refresh($source);
            $stories = $this->catalog->load($source)['stories'];

            return $this->outcome(self::STATUS_DONE, $this->importer->import($stories));
        } catch (ApiException $exception) {
            return $this->outcome(self::STATUS_ERROR, null, $exception->userMessage());
        } catch (Throwable $throwable) {
            return $this->outcome(self::STATUS_ERROR, null, $throwable->getMessage());
        } finally {
            delete_option(self::LOCK);
        }
    }

    /**
     * add_option() inserts atomically and fails when the row exists, so only one request wins the lock.
     * A lock older than LOCK_TTL belongs to a run that died without releasing it and is taken over.
     */
    private function acquireLock(): bool
    {
        if (add_option(self::LOCK, time(), '', false)) {
            return true;
        }

        $lockedAt = (int) get_option(self::LOCK, 0);

        if (time() - $lockedAt < self::LOCK_TTL) {
            return false;
        }

        return update_option(self::LOCK, time(), false);
    }

    /**
     * @return array{status: string, result: ImportResult|null, message: string}
     */
    private function outcome(string $status, ?ImportResult $result = null, string $message = ''): array
    {
        return ['status' => $status, 'result' => $result, 'message' => $message];
    }
}
