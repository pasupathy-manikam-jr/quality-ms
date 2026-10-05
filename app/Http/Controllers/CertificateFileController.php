<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateFileController extends Controller
{
    /**
     * Download the certificate's original scan or PDF (route requires manage-certificates).
     */
    public function __invoke(Certificate $certificate): StreamedResponse
    {
        return $certificate->downloadUpload();
    }
}
