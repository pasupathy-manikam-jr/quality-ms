<?php

namespace App\Actions\Certificates;

use Anthropic\Beta\Messages\BetaBase64ImageSource;
use Anthropic\Beta\Messages\BetaBase64PDFSource;
use Anthropic\Beta\Messages\BetaImageBlockParam;
use Anthropic\Beta\Messages\BetaRequestDocumentBlock;
use Anthropic\Beta\Messages\BetaTextBlock;
use Anthropic\Beta\Messages\BetaTextBlockParam;
use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Models\Certificate;
use App\Models\MaterialLimit;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Reads a supplier certificate's file (PDF or scan) with Claude and returns the lots and
 * results printed on it, for a person to check before anything is saved.
 */
class ReadCertificateFile
{
    private const SYSTEM = <<<'TXT'
        You read supplier material certificates: EN 10204 mill test certificates, certificates of analysis (CoA) and certificates of conformance (CoC).
        Extract every lot (heat number, batch number, date code) on the certificate and the test results printed for it, exactly as printed.
        Never estimate, convert or calculate a value. If a value is unreadable or ambiguous, leave it out and say so in notes.
        Values are plain decimal numbers written with a "." decimal separator and no units, thousands separators or signs other than a leading minus.
        If a result is printed as below a detection limit (for example "<0.005"), report the limit ("0.005") and mention it in notes.
        When a printed property means the same as one of the known property names you are given, use the known name exactly.
        TXT;

    public function __construct(private Client $client) {}

    public static function isEnabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @return array{lots: list<array{lot_number: string, size: string|null, quantity: string|null, quantity_unit: string|null, results: list<array{property: string, value: string}>}>, notes: string}
     */
    public function read(Certificate $certificate): array
    {
        if (! $certificate->hasUpload()) {
            throw new RuntimeException(__('This certificate has no file to read.'));
        }

        $content = [
            $this->fileBlock((string) $certificate->file_path),
            BetaTextBlockParam::with(text: $this->instructions($certificate)),
        ];

        $data = $this->ask($content);

        return [
            'lots' => array_values(array_map(fn (array $lot) => [
                'lot_number' => trim((string) $lot['lot_number']),
                'size' => $lot['size'] ?? null,
                'quantity' => $lot['quantity'] ?? null,
                'quantity_unit' => $lot['quantity_unit'] ?? null,
                'results' => array_values(array_map(fn (array $r) => ['property' => trim((string) $r['property']), 'value' => trim((string) $r['value'])], $lot['results'] ?? [])),
            ], $data['lots'] ?? [])),
            'notes' => (string) ($data['notes'] ?? ''),
        ];
    }

    /**
     * Send the request and return the decoded JSON. Kept separate so tests can replace it.
     *
     * @param  list<BetaRequestDocumentBlock|BetaImageBlockParam|BetaTextBlockParam>  $content
     * @return array<string, mixed>
     */
    protected function ask(array $content): array
    {
        try {
            $message = $this->client->beta->messages->create(
                model: (string) config('services.anthropic.model'),
                maxTokens: 16000,
                system: self::SYSTEM,
                messages: [['role' => 'user', 'content' => $content]],
                outputConfig: ['format' => ['type' => 'json_schema', 'schema' => self::schema()]],
                // On a policy decline, the API retries on a fallback model inside the same call.
                betas: ['server-side-fallback-2026-07-01'],
                fallbacks: 'default',
            );
        } catch (APIException $e) {
            report($e);

            throw new RuntimeException(__('The certificate could not be read right now. Try again, or enter the results by hand.'), previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException(__('The file was declined by the reader. Enter the results by hand.'));
        }

        if ($message->stopReason === 'max_tokens') {
            throw new RuntimeException(__('The certificate is too long to read in one go. Enter the results by hand.'));
        }

        foreach ($message->content as $block) {
            if ($block instanceof BetaTextBlock) {
                $data = json_decode($block->text, true);

                if (is_array($data)) {
                    return $data;
                }
            }
        }

        throw new RuntimeException(__('The reader returned nothing usable. Enter the results by hand.'));
    }

    /**
     * The certificate file as a document (PDF) or image content block.
     */
    private function fileBlock(string $path): BetaRequestDocumentBlock|BetaImageBlockParam
    {
        $data = base64_encode((string) Storage::disk('local')->get($path));

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'pdf' => BetaRequestDocumentBlock::with(source: BetaBase64PDFSource::with(data: $data)),
            'png' => BetaImageBlockParam::with(source: BetaBase64ImageSource::with(data: $data, mediaType: 'image/png')),
            'jpg', 'jpeg' => BetaImageBlockParam::with(source: BetaBase64ImageSource::with(data: $data, mediaType: 'image/jpeg')),
            default => throw new RuntimeException(__('Only PDF, JPG and PNG files can be read.')),
        };
    }

    private function instructions(Certificate $certificate): string
    {
        $properties = MaterialLimit::query()
            ->select('property', 'unit')
            ->distinct()
            ->orderBy('property')
            ->get()
            ->map(fn (MaterialLimit $limit) => $limit->property.($limit->unit ? " ({$limit->unit})" : ''))
            ->unique()
            ->implode(', ');

        return "Certificate number on file: {$certificate->number} (type: {$certificate->typeLabel()}).\n"
            .'Known property names: '.($properties ?: 'none').".\n"
            .'Extract the lots and results from the attached certificate.';
    }

    /**
     * @return array<string, mixed>
     */
    private static function schema(): array
    {
        $nullableString = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'lots' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'lot_number' => ['type' => 'string', 'description' => 'Heat, batch or lot number as printed'],
                            'size' => [...$nullableString, 'description' => 'Thickness or other size as a plain number, if printed'],
                            'quantity' => [...$nullableString, 'description' => 'Quantity as a plain number, if printed'],
                            'quantity_unit' => $nullableString,
                            'results' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'property' => ['type' => 'string'],
                                        'value' => ['type' => 'string', 'description' => 'Plain decimal number, no unit'],
                                    ],
                                    'required' => ['property', 'value'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['lot_number', 'size', 'quantity', 'quantity_unit', 'results'],
                        'additionalProperties' => false,
                    ],
                ],
                'notes' => ['type' => 'string', 'description' => 'Anything unreadable, ambiguous or reported as below a detection limit'],
            ],
            'required' => ['lots', 'notes'],
            'additionalProperties' => false,
        ];
    }
}
