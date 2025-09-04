<?php
namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Str;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created  (fn($m) => $m->writeLog('created'));
        static::updated  (fn($m) => $m->writeLog('updated'));
        static::deleted  (fn($m) => $m->writeLog('deleted'));
    }

    /** label entitas; override di model kalau mau: prospek/klien/dll */
    protected function getEntityType(): string
    {
        return Str::snake(class_basename($this)); // default: TagihanKlien => tagihan_klien
    }

    /** catat aksi non-CRUD */
    public function logCustom(string $action, array $after = null, array $before = null): void
    {
        $this->writeLog($action, $after, $before);
    }

    protected function writeLog(string $action, array $extraAfter = null, array $extraBefore = null): void
    {
        try {
            $attrsAll = $this->getAttributes();
            $origAll  = $this->getOriginal();

            $omit = ['updated_at','created_at'];

            if ($action === 'created') {
                $before = null;
                $after  = array_diff_key($attrsAll, array_flip($omit));
            } elseif ($action === 'updated') {
                $changed = array_keys($this->getChanges());
                $changed = array_values(array_diff($changed, $omit)); // buang noise
                $before  = array_intersect_key($origAll, array_flip($changed));
                $after   = array_intersect_key($attrsAll, array_flip($changed));
                if (empty($changed)) return; // tidak ada perubahan berarti, skip
            } else { // deleted
                $before = array_diff_key($origAll, array_flip($omit));
                $after  = null;
            }

            // merge payload custom kalau ada
            if ($extraBefore) $before = array_merge($before ?? [], $extraBefore);
            if ($extraAfter)  $after  = array_merge($after  ?? [], $extraAfter);

            ActivityLog::create([
                'user_id'     => auth()->id(),
                'entity_type' => $this->getEntityType(),
                'entity_id'   => $this->getKey(),
                'action'      => $action,
                'before'      => $before ?: null,
                'after'       => $after  ?: null,
                'ip'          => request()?->ip(),
                'user_agent'  => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // jangan ganggu transaksi utama
            // report($e);
        }
    }
}
