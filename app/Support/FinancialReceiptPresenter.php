<?php

namespace App\Support;

use App\Models\FinancialTransaction;

class FinancialReceiptPresenter
{
    /**
     * @return array{
     *     fromName: string,
     *     categoryName: string,
     *     amount: string,
     *     amountExtenso: string,
     *     amountLine: string,
     *     city: string,
     *     day: string,
     *     month: string,
     *     year: string,
     *     dateLine: string,
     *     reciboNumero: string,
     *     memberCpfRg: string,
     *     memberAddress: string
     * }
     */
    public static function from(FinancialTransaction $transaction): array
    {
        $member = $transaction->member;

        if ($transaction->type === 'receita') {
            $fromName = $member
                ? PdfText::upper($member->name)
                : PdfText::upper($transaction->received_from_other ?: 'OUTROS');
        } else {
            $fromName = 'ASSEMBLEIA DE DEUS DE LUZIÂNIA em São Sebastião';
        }

        $fallbackCategory = $transaction->type === 'receita' ? 'RECEITA' : 'DESPESA';
        $categoryName = PdfText::upper($transaction->category?->name ?: $fallbackCategory);
        $amountNum = (float) $transaction->amount;
        $date = $transaction->transaction_date->copy()->locale('pt_BR');

        $cpf = trim((string) ($member?->cpf ?? ''));
        $rg = trim((string) ($member?->rg ?? ''));

        $amountFormatted = number_format($amountNum, 2, ',', '.');
        $amountExtenso = ValorPorExtenso::reais($amountNum);

        return [
            'fromName' => $fromName,
            'categoryName' => $categoryName,
            'amount' => $amountFormatted,
            'amountExtenso' => $amountExtenso,
            'amountLine' => 'R$ '.$amountFormatted.' ('.$amountExtenso.')',
            'city' => 'Brasília - DF',
            'day' => $date->format('d'),
            'month' => $date->translatedFormat('F'),
            'year' => $date->format('Y'),
            'dateLine' => $date->isoFormat('D [de] MMMM [de] YYYY'),
            'reciboNumero' => self::number($transaction),
            'memberCpfRg' => $cpf !== '' ? $cpf : $rg,
            'memberAddress' => $member ? $member->fullAddress() : '',
        ];
    }

    /**
     * Número do recibo: id da transação com zeros à esquerda.
     */
    public static function number(FinancialTransaction $transaction): string
    {
        return str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
    }
}
