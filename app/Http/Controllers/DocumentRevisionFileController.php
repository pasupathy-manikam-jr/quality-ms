<?php

namespace App\Http\Controllers;

use App\Models\DocumentRevision;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentRevisionFileController extends Controller
{
    /**
     * Download a revision's file: anyone who manages documents, or a person asked to read it.
     */
    public function __invoke(DocumentRevision $revision): StreamedResponse
    {
        $user = request()->user();

        abort_unless(
            $user !== null && ($user->can('manage-documents') || $revision->readers()->whereKey($user->id)->exists()),
            403,
        );

        return $revision->downloadUpload();
    }
}
