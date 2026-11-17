<?php

namespace JeffersonGoncalves\LgpdMinors\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\LgpdMinors\MinorsManager;
use JeffersonGoncalves\LgpdMinors\Models\Suspension;

class MinorsCommand extends Command
{
    public $signature = 'lgpd:minors {--json : Print the report as JSON}';

    public $description = 'List accounts up to 16 without a guardian and suspensions waiting for an appeal decision';

    public function handle(MinorsManager $minors): int
    {
        $missing = $minors->missingGuardian()
            ->map(fn (Model $subject): array => ['type' => $subject->getMorphClass(), 'id' => $subject->getKey()])
            ->all();

        $appeals = Suspension::query()->scopes('awaitingDecision')->oldest('appealed_at')->get()
            ->map(fn (Suspension $s): array => [
                'id' => $s->id,
                'type' => $s->subject_type,
                'subject_id' => $s->subject_id,
                'appealed_at' => $s->appealed_at?->toIso8601String(),
            ])
            ->all();

        if ($this->option('json')) {
            $this->line((string) json_encode(['missing_guardian' => $missing, 'appeals' => $appeals], JSON_PRETTY_PRINT));
        } else {
            $this->components->info(count($missing).' account(s) up to 16 without a guardian (art. 24).');
            $this->table(['Type', 'ID'], $missing);
            $this->components->info(count($appeals).' appeal(s) waiting for a decision (art. 24, §4).');
            $this->table(['Suspension', 'Type', 'Subject', 'Appealed at'], $appeals);
        }

        return $missing === [] && $appeals === [] ? self::SUCCESS : self::FAILURE;
    }
}
