<?php

namespace App\Support;

class SqlitePath
{
    /**
     * Ambil path file SQLite yang sedang dipakai Laravel.
     *
     * - Utama: config('database.connections.sqlite.database')
     * - Fallback: database_path('database.sqlite')
     */
    public static function current(): string
    {
        $path = config('database.connections.sqlite.database');

        if (!$path) {
            $path = database_path('database.sqlite');
        }

        // Kalau bisa di-resolve ke realpath, pakai itu (lebih rapi di log),
        // kalau tidak, tetap kembalikan string original.
        return realpath($path) ?: $path;
    }

    /**
     * Folder backup default: storage/backups
     */
    public static function backupDir(): string
    {
        return storage_path('backups');
    }
}
