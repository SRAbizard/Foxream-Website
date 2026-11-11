<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\StudentDocument;
use App\Support\PrivateFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Opens a candidate's private file (see PrivateFiles): only for the
 * candidate who owns it and admins who manage students. Shown in the
 * browser, or downloaded with ?download=1.
 */
class FileController extends Controller
{
    public function document(Request $request, StudentDocument $document): StreamedResponse
    {
        abort_unless(PrivateFiles::canOpen($request->user(), $document->user_id), 403);

        return $this->send($request, $document->file_path, $document->user?->name.' '.($document->label ?: $document->type));
    }

    public function application(Request $request, Application $application, string $file): StreamedResponse
    {
        abort_unless(in_array($file, Application::FILES, true), 404);
        abort_unless(PrivateFiles::canOpen($request->user(), $application->user_id), 403);

        return $this->send($request, $application->{$file}, $application->user?->name.' '.Str::before($file, '_path'));
    }

    private function send(Request $request, ?string $path, string $name): StreamedResponse
    {
        abort_unless($path && PrivateFiles::disk()->exists($path), 404);

        $filename = Str::slug($name).'.'.pathinfo($path, PATHINFO_EXTENSION);
        $headers  = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];

        return $request->boolean('download')
            ? PrivateFiles::disk()->download($path, $filename, $headers)
            : PrivateFiles::disk()->response($path, $filename, $headers);
    }
}
