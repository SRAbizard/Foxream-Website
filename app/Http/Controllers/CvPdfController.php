<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Pdf;
use App\Support\Rirekisho;
use App\Support\RirekishoDocx;
use App\Support\RirekishoPdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 履歴書 (Japanese CV): the filled Word template (.docx) or a PDF.
 * Students get their own from the Student Dashboard; admins get any
 * student's from the student page. For PDF, ?download=1 downloads,
 * otherwise it opens in the browser.
 */
class CvPdfController extends Controller
{
    public function mine(Request $request, string $format = 'pdf'): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('filament.student.auth.login'));
        }

        return $this->send($request, $request->user(), $format);
    }

    public function admin(Request $request, User $user, string $format = 'pdf'): Response
    {
        if (! $request->user()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }
        abort_unless($request->user()->canManageStudents(), 403);

        return $this->send($request, $user, $format);
    }

    private function send(Request $request, User $user, string $format): Response
    {
        $job = $request->string('job')->limit(160)->toString() ?: null;

        if ($format === 'docx') {
            $name = Rirekisho::filename($user, 'docx');
            $ascii = preg_replace('/[^\x20-\x7E]/', '_', $name);

            return response(RirekishoDocx::render($user, $job), 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="'.$ascii.'"; filename*=UTF-8\'\''.rawurlencode($name),
                'Cache-Control'       => 'private, no-store',
            ]);
        }

        return Pdf::respond(RirekishoPdf::render($user, $job), Rirekisho::filename($user), $request->boolean('download'));
    }
}
