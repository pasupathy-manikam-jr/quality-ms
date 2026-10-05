<?php

namespace App\Http\Controllers;

use App\Models\Calibration;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CalibrationFileController extends Controller
{
    /**
     * Download the calibration certificate (route requires manage-gauges).
     */
    public function __invoke(Calibration $calibration): StreamedResponse
    {
        return $calibration->downloadUpload();
    }
}
