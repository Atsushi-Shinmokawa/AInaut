<?php

// app/Http/Controllers/ReadingLogController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReadingLogRequest;
use App\Http\Requests\UpdateReadingLogRequest;
use App\Models\ReadingLog;
use App\Services\ReadingLogService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReadingLogController extends Controller
{
    public function __construct(
        private readonly ReadingLogService $readingLogService,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();   

        $readingLogs = $this->readingLogService->listForUser($user);

        return Inertia::render('ReadingLogs/Index', [
            'readingLogs' => $readingLogs->map(function (ReadingLog $log) {
                return [
                    'id'     => $log->id,
                    'status' => $log->status,
                    'book'   => [
                        'id'     => $log->book->id,
                        'title'  => $log->book->title,
                        'author' => optional($log->book->author)->name,
                    ],
                ];
            }),
            'statuses' => ReadingLog::statuses(),
        ]);
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
