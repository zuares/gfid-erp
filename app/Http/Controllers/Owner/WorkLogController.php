<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\OwnerWorkLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkLogController extends Controller
{
    private function ensureOwner(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $ownerEmail = env('OWNER_EMAIL', 'ciciadeliamardani@gmail.com');

        $isOwner =
            (bool) ($user->is_owner ?? false) ||
            (($user->role ?? null) === 'owner') ||
            (($user->email ?? null) === $ownerEmail);

        abort_unless($isOwner, 403, 'Halaman ini hanya bisa diakses owner.');
    }

    public function index(Request $request)
    {
        $this->ensureOwner();

        $activeTab = $request->get('tab', 'progress');
        $activeTab = in_array($activeTab, ['progress', 'done'], true) ? $activeTab : 'progress';

        $logs = OwnerWorkLog::query()
            ->with('creator')
            ->where('status', $activeTab)
            ->when($request->filled('q'), function ($q) use ($request) {
                $keyword = trim((string) $request->q);

                $q->where(function ($qq) use ($keyword) {
                    $qq->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('page_url', 'like', "%{$keyword}%");
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' => OwnerWorkLog::count(),
            'progress' => OwnerWorkLog::where('status', 'progress')->count(),
            'done' => OwnerWorkLog::where('status', 'done')->count(),
        ];

        return view('owner.work_logs.index', [
            'logs' => $logs,
            'summary' => $summary,
            'activeTab' => $activeTab,
            'categories' => OwnerWorkLog::CATEGORIES,
            'statuses' => OwnerWorkLog::STATUSES,
            'priorities' => OwnerWorkLog::PRIORITIES,
        ]);
    }

    public function create()
    {
        $this->ensureOwner();

        return redirect()->route('owner.work-logs.index');
    }

    public function store(Request $request)
    {
        $this->ensureOwner();

        $data = $this->validated($request);
        $data['status'] = 'progress';
        $data['work_date'] = $data['work_date'] ?: now()->toDateString();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        OwnerWorkLog::create($data);

        return redirect()
            ->route('owner.work-logs.index')
            ->with('status', 'success')
            ->with('message', 'Log berhasil ditambahkan.');
    }

    public function show(OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        return view('owner.work_logs.show', [
            'log' => $workLog,
        ]);
    }

    public function edit(OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        return redirect()->route('owner.work-logs.index');
    }

    public function update(Request $request, OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        $data = $this->validated($request);
        $data['updated_by'] = Auth::id();

        $workLog->update($data);

        return redirect()
            ->route('owner.work-logs.index')
            ->with('status', 'success')
            ->with('message', 'Log berhasil diupdate.');
    }

    public function markDone(OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        $workLog->update([
            'status' => 'done',
            'done_at' => now(),
            'completed_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('owner.work-logs.index')
            ->with('status', 'success')
            ->with('message', 'Log ditandai selesai.');
    }

    public function reopen(OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        $workLog->update([
            'status' => 'progress',
            'done_at' => null,
            'completed_at' => null,
            'updated_by' => Auth::id(),
        ]);

        return redirect()
            ->route('owner.work-logs.index')
            ->with('status', 'success')
            ->with('message', 'Log dibuka lagi.');
    }

    public function destroy(OwnerWorkLog $workLog)
    {
        $this->ensureOwner();

        $workLog->delete();

        return redirect()
            ->route('owner.work-logs.index')
            ->with('status', 'success')
            ->with('message', 'Log berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'work_date' => ['nullable', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'string', 'max:50'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
