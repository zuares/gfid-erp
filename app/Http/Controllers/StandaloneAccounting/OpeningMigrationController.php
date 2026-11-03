<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneOpeningMigration;
use App\Models\SystemSetting;
use App\Services\Accounting\StandaloneOpeningMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpeningMigrationController extends Controller
{
    public function __construct(private StandaloneOpeningMigrationService $service) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'as_of' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'preview' => ['nullable', 'uuid'],
        ]);
        $asOf = $data['as_of'] ?? max(now()->subDay()->toDateString(), SystemSetting::cutoffDateString() ?? '');
        $saved = isset($data['preview']) ? $request->session()->get('opening_migration.previews.'.$data['preview']) : null;
        if (isset($data['preview']) && ! $saved) {
            return to_route('standalone-accounting.opening-migration.index')->withErrors(['migration' => 'Preview tidak ditemukan. Ambil saldo dan tinjau ulang mapping.']);
        }

        return $this->page($request, $asOf, $saved);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'as_of' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'mapping' => ['required', 'array'],
            'mapping.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $token = (string) Str::uuid();
        $previews = $request->session()->get('opening_migration.previews', []);
        $previews[$token] = [
            'token' => $token,
            'snapshot' => $this->service->preview($data['as_of'], $data['mapping']),
            'created_at' => now()->timestamp,
            'review' => ! $request->boolean('edit_mapping'),
        ];
        $request->session()->put('opening_migration.previews', array_slice($previews, -5, null, true));

        return to_route('standalone-accounting.opening-migration.index', ['preview' => $token]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['preview_token' => ['required', 'uuid'], 'confirmed' => ['accepted']]);
        $preview = $request->session()->get('opening_migration.previews.'.$data['preview_token']);
        if (! $preview || ! $preview['review'] || $preview['created_at'] < now()->subMinutes(30)->timestamp) {
            throw ValidationException::withMessages(['migration' => 'Preview tidak ditemukan atau sudah kedaluwarsa (30 menit). Buat preview baru.']);
        }
        $this->service->post($preview['snapshot'], $data['preview_token'], (int) $request->user()->id);

        return to_route('standalone-accounting.opening-migration.index')->with('message', 'Opening Migrasi berhasil diposting ke Akuntansi Baru.');
    }

    public function correct(Request $request)
    {
        $data = $request->validate([
            'request_token' => ['required', 'uuid'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reason' => ['required', 'string', 'max:2000'],
            'confirmed' => ['accepted'],
            'lines' => ['required', 'array', 'min:2', 'max:100'],
            'lines.*.account_id' => ['nullable', 'integer'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
        ]);
        if (! in_array($data['request_token'], $request->session()->get('opening_migration.correction_tokens', []), true)) {
            throw ValidationException::withMessages(['correction' => 'Form koreksi kedaluwarsa. Buka kembali Opening Migrasi.']);
        }
        $this->service->correct($data, (int) $request->user()->id);

        return to_route('standalone-accounting.opening-migration.index')->with('message', 'Koreksi dicatat sebagai jurnal terpisah. Snapshot opening tetap tersimpan.');
    }

    private function page(Request $request, string $asOf, ?array $saved = null)
    {
        $migration = StandaloneOpeningMigration::with(['journal', 'poster', 'corrections.journal', 'corrections.poster'])->findOrFail(1);
        $snapshot = $migration->posted_at ? $migration->snapshot : ($saved['snapshot'] ?? $this->service->preview($asOf));
        $review = (bool) ($saved['review'] ?? false);
        $previewToken = null;
        $correctionToken = null;
        if ($review && ! $migration->posted_at && ! $snapshot['issues']) {
            $previewToken = $saved['token'];
        }
        if ($migration->posted_at) {
            $correctionToken = (string) Str::uuid();
            $tokens = $request->session()->get('opening_migration.correction_tokens', []);
            $tokens[] = $correctionToken;
            $request->session()->put('opening_migration.correction_tokens', array_slice($tokens, -10));
        }

        return view('standalone_accounting.opening_migration.index', [
            'migration' => $migration, 'snapshot' => $snapshot, 'previewToken' => $previewToken,
            'correctionToken' => $correctionToken, 'review' => $review,
            'accounts' => StandaloneAccountingAccount::where('is_active', true)->orderBy('code')->get(),
        ]);
    }
}
