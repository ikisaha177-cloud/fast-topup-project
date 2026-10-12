<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Pencatat aktivitas penting (audit trail ringan).
 */
class ActivityLogger
{
    /**
     * Catat sebuah aktivitas.
     *
     * @param  string             $action      kode aksi, mis. "game.create"
     * @param  string             $description deskripsi yang enak dibaca
     * @param  \Illuminate\Database\Eloquent\Model|null  $subject
     * @return \App\Models\ActivityLog|null
     */
    public static function log($action, $description, Model $subject = null)
    {
        try {
            return ActivityLog::create([
                'user_id'      => auth()->id(),
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject ? $subject->getKey() : null,
                'ip'           => request()->ip(),
                'user_agent'   => substr((string) request()->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Log tidak boleh menggagalkan aksi utama.
            report($e);

            return null;
        }
    }
}
