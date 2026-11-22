<?php

namespace App\Services\IA;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

class ExtratorTextoEdital
{
    public const LIMITE_CARACTERES = 14000;

    public function extrairDePdf(UploadedFile $arquivo): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($arquivo->getRealPath());
            $textoBruto = $pdf->getText();
        } catch (Throwable $e) {
            throw new RuntimeException('Não foi possível ler o texto do arquivo PDF enviado. Verifique se o PDF não está corrompido ou protegido por senha.', 0, $e);
        }

        $textoLimpo = $this->limparTexto($textoBruto);

        if ($textoLimpo === '') {
            throw new RuntimeException('O PDF não contém texto selecionável (pode ser um documento escaneado como imagem). Cole o texto manualmente ou preencha o formulário.');
        }

        return $textoLimpo;
    }

    public function limparTexto(string $texto, int $limite = self::LIMITE_CARACTERES): string
    {
        // Remove caracteres de controle e normaliza espaços/quebras excessivas
        $limpo = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $texto) ?? $texto;
        $limpo = preg_replace("/[ \t]+/u", ' ', $limpo) ?? $limpo;
        $limpo = preg_replace("/\R{3,}/u", "\n\n", $limpo) ?? $limpo;
        $limpo = trim($limpo);

        if (mb_strlen($limpo) > $limite) {
            $limpo = mb_substr($limpo, 0, $limite);
        }

        return $limpo;
    }
}