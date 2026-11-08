<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/**
 * Add to a model to record every create / update / delete / restore in the
 * activity log, with the old and new values of changed fields.
 *
 * A model can set:
 *   protected array $logIgnore = ['field', …];   // never logged (e.g. timestamps)
 *   protected array $logHidden = ['password'];    // logged as "changed" without the values
 *   public function activityLabel(): string       // how the record is named in the log
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record('created', 'Created ' . $model->activityName(), $model));

        static::updated(function ($model) {
            $changes = $model->activityChanges();
            if ($changes) {
                $fields = implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), array_keys($changes)));
                ActivityLog::record('updated', 'Updated ' . $model->activityName() . " ({$fields})", $model, $changes);
            }
        });

        static::deleted(fn ($model) => ActivityLog::record('deleted', 'Deleted ' . $model->activityName(), $model));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => ActivityLog::record('restored', 'Restored ' . $model->activityName(), $model));
        }
    }

    // "employee Bobby Lim", "department Finance", …
    public function activityName(): string
    {
        $type = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename($this)));
        $label = method_exists($this, 'activityLabel') ? $this->activityLabel() : '#' . $this->getKey();

        return "{$type} {$label}";
    }

    // { field: { old, new } } for the fields that really changed.
    protected function activityChanges(): array
    {
        $ignore = array_merge(['updated_at', 'created_at', 'deleted_at', 'remember_token'], $this->logIgnore ?? []);
        $hidden = $this->logHidden ?? [];
        $changes = [];

        foreach ($this->getChanges() as $field => $new) {
            if (in_array($field, $ignore, true)) {
                continue;
            }
            $changes[$field] = in_array($field, $hidden, true)
                ? ['old' => '(hidden)', 'new' => '(hidden)']
                : ['old' => $this->getOriginal($field) instanceof \DateTimeInterface ? $this->getOriginal($field)->format('Y-m-d') : $this->getOriginal($field),
                    'new' => $this->getAttribute($field) instanceof \DateTimeInterface ? $this->getAttribute($field)->format('Y-m-d') : $new];
        }

        return $changes;
    }
}
