<?php

declare(strict_types=1);

namespace TpmPsych\Validation;

/**
 * Validează `layout_definition_json` al unui șablon de raport. Structura
 * minimă cerută e `interpretation_texts`: o listă de blocuri text
 * autorizate, fiecare legat de un `interpretation_range_code` existent la
 * nivelul etaloanelor instrumentului (ex. codul unui interval de
 * interpretare definit în `norm_entries`). Textele propriu-zise sunt
 * conținut protejat — introduse ulterior de utilizator, nu generate aici.
 */
final class ReportTemplateValidator
{
    /**
     * @param array<string, mixed> $layoutDefinition
     * @param list<string> $knownInterpretationRangeCodes
     */
    public function validate(array $layoutDefinition, array $knownInterpretationRangeCodes): void
    {
        $texts = $layoutDefinition['interpretation_texts'] ?? null;

        if (! is_array($texts) || $texts === []) {
            throw new ValidationException(['Șablonul necesită cel puțin un bloc "interpretation_texts".']);
        }

        $seenCodes = [];

        foreach ($texts as $index => $text) {
            if (! is_array($text)) {
                throw new ValidationException(['Blocul #' . $index . ' din "interpretation_texts" trebuie să fie un obiect.']);
            }

            $code = $text['interpretation_range_code'] ?? null;
            $body = $text['body'] ?? null;

            if (! is_string($code) || $code === '') {
                throw new ValidationException(['Blocul #' . $index . ': "interpretation_range_code" e obligatoriu.']);
            }

            if (! is_string($body) || trim($body) === '') {
                throw new ValidationException(['Blocul #' . $index . ': "body" e obligatoriu și nu poate fi gol.']);
            }

            if (isset($seenCodes[$code])) {
                throw new ValidationException(['Codul de interpretare "' . $code . '" apare de mai multe ori în șablon.']);
            }
            $seenCodes[$code] = true;

            if ($knownInterpretationRangeCodes !== [] && ! in_array($code, $knownInterpretationRangeCodes, true)) {
                throw new ValidationException(['"' . $code . '" nu corespunde niciunui interval de interpretare definit în etaloanele instrumentului.']);
            }
        }
    }
}
