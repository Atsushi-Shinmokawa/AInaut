<?php

// app/Http/Controllers/ReadingLogController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingLogRequest;
use App\Http\Requests\UpdateReadingLogRequest;
use App\Models\ReadingLog;
use App\Models\ReadingNote;
use App\Services\ReadingLogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;

class ReadingLogController extends Controller
{
    public function __construct(
        private readonly ReadingLogService $readingLogService,
    ) {}

    public function list(): Response
{
    $props = $this->readingLogService->list(Auth::user());

    return Inertia::render('ReadingLogs/Index', $props);
}


    public function store(StoreReadingLogRequest $request): RedirectResponse
    {
        $this->readingLogService->storeOrUpdate(
            $request->user(),
            $request->validated(),
        );

        return back()->with('success', '本棚に追加しました。');
    }

    public function update(UpdateReadingLogRequest $request, ReadingLog $readingLog): RedirectResponse
    {
        $this->readingLogService->updateStatus(
            $readingLog,
            $request->validated('status'),
        );

        return back()->with('success', 'ステータスを更新しました。');
    }

    public function destroy(ReadingLog $readingLog): RedirectResponse
    {
        // Policy 生やしたとき用のフック（今はつけたままでOK）
        $this->authorize('delete', $readingLog);

        $this->readingLogService->delete($readingLog);

        return back()->with('success', '本棚から削除しました。');
    }
}
